<?php
namespace Crossmedia\FalMam\Task;

use Crossmedia\FalMam\Service\DbHandler;
use Crossmedia\FalMam\Service\FileHandler;
use Crossmedia\FalMam\Service\MamClient;
use Crossmedia\FalMam\Task\EventHandlerState;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;


class EventQueueHandler extends AbstractTask {

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
	 * @inject
	 */
	protected $configuration;

	public function execute() {
		$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->client = $objectManager->get('\Crossmedia\FalMam\Service\MamClient');
		$this->dataHandler = $objectManager->get('\TYPO3\CMS\Core\DataHandling\DataHandler');
		$this->dbHandler = $objectManager->get('\Crossmedia\FalMam\Service\DbHandler');
		$this->fileHandler = $objectManager->get('\Crossmedia\FalMam\Service\FileHandler');
		$this->state = $objectManager->get('\Crossmedia\FalMam\Task\EventHandlerState');

		$this->items = 10000;

		$counter = 0;
		while ($counter < $this->items) {
			$event = $this->dbHandler->claimEventFromQueue();
			if ($event === NULL) {
				// nothing left to do
				return TRUE;
			}
			$counter++;

			// echo $event['object_id'] . chr(10);
			$success = $this->processEvent($event);

			if ($success === TRUE) {
				$this->dbHandler->finnishEvent($event);
			} else {
				$this->dbHandler->rescheduleEvent($event);
			}
			unset($event);
		}

		return TRUE;
	}

	/**
	 * process an event
	 *
	 * @param  array $event
	 * @return void
	 */
	public function processEvent($event) {
		switch ($event['event_type']) {
			case 'create': // create
			case 'update': // update
				return $this->processUpdateEvent($event);
				break;

			case 'delete': // delete
				// $this->processDeleteEvent($event);
				break;

			default:
				// todo: wtf => exception
				break;
		}
	}

	/**
	 * process a delete event
	 *
	 * @param  array $event
	 * @return void
	 */
	public function processDeleteEvent($event) {
		if ($event['target'] == 'metadata' || $event['target'] == 'both') {
			$this->dbHandler->deleteAsset(
				$event['object_id']
			);
		}

		if ($event['target'] == 'file' || $event['target'] == 'both') {
			$this->fileHandler->deleteFile(
				$event['object_id']
			);
		}

		return FALSE;
	}

	/**
	 * process a update event
	 *
	 * @param  array $event
	 * @return void
	 */
	public function processUpdateEvent($event) {
		$beans = $this->client->getBeans($event['object_id']);
		$bean = current($beans);

		if ($bean['type'] == 'folder') {
			$this->fileHandler->createFolder($bean['properties']['data_shellpath']);
			unset($bean, $beans);
			return TRUE;
		}

		if ($event['target'] == 'file' || $event['target'] == 'both') {
			$this->client->saveDerivate(
				$bean['properties']['data_shellpath'] . $bean['properties']['data_name'],
				$event['object_id']
			);
		}

		if ($event['target'] == 'metadata' || $event['target'] == 'both') {
			if (FALSE == file_exists($bean['properties']['data_shellpath'] . $bean['properties']['data_name'])) {
				unset($bean, $beans);
				return FALSE;
			}

			$this->dbHandler->createAsset(
				$bean['properties']['data_name'],
				$bean['properties']['data_shellpath'],
				$event['object_id'],
				$bean['properties']
			);
		}
		unset($bean, $beans);
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