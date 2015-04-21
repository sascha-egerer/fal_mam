<?php
namespace Crossmedia\FalMam\Service;

use Crossmedia\FalMam\Error\MamApiException;


class MamClient implements \TYPO3\CMS\Core\SingletonInterface{

	/**
	 * @var string
	 */
	protected $baseUrl;

	/**
	 * @var string
	 */
	protected $restUrl;

	/**
	 * @var string
	 */
	protected $dataUrl;

	/**
	 * @var string
	 */
	protected $connectorName;

	/**
	 * @var string
	 */
	protected $username;

	/**
	 * @var string
	 */
	protected $password;

	/**
	 * @var string
	 */
	protected $customer;

	/**
	 * @var string
	 */
	protected $sessionId;

	/**
	 * @var string
	 */
	protected $configHash;

	public function __construct($autologin = TRUE) {
		if ($autologin === TRUE) {
			$this->initialize();
		}
	}

	public function __destruct() {
		$this->logout();
	}

	public function initialize() {
		 if(isset($GLOBALS['TYPO3_CONF_VARS']["EXT"]["extConf"]['fal_mam'])) {
			$configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']["EXT"]["extConf"]['fal_mam']);
			$configuration = $configuration['fal_mam.'];

			$this->setBaseUrl($configuration['base_url']);
			$this->setConnectorName($configuration['connector_name']);
			$this->username = $configuration['username'];
			$this->password = $configuration['password'];
			$this->customer = $configuration['customer'];

			$this->login();

			$connectorConfig = $this->getConnectorConfig();
			$this->configHash = $connectorConfig['config_hash'];
		}
	}

	/**
	 * @param string $baseUrl
	 */
	public function setBaseUrl($baseUrl) {
		$this->baseUrl = $baseUrl;
		$this->restUrl = $baseUrl . 'rest?service=PAPRemoteService';
		$this->dataUrl = $baseUrl . 'dataservice';
	}

