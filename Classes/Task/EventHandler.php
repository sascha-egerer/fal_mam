<?php
namespace Crossmedia\FalMam\Task;

use Crossmedia\FalMam\Service\MamClient;
use Crossmedia\FalMam\Task\EventHandlerState;
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
	 * @var \TYPO3\CMS\Frontend\Page\PageRepository
	 */
	protected $pageRepository;

	public function execute() {
		$this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->client = $this->objectManager->get('\Crossmedia\FalMam\Service\MamClient');
		$this->dataHandler = $this->objectManager->get('\TYPO3\CMS\Core\DataHandling\DataHandler');
		$this->sys_page = $this->objectManager->get('\TYPO3\CMS\Frontend\Page\PageRepository');
		$this->state = $this->objectManager->get('\Crossmedia\FalMam\Task\EventHandlerState');

		$configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']["EXT"]["extConf"]['fal_mam']);
		$configuration = $configuration['fal_mam.'];
		$this->basePath = $configuration['base_path'];

		if ($this->hasConfigurationChanged()) {
			echo 'configuration has changed';
			$this->synchronize();
		} else {
			echo 'configuration has not changed';

			$events = $this->client->getEvents($this->state->getEventId());
			foreach ($events as $event) {
				$this->processEvent($event);
			}
		}
	}

	public function synchronize() {
		$result = $this->client->synchronize(
			$this->state->getSyncId(),
			$this->state->getSyncOffset()
		);

		if (count($result['ids']) === 0) {
			// full sync finished, set config hash to resume normal event-handling
			$this->state->setSyncId(0);
			$this->state->setSyncOffset(0);
			$this->state->setConfigHash($this->client->getConfigHash());
			$this->state->save();
			return;
		}

		// todo: actually implement synchronization once it's running on the
		// mam side

		// $beans = $this->client->getBeans($result['ids'], 'contact_pap_connector');

		// foreach ($beans as $bean) {
		// 	var_dump($bean);
		// }

		// increase offset to check for more sync jobs
		$this->state->setSyncId($result['event_id']);
		$this->state->increaseSyncOffset();
	}

	/**
	 * process an event
	 *
	 * @param  array $event
	 * @return void
	 */
	public function processEvent($event) {
		switch ($event['event_type']) {
			case 0: // delete
				$this->processDeleteEvent($event);
				break;

			case 1: // update
				$this->processUpdateEvent($event);
				break;

			case 2: // create
				$this->processCreateEvent($event);
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
		// echo 'delete' . chr(10);
	}

	/**
	 * process a update event
	 *
	 * @param  array $event
	 * @return void
	 */
	public function processUpdateEvent($event) {
		// echo 'update' . chr(10);
	}

	/**
	 * process a create event
	 *
	 * @param  array $event
	 * @return void
	 */
	public function processCreateEvent($event) {
		// echo 'create' . chr(10);
		// $beans = $this->client->getBeans($event['object_id']);
		// todo: implement metadata update when mam is ready

		$filepath = $this->basePath . $event['object_id'];
		file_put_contents($filepath, $this->client->getDerivate($event['object_id']));
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