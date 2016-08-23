<?php
namespace Crossmedia\FalMam\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * The Dashboard Controller offers a basic overview of the synchronisation and
 * mapping of mam fields to fal fields.
 */
class DashboardController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var integer
	 */
	protected $eventsPerPage = 50;

	/**
	 * @var \Crossmedia\FalMam\Service\MamClient
	 * @inject
	 */
	protected $client;

	/**
	 * @var \TYPO3\CMS\Core\DataHandling\DataHandler
	 * @inject
	 */
	protected $dataHandler;

	/**
	 * @var \Crossmedia\FalMam\Service\Configuration
	 * @inject
	 */
	protected $configuration;

	/**
	 * @var \Crossmedia\FalMam\Task\EventHandlerState
	 * @inject
	 */
	protected $state;

	/**
	 * @var \Crossmedia\FalMam\Service\Logger
	 */
	protected $logger;

	public function __construct() {
		$this->logger = new \Crossmedia\FalMam\Service\Logger();
	}

	/**
	 * Show current status of the synchronisation
	 *
	 * @param integer $page
	 * @return void
	 */
	public function indexAction($page = 0) {
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_falmam_event_queue',
			'status != "DONE"',
			'',
			'crdate DESC, target ASC',
			$this->eventsPerPage . ' OFFSET ' . ($this->eventsPerPage * $page)
		);
		$this->view->assign('events', $rows);

		$totalPending = $this->getTotalPending();
		$this->view->assign('totalPending', $totalPending);

		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'SUM(runtime) / COUNT(uid) as seconds_per_event',
			'tx_falmam_event_queue',
			'status = "DONE" AND tstamp > "' . (time() - (60 * 10)) . '"'
		);
		// take full log into account if nothing happened in the last hour
		if ($row['seconds_per_event'] === 0) {
			$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'SUM(runtime) / COUNT(uid) as seconds_per_event',
				'tx_falmam_event_queue',
				'status = "DONE"'
			);
		}

		// $runtimes = $this->getEventQueueRuntimes($totalPending);
		$runtimes = array();
		$runtimes['averageRuntime'] = intval($row['seconds_per_event']);
		$runtimes['remainingRuntime'] = $this->convertSecondsToHumanTime(
			(intval($row['seconds_per_event'])/1000) * $totalPending
		);
		$this->view->assign('runtimes', $runtimes);
	}

	/**
	 * Update Mapping configuration
	 *
	 * @param array $fields
	 * @param array $valueMaps
	 * @return void
	 */
	public function configurationAction($fields = NULL, $valueMaps = NULL) {
		// Save mapping and restart sync
		if ($fields !== NULL) {
			$this->saveFields($fields, $valueMaps);
			$this->state->setConfigHash($this->client->getConfigHash());
			$this->state->save();
		}

		if ($this->hasConfigurationChanged()) {
			$this->addFlashMessage('Please check the mapping to add/update any new/changed fields.', 'The MAM Configuration has changed since the last mapping.', \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING);
		}

		$existingFields = $this->getExistingFields();
		$fields = $this->getFieldsFromConnectorConfiguration($existingFields);
		$this->view->assign('fields', $fields);

		$falFields = $this->getFalFieldsFromTca();
		$this->view->assign('falFields', $falFields);
	}

	/**
	 * start a full synchronisation
	 *
	 * @return void
	 */
	public function syncAction() {
		$this->logger->warning('Full synchronisation triggered!');

		// flush "outdated" pending events
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_falmam_event_queue', '1=1');

		$this->state->setEventId(-1);
		$this->state->save();
		$this->addFlashMessage('full synchronisation will start shortly.');
		$this->redirect('configuration');
	}

	/**
	 * start a full synchronisation
	 *
	 * @param string $uuid
	 * @return void
	 */
	public function analyzeAction($uuid = NULL) {
		if ($uuid !== NULL) {
			$beans = $this->client->getBeans($uuid);
			$this->view->assign('beans', $beans);
		}
	}

	/**
	 * start a full synchronisation
	 *
	 * @return void
	 */
	public function skipHistoryAction() {

		if (isset($_REQUEST['eventId'])) {
			$lastEventId = intval($_REQUEST['eventId']);
		} else {
			$lastEventId = 0;
			$break = 0;
			while (count($events = $this->client->getEvents($lastEventId + 1)) > 0) {
				$last = end($events);
				$lastEventId = $last['id'];
			}
		}

		echo 'setting event id to: ' . $lastEventId . '<br />';
		$this->state->setEventId($lastEventId);
		$this->state->save();

		echo 'flushing events table<br />';
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_falmam_event_queue', '1=1');
		return '';
	}

	/**
	 * check if the configuration has changed since the last time the task has
	 * run
	 *
	 * @return boolean
	 */
	public function hasConfigurationChanged() {
		return $this->state->getConfigHash() !== $this->client->getConfigHash();
	}

	/**
	 * get the current amount of pending events.
	 *
	 * @return integer
	 */
	public function getTotalPending() {
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('COUNT(uid) as totalPending', 'tx_falmam_event_queue', 'status != "DONE"');
		return $row['totalPending'];
	}

	/**
	 * get the current runtimes based on the per event runtime
	 *
	 * @param integer $totalPending
	 * @return array
	 */
	public function getEventQueueRuntimes($totalPending) {
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('MIN(runtime) as minRuntime, MAX(runtime) as maxRuntime, AVG(runtime) as averageRuntime', 'tx_falmam_event_queue', 'runtime > 0');
		$row['minRuntime'] = number_format($row['minRuntime'], 2);
		$row['maxRuntime'] = number_format($row['maxRuntime'], 2);
		$row['averageRuntime'] = number_format($row['averageRuntime'], 2);
		$row['remainingRuntime'] = $this->convertSecondsToHumanTime((($row['averageRuntime'] * $totalPending) / 1000) * 2);
		return $row;
	}

	/**
	 * convert seconds into a human readable time string
	 *
	 * @param integer $seconds
	 * @return string
	 */
	public function convertSecondsToHumanTime($seconds) {
		$parts = array(
			'months' => floor($seconds/2592000),
			'days' => floor(($seconds%2592000)/86400),
			'hours' => floor(($seconds%86400)/3600),
			'minutes' => floor(($seconds%3600)/60),
			'seconds' => $seconds%60
		);
		foreach ($parts as $part => $value) {
			if ($value == 0) {
				unset($parts[$part]);
			} else {
				$parts[$part] = $value . ' ' . $part;
			}
		}
		return implode(', ', $parts);
	}

	/**
	 * save the submitted mapping configuration
	 *
	 * @param array $fields
	 * @param array $valueMaps
	 * @return void
	 */
	public function saveFields($fields, $valueMaps) {
		$data = array('tx_falmam_mapping' => array());
		foreach ($fields as $uid => $falField) {
			$data['tx_falmam_mapping'][$uid] = array(
				'fal_field' => $falField,
				'value_map' => $valueMaps[$uid]
			);
		}
		$this->dataHandler->start($data, array());
		$this->dataHandler->process_datamap();
		$this->addFlashMessage('mapping has been saved.');
	}

	/**
	 * get all existing mapped fields
	 *
	 * @return array
	 */
	public function getExistingFields() {
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_falmam_mapping',
			'connector_name = "' . $this->configuration->connector_name . '"',
			'',
			'',
			'',
			'mam_field'
		);
	}

	/**
	 * get all fields that are currently configured in the connector config
	 * it also takes care of removing old fields
	 *
	 * @param array $existingFields
	 * @return array
	 */
	public function getFieldsFromConnectorConfiguration($existingFields) {
		$configuration = $this->client->getConnectorConfig();
		$fields = array();
		foreach ($configuration['fieldsToLoad'] as $fieldName => $fieldInfo) {
			$fields[$fieldName]['fieldName'] = $fieldName;
			$fields[$fieldName]['fieldType'] = $fieldInfo['fieldType'];
			if (!isset($existingFields[$fieldName])) {
				$data['tx_falmam_mapping']['NEW'] = array(
					'pid' => $this->configuration->storage_pid,
					'connector_name' => $this->configuration->connector_name,
					'mam_field' => $fieldName
				);

				$this->dataHandler->start($data, array());
				$this->dataHandler->process_datamap();

				$fields[$fieldName]['uid'] = $this->dataHandler->substNEWwithIDs[$this->uid];
			} else {
				$fields[$fieldName]['uid'] = $existingFields[$fieldName]['uid'];
				$fields[$fieldName]['fal_field'] = $existingFields[$fieldName]['fal_field'];
				$fields[$fieldName]['value_map'] = $existingFields[$fieldName]['value_map'];
				unset($existingFields[$fieldName]);
			}
		}
		ksort($fields);

		// remove old fields
		foreach ($existingFields as $existingField) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_falmam_mapping', 'uid = "' . $existingField['uid'] . '"');
		}

		return $fields;
	}

	/**
	 * returns all in tca configured metadata fields
	 *
	 * @return array
	 */
	public function getFalFieldsFromTca() {
		$falFields = array('' => '- not mapped -');
		foreach ($GLOBALS['TCA']['sys_file_metadata']['columns'] as $columnName => $column) {
			$falFields[$columnName] = LocalizationUtility::translate($column['label'], '') . ' (' . $columnName . ')';
		}
		ksort($falFields);
		return $falFields;
	}
}
