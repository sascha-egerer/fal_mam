<?php
namespace Crossmedia\FalMam\Service;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Arno Dudek <webmaster@adgrafik.at>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


class MamClient {

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
	protected $sessionId;

	/**
	 * @var string
	 */
	protected $configHash;

	/**
	 * @var string
	 */
	protected $connectorName;

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
	 * Get configuration for a connector from MAM
	 *
	 * @apiparam session_id - Usersession
	 * @apiparam connector_name - Name des Connectors
	 *
	 * @param string $connectorName
	 * @return array $configuration
	 */
	public function getConnectorConfig($connectorName) {
		$configuration = $this->getRequest('getConnectorConfig', array(
			$this->sessionId,
			$connectorName
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
	 * @param string $connectorName
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
	 * @param integer $objectId
	 * @return array $beans
	 */
	public function getBeans($objectId) {
		$beans = $this->getRequest('getBeans', array(
			$this->sessionId,
			$this->connectorName,
			array($objectId)
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
		return \Requests::get($uri)->body;
	}

	public function getRequest($method, $parameter) {
		$uri = $this->restUrl . '&method=' . $method . '&parameter=' . json_encode($parameter);
		$request = \Requests::get($uri);
		$result = json_decode($request->body, TRUE);
		if ($result['code'] !== 0) {
			var_dump($uri, $request->body);
			//todo error handling
		}
		return $result['result'];
	}
}

?>