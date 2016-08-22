<?php
namespace Crossmedia\FalMam\Service;

class Logger {

	/**
	 * @var Raven_Client
	 */
	protected $client;

	/**
	 * @var integer
	 */
	protected $logLevel = 3;

	public function __construct($autologin = TRUE) {
		require_once(PATH_site . 'typo3conf/ext/fal_mam/Resources/PHP/sentry-php/lib/Raven/Autoloader.php');
		\Raven_Autoloader::register();
		$this->client = new \Raven_Client('http://f6e823b143eb465cb5b741d62b9007b0:b5534f5496434a6f9c12795a283509c1@docker.mia3.com:9000/2');
		if(isset($GLOBALS['TYPO3_CONF_VARS']["EXT"]["extConf"]['fal_mam'])) {
			$configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']["EXT"]["extConf"]['fal_mam']);
			$this->client->user_context($configuration);
		}
	}

	public function debug($message, $context = array()) {
		if ($this->logLevel < 3) {
			return;
		}

		$this->client->captureMessage($message, array(),
			array(
				'level' => 'debug',
				'extra' => $context
			)
		);
	}

	public function info($message, $context = array()) {
		if ($this->logLevel < 2) {
			return;
		}
		$this->client->captureMessage($message, array(),
			array(
				'level' => 'info',
				'extra' => $context
			)
		);
	}

	public function warning($message, $context = array()) {
		if ($this->logLevel < 1) {
			return;
		}
		$this->client->captureMessage($message, array(),
			array(
				'level' => 'warning',
				'extra' => $context
			)
		);
	}

	public function error($message, $context = array()) {
		if ($this->logLevel < 0) {
			return;
		}
		$this->client->captureMessage($message, array(),
			array(
				'level' => 'error',
				'extra' => $context
			)
		);
	}

	public function fatal($message, $context = array()) {
		if ($this->logLevel < 0) {
			return;
		}
		$this->client->captureMessage($message, array(),
			array(
				'level' => 'fatal',
				'extra' => $context
			)
		);
	}
}