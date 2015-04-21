<?php
namespace Crossmedia\FalMam\Task;

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
	 * @var \TYPO3\CMS\Frontend\Page\PageRepository
	 * @inject
	 */
	protected $pageRepository;



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

	public function __construct(\Crossmedia\FalMam\Service\MamClient $client, \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler, \TYPO3\CMS\Frontend\Page\PageRepository $pageRepository) {
		$this->client = $client;
		$this->dataHandler = $dataHandler;
		$this->pageRepository = $pageRepository;

		if (!$this->load()) {
			$this->connectorName = $client->getConnectorName();
			$this->save();
		}
	}

	public function load() {
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_falmam_state',
			'connector_name = "' . $this->client->getConnectorName() . '"'
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
			$data['tx_falmam_state'][$this->uid]['pid'] = $this->locateRootPageUid();
 		}

		$this->dataHandler->start($data, array());
		$this->dataHandler->process_datamap();

		if ($this->uid == 'NEW') {
			$this->uid = $this->dataHandler->substNEWwithIDs[$this->uid];
		}
	}

	/**
	 * locate a proper pid of the root page to put state table entries on.
	 * the root page is determined by the pid of the first root sys_template
	 * in the database
	 *
	 * @return int
	 */
	public function locateRootPageUid() {
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'pid',
			'sys_template',
			'root = 1 ' . $this->pageRepository->enableFields('sys_template')
 		);
 		if (count($rows) > 0) {
			return $rows[0]['pid'];
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