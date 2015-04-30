<?php
namespace Crossmedia\FalMam\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * ProjectController
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
			'event_id',
			$this->eventsPerPage . ' OFFSET ' . ($this->eventsPerPage * $page)
		);
		$this->view->assign('events', $rows);

		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('COUNT(uid) as totalPending', 'tx_falmam_event_queue', 'status != "DONE"');
		$totalPending = $row['totalPending'];
		$this->view->assign('totalPending', $totalPending);

		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('MIN(runtime) as minRuntime, MAX(runtime) as maxRuntime, AVG(runtime) as averageRuntime', 'tx_falmam_event_queue', 'runtime > 0');
		$row['minRuntime'] = number_format($row['minRuntime'], 2);
		$row['maxRuntime'] = number_format($row['maxRuntime'], 2);
		$row['averageRuntime'] = number_format($row['averageRuntime'], 2);

		$row['remainingRuntime'] = $this->secondsToHumanTime((($row['averageRuntime'] * $totalPending) / 1000) * 2);
		$this->view->assign('runtimes', $row);
	}

	/**
	 *
	 * @param array $fields
	 * @return void
	 */
	public function configurationAction($fields = NULL) {
		// Save mapping and restart sync
		if ($fields !== NULL) {
			$data = array('tx_falmam_mapping' => array());
			foreach ($fields as $uid => $falField) {
				$data['tx_falmam_mapping'][$uid] = array('fal_field' => $falField);
			}
			$this->dataHandler->start($data, array());
			$this->dataHandler->process_datamap();
			$this->addFlashMessage('mapping has been saved.<br /> full synchronisation will start shortly.');

			$this->state->setEventId(0);
			$this->state->setConfigHash($this->client->getConfigHash());
			$this->state->save();

			// flush "outdated" pending events
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_falmam_event_queue', '1=1');
		}

		if ($this->hasConfigurationChanged()) {
			$this->addFlashMessage('Please check the mapping to add/update any new/changed fields.', 'The MAM Configuration has changed since the last mapping.', \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING);
		}

		$configuration = $this->client->getConnectorConfig();

		$existingFields = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_falmam_mapping',
			'connector_name = "' . $this->configuration->connector_name . '"',
			'',
			'',
			'',
			'mam_field'
		);

		$fields = $configuration['fieldsToLoad'];
		foreach ($fields as $fieldName => $field) {
			if (!isset($existingFields[$field['fieldName']])) {
				$data['tx_falmam_mapping']['NEW'] = array(
					'pid' => $this->configuration->storage_pid,
					'connector_name' => $this->configuration->connector_name,
					'mam_field' => $field['fieldName']
				);

				$this->dataHandler->start($data, array());
				$this->dataHandler->process_datamap();

				$fields[$fieldName]['uid'] = $this->dataHandler->substNEWwithIDs[$this->uid];
			} else {
				$fields[$fieldName]['uid'] = $existingFields[$fieldName]['uid'];
				$fields[$fieldName]['fal_field'] = $existingFields[$fieldName]['fal_field'];
				unset($existingFields[$fieldName]);
			}
		}
		ksort($fields);
		$this->view->assign('fields', $fields);

		// remove old fields
		foreach ($existingFields as $existingField) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_falmam_mapping', 'uid = "' . $existingField['uid'] . '"');
		}

		$falFields = array('' => '- not mapped -');
		foreach ($GLOBALS['TCA']['sys_file_metadata']['columns'] as $columnName => $column) {
			$falFields[$columnName] = LocalizationUtility::translate($column['label']) . ' (' . $columnName . ')';
		}
		ksort($falFields);
		$this->view->assign('falFields', $falFields);
	}

	public function secondsToHumanTime($seconds) {
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
	 * check if the configuration has changed since the last time the task has
	 * run
	 *
	 * @return boolean
	 */
	public function hasConfigurationChanged() {
		return $this->state->getConfigHash() !== $this->client->getConfigHash();
	}

}