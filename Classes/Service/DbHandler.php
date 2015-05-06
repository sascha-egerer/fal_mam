<?php
namespace Crossmedia\FalMam\Service;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\ResourceFactory;


/**
 *
 */
class DbHandler {
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
	 * @var integer
	 */
	protected $reclaimTime = 60;

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceStorage
	 */
	protected $resourceStorage;

	public function __construct() {
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$storageRepository = $objectManager->get('\TYPO3\CMS\Core\Resource\StorageRepository');
		$this->resourceStorage =  current($storageRepository->findByStorageType('MAM'));
	}

	public function createAsset($filename, $filepath, $mamId, $metadata) {
		$path = str_replace($this->configuration->base_path, '', $filepath . $filename);
		$fileObject = ResourceFactory::getInstance()->getObjectFromCombinedIdentifier('1:/' . $path);
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
	}

	public function updateAsset($filename, $filepath, $mamId, $metadata) {
		$fileObject = $this->getFileObject($mamId);

		if ($fileObject === NULL) {
			// false update event -> create!
			return $this->createAsset($filename, $filepath, $mamId, $metadata);
		}

		$path = str_replace($this->configuration->base_path, '', $filepath . $filename);

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
	}

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

	public function claimEventFromQueue() {
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_falmam_event_queue',
			'(status = "NEW" AND skipuntil < ' . time() . ') OR (status = "CLAIMED" AND tstamp < ' . (time() - $reclaimTime) . ')',
			'',
			'event_id',
			'1'
		);
		if (count($rows) > 0) {
			$event = current($rows);
			unset($rows);
			$data['tx_falmam_event_queue'][$event['uid']] = array(
				'status' => 'CLAIMED'
			);

			$this->dataHandler->start($data, array());
			$this->dataHandler->process_datamap();

			$event['start'] = microtime(TRUE);
			return $event;
		}
	}

	public function finnishEvent($event) {
		if (isset($event['start'])) {
			$event['runtime'] = number_format((microtime(TRUE) - $event['start']) * 1000, 2);
		}

		$data['tx_falmam_event_queue'][$event['uid']] = array(
			'status' => 'DONE',
			'runtime' => $event['runtime'],
			'skipuntil' => NULL
		);

		$this->dataHandler->start($data, array());
		$this->dataHandler->process_datamap();
	}

	public function rescheduleEvent($event) {
		$data['tx_falmam_event_queue'][$event['uid']] = array(
			'status' => 'NEW',
			'skipuntil' => time() + 1
		);

		$this->dataHandler->start($data, array());
		$this->dataHandler->process_datamap();
	}

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

		return ResourceFactory::getInstance()->getFileObject($row['uid'], $row);
	}

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
}

?>