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
	 * @var \TYPO3\CMS\Core\Resource\ResourceStorage
	 */
	protected $resourceStorage;

	public function __construct() {
		if(isset($GLOBALS['TYPO3_CONF_VARS']["EXT"]["extConf"]['fal_mam'])) {
			$configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']["EXT"]["extConf"]['fal_mam']);
			$this->configuration = $configuration['fal_mam.'];
		}
	}

	public function updateFile($filename, $content) {
		$path = PATH_site . $this->normalizePath($filename);
		mkdir(dirname($path), 0777, TRUE);
		file_put_contents($path, $content);

		// call hook after updating a file
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXT']['fal_mam']['Service/FileHandler.php']['fileUpdated'])) {
			$params = array(
				'path' => $path
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXT']['fal_mam']['Service/FileHandler.php']['fileUpdated'] as $reference) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($reference, $params, $this);
			}
		}
	}

	public function moveFile($from, $to) {

	}

	public function deleteFile($filename) {
		$resourceStorage = $this->getResourceStorage();
		var_dump($filename);
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
}

?>