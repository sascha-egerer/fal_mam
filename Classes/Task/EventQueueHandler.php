<?php
namespace Crossmedia\FalMam\Task;

use Crossmedia\FalMam\Service\FileHandler;
use Crossmedia\FalMam\Service\MamClient;
use Crossmedia\FalMam\Task\EventHandlerState;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\ResourceFactory;
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
	 * @var \Crossmedia\FalMam\Service\Configuration
	 * @inject
	 */
	protected $configuration;

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceStorage
	 */
	protected $resourceStorage;

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceFactory
	 */
	protected $resourceFactory;

	/**
	 * @var string
	 */
	protected $items = 10;

	/**
	 * @var integer
	 */
	protected $reclaimTime = 60;

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
	 * @param \TYPO3\CMS\Core\Resource\ResourceStorage $resourceStorage
	 * @return void
	 */
	public function injectResourceStorage(\TYPO3\CMS\Core\Resource\ResourceStorage $resourceStorage) {
		$this->resourceStorage = $resourceStorage;
	}

	/**
	 * @param \TYPO3\CMS\Core\Resource\ResourceFactory $resourceFactory
	 * @return void
	 */
	public function injectResourceFactory(\TYPO3\CMS\Core\Resource\ResourceFactory $resourceFactory) {
		$this->resourceFactory = $resourceFactory;
	}

	public function execute() {
		$this->initialize();

		$this->items = 1000;

		$counter = 0;
		$start = time();
		while ($counter < $this->items) {
			$event = $this->claimEventFromQueue();

			if ($event === NULL) {
				// nothing left to do
				return TRUE;
			}
			$counter++;

			// echo $event['object_id'] . chr(10);
			$success = $this->processEvent($event);

			if ($success === TRUE) {
				$this->finnishEvent($event);
			} else {
				$this->rescheduleEvent($event);
			}
			unset($event);
		}
		$this->addLog($start, time(), $counter);

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
		if ($this->resourceStorage === NULL) {
			$storageRepository = $objectManager->get('\TYPO3\CMS\Core\Resource\StorageRepository');
			$this->resourceStorage =  current($storageRepository->findByStorageType('MAM'));
		}

		if ($this->resourceFactory === NULL) {
			$this->injectResourceFactory(ResourceFactory::getInstance());
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
			$this->deleteAsset($event['object_id']);
		}

		if ($event['target'] == 'file' || $event['target'] == 'both') {
			$this->deleteAsset($event['object_id']);
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

		$result = TRUE;

		if ($bean['type'] == 'folder') {
			$this->createFolder($bean['properties']['data_shellpath']);
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
			$result = $this->createAsset(
				$bean['properties']['data_name'],
				$bean['properties']['data_shellpath'],
				$event['object_id'],
				$bean['properties']
			);
		}
		unset($bean, $beans);
		return $result;
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

		$result = TRUE;

		if ($event['target'] == 'file' || $event['target'] == 'both') {
			$this->moveFile(
				$event['object_id'],
				$bean['properties']['data_shellpath'],
				$bean['properties']['data_name']
			);
			$this->client->saveDerivate(
				$bean['properties']['data_shellpath'] . $bean['properties']['data_name'],
				$event['object_id']
			);
		}

		if ($event['target'] == 'metadata' || $event['target'] == 'both') {
			$result = $this->updateAsset(
				$bean['properties']['data_name'],
				$bean['properties']['data_shellpath'],
				$event['object_id'],
				$bean['properties']
			);
		}
		unset($bean, $beans);
		return $result;
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
	 * create the asset in the sys_file database through the fal api.
	 * This needs the file to already exist on the filesystem.
	 *
	 * @param  string $filename
	 * @param  string $filepath
	 * @param  string $mamId
	 * @param  string $metadata
	 * @return void
	 */
	public function createAsset($filename, $filepath, $mamId, $metadata) {
		$path = str_replace($this->configuration->base_path, '', $filepath . $filename);

		if (FALSE == $this->fileExists($filepath . $filename)) {
			return FALSE;
		}

		$fileObject = $this->resourceFactory->getObjectFromCombinedIdentifier($this->resourceStorage->getUid() . ':/' . $path);
		$fileObject->_getMetaData();

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_file', 'uid = ' . $fileObject->getUid(), array(
			'tx_falmam_id' => $mamId
		));

		$data = $this->mapMetadata($metadata);
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_file_metadata', 'file=' . $fileObject->getUid(), $data);

		// call hook after creating an asset
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXT']['fal_mam']['Service/DbHandler.php']['assetCreated'])) {
			$params = array(
				'path' => $path,
				'fileObject' => $fileObject
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXT']['fal_mam']['Service/DbHandler.php']['assetCreated'] as $reference) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($reference, $params, $this);
			}
		}

		unset($fileObject, $path, $data);

		return TRUE;
	}

	/**
	 * update the asset in the sys_file database through the fal api.
	 *
	 * @param  string $filename
	 * @param  string $filepath
	 * @param  string $mamId
	 * @param  string $metadata
	 * @return void
	 */
	public function updateAsset($filename, $filepath, $mamId, $metadata) {
		$fileObject = $this->getFileObject($mamId);

		if ($fileObject === NULL) {
			// false update event -> create!
			return $this->createAsset($filename, $filepath, $mamId, $metadata);
		}

		$path = str_replace($this->configuration->base_path, '', $filepath . $filename);

		$oldFilePath = realpath($this->configuration->base_path . $fileObject->getIdentifier());
		$newFilePath = realpath($filepath . $filename);

		if ($oldFilePath !== $newFilePath) {
			$this->moveFile($mamId, $filepath, $filename);
		}

		$data = $this->mapMetadata($metadata);
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_file_metadata', 'file=' . $fileObject->getUid(), $data);

		// call hook after updating an asset
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXT']['fal_mam']['Service/DbHandler.php']['assetUpdated'])) {
			$params = array(
				'path' => $path,
				'fileObject' => $fileObject
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXT']['fal_mam']['Service/DbHandler.php']['assetUpdated'] as $reference) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($reference, $params, $this);
			}
		}

		unset($fileObject, $path, $data);

		return TRUE;
	}

	/**
	 * remove an asset rom the fal database.
	 * This also removes the file from the filesystem, because fal does this
	 * in one action.
	 *
	 * @param  string $mamId
	 * @return void
	 */
	public function deleteAsset($mamId) {
		$fileObject = $this->getFileObject($mamId);

		if ($fileObject === NULL) {
			return;
		}
		$this->resourceStorage->deleteFile($fileObject);

		// call hook after deleting an asset
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXT']['fal_mam']['Service/DbHandler.php']['assetDeleted'])) {
			$params = array(
				'fileObject' => $fileObject
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXT']['fal_mam']['Service/DbHandler.php']['assetDeleted'] as $reference) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($reference, $params, $this);
			}
		}
	}

	/**
	 * claims a pending event from the even_queue table and sets the status of
	 * the claimed event to "CLAIMED"
	 *
	 * @return array
	 */
	public function claimEventFromQueue() {
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_falmam_event_queue',
			'(status = "NEW" AND skipuntil < ' . time() . ') OR (status = "CLAIMED" AND tstamp < ' . (time() - $reclaimTime) . ')',
			'',
			'event_id',
			'5',
			'object_id'
		);
		if (count($rows) > 0) {
			$event = current($rows);
			unset($rows);

			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'tx_falmam_event_queue',
				'uid=' . $event['uid'],
				array(
					'tstamp' => time(),
					'status' => 'CLAIMED'
				)
			);

			$event['start'] = microtime(TRUE);
			return $event;
		}
	}

	/**
	 * called after finishing a event to set the status to "DONE" and save the
	 * runtime.
	 *
	 * @param  array $event
	 * @return void
	 */
	public function finnishEvent($event) {
		if (isset($event['start'])) {
			$event['runtime'] = number_format((microtime(TRUE) - $event['start']) * 1000, 2);
		}

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_falmam_event_queue',
			'uid=' . $event['uid'],
			array(
				'status' => 'DONE',
				'runtime' => $event['runtime'],
				'skipuntil' => NULL
			)
		);
	}

	/**
	 * sets a back to the status "NEW" and adds a "skipuntil" timestamp to
	 * delay the next execution by 1 second. This mostly happens when the
	 * metadata event occurs before the file itself was saved to the filesystem.
	 *
	 * @param  array $event
	 * @return void
	 */
	public function rescheduleEvent($event) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_falmam_event_queue',
			'uid=' . $event['uid'],
			array(
				'tstamp' => time(),
				'status' => 'NEW',
				'skipuntil' => time() + 1
			)
		);
	}

	/**
	 * adds a log entry to the tx_falmam_log table
	 *
	 * @param integer $start
	 * @param integer $stop
	 * @param integer $count
	 */
	public function addLog($start, $stop, $count) {
		$data = array();
		$data['tx_falmam_log']['NEW'] = array(
			'pid' => $this->configuration->storage_pid,
			'connector_name' => $this->configuration->connector_name,
			'start_time' => $start,
			'end_time' => $stop,
			'event_count' => $count,
			'runtime' => $stop - $start,
		);

		$this->dataHandler->start($data, array());
		$result = $this->dataHandler->process_datamap();
	}

	/**
	 * fetches a fal file object from the ResourceFactory based on the provided
	 * mamId
	 *
	 * @param  string $mamId
	 * @return void
	 */
	public function getFileObject($mamId) {
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'*',
			'sys_file',
			'tx_falmam_id = "' . $mamId . '"'
		);

		if (!is_array($row)) {
			// todo: exception!
			return NULL;
		}

		return $this->resourceFactory->getFileObject($row['uid'], $row);

	}

	/**
	 * maps mam metadata based on the mapping configuration to the fal metadata
	 * fields
	 *
	 * @param  array $metadata
	 * @return array
	 */
	public function mapMetadata($metadata) {
		$data = array();
		foreach ($this->configuration->mapping as $mamField => $mapping) {
			if (isset($metadata[$mamField]) && strlen($mapping['fal_field']) > 0) {
				$value = $metadata[$mamField];

				if (isset($mapping['value_map'][$value])) {
					$value = $mapping['value_map'][$value];
				}

				$data[$mapping['fal_field']] = $value;
			}
		}
		return $data;
	}

	/**
	 * normalizes a mam path to the current format
	 *
	 * @param  string $path
	 * @return string
	 */
	public function normalizePath($path) {
		if (strlen($this->configuration->mam_shell_path) > 0) {
			$path = str_replace($this->configuration->mam_shell_path, '', $path);
		}
		$path = ltrim($path, '/\\');
		return $path;
	}

	/**
	 * move a file to its target location
	 *
	 * @param string $mamId
	 * @param string $filepath
	 * @param string $filename
	 * @return void
	 */
	public function moveFile($mamId, $filepath, $filename) {
		$fileObject = $this->getFileObject($mamId);
		if ($fileObject !== NULL) {
			$oldFilePath = rtrim($this->configuration->base_path, '/') . $fileObject->getIdentifier();
			$newFilePath = $filepath . $filename;
			$storagePath = str_replace($this->configuration->base_path, '', $filepath);

			if (!$this->fileExists($oldFilePath)) {
				return;
			}

			if ($oldFilePath !== $newFilePath) {
				if (!is_dir($filepath)) {
					mkdir($filepath, 0777, TRUE);
				}
				$folder = $this->resourceFactory->getObjectFromCombinedIdentifier($this->resourceStorage->getUid() . ':/' . $storagePath);
				$this->resourceStorage->moveFile($fileObject, $folder, $filename);

				$this->cleanupEmptyFoldersInRootline(dirname($oldFilePath));
			}
		}
	}

	public function cleanupEmptyFoldersInRootline($path) {
		$absolutePath = realpath($path);
		if (substr($path, 0, 1) === '/' || strlen($path) < 1) {
			// something fishy! abort!
			return;
		}
		$files = array_diff(scandir($absolutePath), array('.', '..'));

		if (count($files) === 0) {
			rmdir($absolutePath);
			$this->cleanupEmptyFoldersInRootline(dirname($path));
		}
	}

	/**
	 * create a folder on the filesystem
	 *
	 * @param  string $path
	 * @return void
	 */
	public function createFolder($path) {
		$path = PATH_site . $this->normalizePath($path);
		mkdir(dirname($path), 0777, TRUE);
	}

	/**
	 * check if a file exists on the filesystem
	 *
	 * @param  string $path
	 * @return boolean
	 */
	public function fileExists($path) {
		return file_exists($path);
	}
}

?>