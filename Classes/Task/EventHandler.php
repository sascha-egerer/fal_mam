<?php
namespace Crossmedia\FalMam\Task;

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
	 * @var \Crossmedia\FalMam\Service\Configuration
	 */
	protected $configuration;

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

	public function execute() {
		$this->initialize();

		if ($this->hasConfigurationChanged()) {
			// notify someone to update the configuration
		}

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

			$this->saveEvents($data);
			// echo count($events) . ': ' . (microtime(TRUE) - $start) . chr(10);

			$this->state->setEventId($event['id']);
			$this->state->save();
		}

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
		if ($this->state === NULL) {
			$this->injectState($objectManager->get('\Crossmedia\FalMam\Task\EventHandlerState'));
		}
		if ($this->configuration === NULL) {
			$this->injectConfiguration($objectManager->get('\Crossmedia\FalMam\Service\Configuration'));
		}
	}

	public function saveEvents($data) {
		$this->dataHandler->start($data, array());
		$this->dataHandler->process_datamap();
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