	/**
	 * @return string
	 */
	public function getBaseUrl() {
		return $this->baseUrl;
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
	 * @param string $sessionId
	 */
	public function setSessionId($sessionId) {
		$this->sessionId = $sessionId;
	}

	/**
	 * @return string
	 */
	public function getSessionId() {
		return $this->sessionId;
	}

	/**
	 * @param string $username
	 */
	public function setUsername($username) {
		$this->username = $username;
	}

	/**
	 * @return string
	 */
	public function getUsername() {
		return $this->username;
	}

	/**
	 * @param string $password
	 */
	public function setPassword($password) {
		$this->password = $password;
	}

	/**
	 * @return string
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 * @param string $customer
	 */
	public function setCustomer($customer) {
		$this->customer = $customer;
	}

	/**
	 * @return string
	 */
	public function getCustomer() {
		return $this->customer;
	}

	public function login() {
		$response = $this->getRequest('login', array(
			$this->username,
			$this->password,
			$this->customer
		));
		if (isset($response['sessionID'])) {
			$this->sessionId = $response['sessionID'];
			return TRUE;
		}
		return FALSE;
	}

	public function logout() {
		if ($this->sessionId !== NULL) {
			$this->getRequest('logout', array(
				$this->sessionId
			));
			$this->sessionId = NULL;
		}
	}

	/**
	 * Get configuration for a connector from MAM
	 *
	 * @apiparam session_id - Usersession
	 * @apiparam connector_name - Name des Connectors
	 *
	 * @param string $connectorName
	 * @return array $configuration
	 */
	public function getConnectorConfig($connectorName = NULL) {
		$configuration = $this->getRequest('getConnectorConfig', array(
			$this->sessionId,
			($connectorName ? $connectorName : $this->connectorName)
		));
		return $configuration;
	}

	/**
	 * Get events from MAM starting from a specific event id
	 *
	 * Dieser Service liefert nicht alle IDs aus maximal 1000.
	 * Es sind alle Events ausgeliefert, sobald 0 Werte zurückgegeben werden.
	 *
	 * @apiparam session_id - Usersession
	 * @apiparam connector_name - Name des Connectors
	 * @apiparam event_id - Die Id des ersten Events
	 * @apiparam config_hash - MD5. Hash der Konfiguration, um Änderungen an der Konfiguration zu erkennen.
	 *
	 * @param  integer $eventId
	 * @return array $events
	 *
	 * id - event id
	 * create_time - time of creation
	 * object_id - id of the relevant object
	 * object_type - type of the relevant object (0 = bean, 1 = derivate, 2 = fileaccess)
	 * field_name - derivate type
	 * event_type - type of event (0 = delete, 1 = update, 2 = create)
	 */
	public function getEvents($eventId) {
		$events = $this->getRequest('getEvents', array(
			$this->sessionId,
			$this->connectorName,
			$eventId,
			$this->configHash
		));
		return $events;
	}

	/**
	 * Start a synchronization
	 *
	 * Dieser Service liefert nicht alle IDs aus maximal 1000.
	 * Es sind alle Events ausgeliefert, sobald 0 Werte zurückgegeben werden.
	 *
	 * @apiparam session_id - Usersession
	 * @apiparam connector_name - Name des Connectors
	 * @apiparam event_id - Die Id des ersten Events
	 * @apiparam offset - offset der IDs
	 *
	 * @param integer $eventId
	 * @param integer $offset
	 * @param string $connectorName
	 * @return array $events
	 */
	public function synchronize($eventId, $offset = 0, $connectorName = NULL) {
		$result = $this->getRequest('synchronize', array(
			$this->sessionId,
			$connectorName ? $connectorName : $this->connectorName,
			$eventId,
			$offset
		));
		return $result;
	}

	/**
	 * Get events from MAM starting from a specific event id
	 *
	 * Dieser Service liefert nicht alle IDs aus maximal 1000.
	 * Es sind alle Events ausgeliefert, sobald 0 Werte zurückgegeben werden.
	 *
	 * @apiparam session_id - Usersession
	 * @apiparam connector_name - Name des Connectors
	 * @apiparam ids - Die Ids des Beans
	 *
	 * @param string $connectorName
	 * @param integer|array $objectIds
	 * @param string $connectorName
	 * @return array $beans
	 */
	public function getBeans($objectIds, $connectorName = NULL) {
		if (!is_array($objectIds)) {
			$objectIds = array($objectIds);
		}

		$beans = $this->getRequest('getBeans', array(
			$this->sessionId,
			$connectorName ? $connectorName : $this->connectorName,
			$objectIds
		));
		return $beans;
	}


	/**
	 * Fetches a specific derivate from MAM
	 *
	 * @apiparam connector_name - Name des Connectors
	 * @apiparam id - Id des Datensatzes für das jeweilige Derivat
	 * @apiparam derivate - web, print etc.
	 *
	 * @param string $objectId id of the object to get a derivate for
	 * @return ???
	 */
	public function getDerivate($objectId, $usage = 'Original') {
		$query = array(
			'session' => $this->sessionId,
			'apptype' => 'MAM',
			'clientType' => 'Web',
			'usage' => $usage,
			'id' => $objectId
		);
		$uri = $this->dataUrl . '?' . http_build_query($query);
		return $this->doGetRequest($uri);
	}

	public function getRequest($method, $parameter) {
		$uri = $this->restUrl . '&method=' . $method . '&parameter=' . json_encode($parameter);
		$response = $this->doGetRequest($uri);
		$result = json_decode($response, TRUE);
		if (!isset($result['code']) || $result['code'] !== 0) {
			$message = isset($result['message']) ? $result['message'] : 'unkown error';
			throw new MamApiException($message);
		}
		return $result['result'];
	}

	public function doGetRequest($uri) {
		return \Requests::get($uri)->body;
	}
}

?>