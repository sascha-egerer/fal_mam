<?php
namespace Crossmedia\FalMam\Service;


class FileHandler {

	/**
	 * @var array
	 */
	protected $configuration;

	/**
	 * @var \TYPO3\CMS\Core\Resource\StorageRepository
	 * @inject
	 */
	protected $storageRepository;

	/**
	 * @var \Crossmedia\FalMam\Service\DbHandler
	 * @inject
	 */
	protected $dbHandler;

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceStorage
	 */
	protected $resourceStorage;

	/**
	 * @param \TYPO3\CMS\Core\Resource\ResourceStorage
	 * @return void
	 */
	public function injectResourceStorage(\TYPO3\CMS\Core\Resource\ResourceStorage $resourceStorage) {
		$this->resourceStorage = $resourceStorage;
	}

	/**
	 * @param \Crossmedia\FalMam\Service\DbHandler $dbHandler
	 * @return void
	 */
	public function injectDbHandler(\Crossmedia\FalMam\Service\DbHandler $dbHandler) {
		$this->dbHandler = $dbHandler;
	}

	public function __construct() {
		if(isset($GLOBALS['TYPO3_CONF_VARS']["EXT"]["extConf"]['fal_mam'])) {
			$configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']["EXT"]["extConf"]['fal_mam']);
			$this->configuration = $configuration['fal_mam.'];
		}
	}

	// handled directly by the mamClient to prevent memory issues because of big files
	// public function updateFile($filename, $content) {
	// 	$path = PATH_site . $this->normalizePath($filename);
	// 	mkdir(dirname($path), 0777, TRUE);
	// 	file_put_contents($path, $content);

	// 	// call hook after updating a file
	// 	if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXT']['fal_mam']['Service/FileHandler.php']['fileUpdated'])) {
	// 		$params = array(
	// 			'path' => $path
	// 		);
	// 		foreach ($GLOBALS['TYPO3_CONF_VARS']['EXT']['fal_mam']['Service/FileHandler.php']['fileUpdated'] as $reference) {
	// 			\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($reference, $params, $this);
	// 		}
	// 	}
	// }

	/**
	 * move a file to its target location
	 *
	 * @param string $mamId
	 * @param string $filename
	 * @param string $path
	 * @return void
	 */
	public function moveFile($mamId, $filename, $path) {
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'*',
			'sys_file',
			'tx_falmam_id = "' . $mamId . '"'
		);
		if ($row['identifier'] !== $path . $filename) {
			$fileObject = $this->dbHandler->getFileObject($mamId);
			$this->getResourceStorage()->moveFile($file, $path, $filename);
		}
	}

	public function createFolder($path) {
		$path = PATH_site . $this->normalizePath($path);
		mkdir(dirname($path), 0777, TRUE);
	}

	public function normalizePath($path) {
		if (strlen($this->configuration['mam_shell_path']) > 0) {
			$path = str_replace($this->configuration['mam_shell_path'], '', $path);
		}
		$path = ltrim($path, '/\\');
		return $path;
	}

	public function getResourceStorage() {
		if ($this->resourceStorage === NULL) {
			$this->resourceStorage =  current($this->storageRepository->findByStorageType('MAM'));
		}
		return $this->resourceStorage;
	}

	public function fileExists($path) {
		return file_exists($path);
	}
}

?>