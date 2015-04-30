<?php
namespace Crossmedia\FalMam\Task;

use Crossmedia\FalMam\Service\DbHandler;
use Crossmedia\FalMam\Service\FileHandler;
use Crossmedia\FalMam\Service\MamClient;
use Crossmedia\FalMam\Task\EventHandlerState;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;


class EventHandler extends AbstractTask {

	/**
	 * @var \Crossmedia\FalMam\Service\MamClient
	 */
	protected $client;

	/**
	 * @var \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	protected $dataHandler;

	/**
	 * @var \Crossmedia\FalMam\Task\EventHandlerState
	 */
	protected $state;

	/**
	 * @var \Crossmedia\FalMam\Service\FileHandler
	 */
	protected $fileHandler;

	/**
	 * @var \Crossmedia\FalMam\Service\DbHandler
	 */
	protected $dbHandler;

	/**
	 * @var \Crossmedia\FalMam\Service\Configuration
	 */
	protected $configuration;

	public function execute() {
		$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->client = $objectManager->get('\Crossmedia\FalMam\Service\MamClient');
		$this->dataHandler = $objectManager->get('\TYPO3\CMS\Core\DataHandling\DataHandler');
		$this->dbHandler = $objectManager->get('\Crossmedia\FalMam\Service\DbHandler');
		$this->fileHandler = $objectManager->get('\Crossmedia\FalMam\Service\FileHandler');
		$this->state = $objectManager->get('\Crossmedia\FalMam\Task\EventHandlerState');
		$this->configuration = $objectManager->get('\Crossmedia\FalMam\Service\Configuration');


		if ($this->hasConfigurationChanged()) {
			// notify someone to update the configuration
		}

		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'COUNT(*) as pending_events',
			'tx_falmam_event_queue',
			'status = "NEW" AND skipuntil < ' . time()
		);

		// if ($row['pending_events'] > 300) {
		// 	return;
		// }
		//
		//
		$eventTypes = array(
			0 => 'delete',
			1 => 'update',
			2 => 'create'
		);
		$targetTypes = array(
			0 => 'metadata',
			1 => 'file',
			2 => 'both'
		);

		while (count($events = $this->client->getEvents($this->state->getEventId() + 1)) > 0) {
			// $start = microtime(TRUE);
			$data = array();
			foreach ($events as $key => $event) {
				$data['tx_falmam_event_queue']['NEW' . $event['id']] = array(
					'pid' => $this->configuration->storage_pid,
					'event_id' => $event['id'],
					'event_type' => $eventTypes[$event['event_type']],
					'target' => $targetTypes[$event['object_type']],
					'object_id' => $event['object_id'],
					'status' => 'NEW'
				);
			}

			$this->dataHandler->start($data, array());
			$this->dataHandler->process_datamap();
			// echo count($events) . ': ' . (microtime(TRUE) - $start) . chr(10);

			$this->state->setEventId($event['id']);
			$this->state->save();
		}

		return TRUE;
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

?>