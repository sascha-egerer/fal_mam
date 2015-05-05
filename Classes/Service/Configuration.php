<?php
namespace Crossmedia\FalMam\Service;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\ResourceFactory;


/**
 * This class represents a central access to the extension and mapping configuration
 */
class Configuration implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var string
	 */
	public $base_url;

	/**
	 * @var string
	 */
	public $connector_name;

	/**
	 * @var string
	 */
	public $username;

	/**
	 * @var string
	 */
	public $password;

	/**
	 * @var string
	 */
	public $customer;

	/**
	 * @var string
	 */
	public $base_path;

	/**
	 * @var string
	 */
	public $storage_pid;

	/**
	 * @var string
	 */
	public $mam_shell_path;

	/**
	 * @var array
	 */
	public $mapping;

	public function __construct() {
		if(isset($GLOBALS['TYPO3_CONF_VARS']["EXT"]["extConf"]['fal_mam'])) {
			$configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']["EXT"]["extConf"]['fal_mam']);
			$this->configuration = $configuration['fal_mam.'];
			foreach ($this->configuration as $key => $value) {
				$this->$key = $value;
			}
		}

		$mapping = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_falmam_mapping',
			'connector_name = "' . $this->connector_name . '"',
			'',
			'',
			'',
			'mam_field'
		);
		foreach ($mapping as $key => $value) {
			$valueMap = array();
			foreach (explode("\n", $value['value_map']) as $row) {
				$parts = explode(':', $row);
				$valueMap[trim($parts[0])] = trim($parts[1]);
			}
			$value['value_map'] = $valueMap;
			$this->mapping[$key] = $value;
		}
	}
}

?>