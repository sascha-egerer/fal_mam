<?php
namespace Crossmedia\FalMam\Service;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\ResourceFactory;


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

	public function injectDataHandler(DataHandler $dataHandler) {
		$this->dataHandler = $dataHandler;
	}

	public function createAsset($filename, $filepath, $mamid, $metadata) {
		$path = str_replace($this->configuration->base_path, '', $filepath . $filename);
		$fileObject = ResourceFactory::getInstance()->getObjectFromCombinedIdentifier('1:/' . $path);
		$fileObject->_getMetaData();
		$data = array();
		foreach ($this->configuration->mapping as $mamField => $falField) {
			if (isset($metadata[$mamField])) {
				$data[$falField] = $metadata[$mamField];
			}
		}
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_file_metadata', 'file=' . $fileObject->getUid(), $data);

		unset($fileObject, $path, $data);
	}

	public function moveFile($from, $to) {

	}

	public function deleteAsset($mamid) {

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
			'runtime' => $event['runtime']
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
}

?>