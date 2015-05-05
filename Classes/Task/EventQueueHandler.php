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

	/**
	 * @var string
	 */
	protected $items = 10;

	/**
	 * @param  \Crossmedia\FalMam\Service\MamClient $client
	 * @return void
	 */
	public function injectClient(\Crossmedia\FalMam\Service\MamClient $client) {
		$this->client = $client;
	}

	/**
	 * @param  \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
	 * @return void
	 */
	public function injectDataHandler(\TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler) {
		$this->dataHandler = $dataHandler;
	}

	/**
	 * @param  \Crossmedia\FalMam\Task\EventHandlerState $state
	 * @return void
	 */
	public function injectState(\Crossmedia\FalMam\Task\EventHandlerState $state) {
		$this->state = $state;
	}

	/**
	 * @param  \Crossmedia\FalMam\Service\Configuration $configuration
	 * @return void
	 */
	public function injectConfiguration(\Crossmedia\FalMam\Service\Configuration $configuration) {
		$this->configuration = $configuration;
	}

	/**
	 * @param \Crossmedia\FalMam\Service\DbHandler $dbHandler
	 * @return void
	 */
	public function injectDbHandler(\Crossmedia\FalMam\Service\DbHandler $dbHandler) {
		$this->dbHandler = $dbHandler;
	}

	/**
	 * @param \Crossmedia\FalMam\Service\FileHandler $fileHandler
	 * @return void
	 */
	public function injectFileHandler(\Crossmedia\FalMam\Service\FileHandler $fileHandler) {
		$this->fileHandler = $fileHandler;
	}

	public function execute() {
		$this->initialize();

		$this->items = 1000;

		$counter = 0;
		$start = time();
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
		$this->dbHandler->addLog($start, time(), $counter);

		return TRUE;
	}

	public function initialize() {
		$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		if ($this->client === NULL) {
			$this->injectClient($objectManager->get('\Crossmedia\FalMam\Service\MamClient'));
		}
		if ($this->dataHandler === NULL) {
			$this->injectDataHandler($objectManager->get('\TYPO3\CMS\Core\DataHandling\DataHandler'));
		}
		if ($this->dbHandler === NULL) {
			$this->injectDbHandler($objectManager->get('\Crossmedia\FalMam\Service\DbHandler'));
		}
		if ($this->fileHandler === NULL) {
			$this->injectFileHandler($objectManager->get('\Crossmedia\FalMam\Service\FileHandler'));
		}
		if ($this->state === NULL) {
			$this->injectState($objectManager->get('\Crossmedia\FalMam\Task\EventHandlerState'));
		}
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
				return $this->processCreateEvent($event);

			case 'update': // update
				return $this->processUpdateEvent($event);
				break;

			case 'delete': // delete
				return $this->processDeleteEvent($event);
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
			$this->dbHandler->deleteAsset($event['object_id']);
		}

		if ($event['target'] == 'file' || $event['target'] == 'both') {
			// file gets deleted by the ResourceStorage together with the Asset Data
		}

		return TRUE;
	}

	/**
	 * process a update event
	 *
	 * @param  array $event
	 * @return void
	 */
	public function processCreateEvent($event) {
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
	 * process a update event
	 *
	 * @param  array $event
	 * @return void
	 */
	public function processUpdateEvent($event) {
		$beans = $this->client->getBeans($event['object_id']);
		$bean = current($beans);

		if ($event['target'] == 'file' || $event['target'] == 'both') {
			$this->client->saveDerivate(
				$bean['properties']['data_shellpath'] . $bean['properties']['data_name'],
				$event['object_id']
			);
			// todo: remove old file!!
		}

		if ($event['target'] == 'metadata' || $event['target'] == 'both') {
			$this->dbHandler->updateAsset(
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