<?php
namespace Crossmedia\FalMam\Task;

use Crossmedia\FalMam\Service\MamClient;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

class EventHandlerState implements \TYPO3\CMS\Core\SingletonInterface{

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
	 * @var string
	 */
	protected $connectorName;

	/**
	 * @var string
	 */
	protected $configHash;

	/**
	 * @var integer
	 */
	protected $eventId = 0;

	/**
	 * @var integer
	 */
	protected $syncId = 0;

	/**
	 * @var integer
	 */
	protected $syncOffset = 0;

	/**
	 * @var integer
	 */
	protected $uid = 'NEW';

	/**
	 * @var integer
	 */
	protected $pid = 0;

	public function __construct() {
		$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->client = $objectManager->get('\Crossmedia\FalMam\Service\MamClient');
		$this->dataHandler = $objectManager->get('\TYPO3\CMS\Core\DataHandling\DataHandler');

		if(isset($GLOBALS['TYPO3_CONF_VARS']["EXT"]["extConf"]['fal_mam'])) {
			$configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']["EXT"]["extConf"]['fal_mam']);
			$configuration = $configuration['fal_mam.'];
			$this->connectorName = $configuration['connector_name'];
			$this->pid = $configuration['storage_pid'];
		}

		if (!$this->load()) {
			$this->save();
		}
	}

	public function load() {
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_falmam_state',
			'connector_name = "' . $this->connectorName . '"'
		);

 		if (empty($rows)) {
 			return false;
 		}
 		$row = current($rows);

 		$this->connectorName = $row['connector_name'];
 		$this->configHash = $row['config_hash'];
 		$this->eventId = $row['event_id'];
 		$this->syncId = $row['sync_id'];
 		$this->syncOffset = $row['sync_offset'];
 		$this->uid = $row['uid'];

 		return true;
	}

	/**
	 * save the state of this connector in the database
	 *
	 * @return void
	 */
	public function save() {
		$data = array();
		$data['tx_falmam_state'][$this->uid] = array(
			'connector_name' => $this->connectorName,
			'config_hash' => $this->configHash,
			'event_id' => $this->eventId,
			'sync_id' => $this->syncId,
			'sync_offset' => $this->syncOffset
		);

 		if ($this->uid == 'NEW') {
			$data['tx_falmam_state'][$this->uid]['pid'] = $this->pid;
 		}

		$this->dataHandler->start($data, array());
		$result = $this->dataHandler->process_datamap();

		if ($this->uid == 'NEW') {
			$this->uid = $this->dataHandler->substNEWwithIDs[$this->uid];
		}
	}

	/**
	 * @param string $connectorName
	 */
	public function setConnectorName($connectorName) {
		$this->connectorName = $connectorName;
	}

	/**
	 * @return string
	 */
	public function getConnectorName() {
		return $this->connectorName;
	}

	/**
	 * @param string $configHash
	 */
	public function setConfigHash($configHash) {
		$this->configHash = $configHash;
	}

	/**
	 * @return string
	 */
	public function getConfigHash() {
		return $this->configHash;
	}

	/**
	 * @param integer $eventId
	 */
	public function setEventId($eventId) {
		$this->eventId = $eventId;
	}

	/**
	 * @return integer
	 */
	public function getEventId() {
		return $this->eventId;
	}

	/**
	 * @param integer $syncId
	 */
	public function setSyncId($syncId) {
		$this->syncId = $syncId;
	}

	/**
	 * @return integer
	 */
	public function getSyncId() {
		return $this->syncId;
	}

	/**
	 * @param integer $syncOffset
	 */
	public function setSyncOffset($syncOffset) {
		$this->syncOffset = $syncOffset;
	}

	/**
	 * @return integer
	 */
	public function getSyncOffset() {
		return $this->syncOffset;
	}

	/**
	 */
	public function increaseSyncOffset() {
		$this->syncOffset = $this->syncOffset + 1000;
	}
}

?>