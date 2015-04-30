<?php
namespace Crossmedia\FalMam\Service;


class FileHandler {

	/**
	 * @var array
	 */
	protected $configuration;

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
	}

	public function moveFile($from, $to) {

	}

	public function deleteFile($filename) {

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
}

?>