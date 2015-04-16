<?php
namespace Crossmedia\FalMam\Tests\Unit\Functional\Repository;

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Functional test for the DataHandler
 */
class MamClientTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

	/** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface The object manager */
	protected $objectManager;

	protected $testExtensionsToLoad = array('typo3conf/ext/fal_mam');

	public function setUp() {
		parent::setUp();
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->client = $this->objectManager->get('\Crossmedia\FalMam\Service\MamClient');

		$this->client->setSessionId('402343_UserManagement_userIdKey_1_00ecd737c5ede6411e4d4d8b966cec51');
		$this->client->setBaseUrl('http://wanzl.4allportal.net/wanzl-dam/');
		$this->client->setConfigHash('5b1679e7aaabdd7a36f6a1cd220b51f4');
		$this->client->setConnectorName('typo3_pap_connector');
	}

	/**
	 * Test getting a configuration hash
	 *
	 * @test
	 * @return void
	 */
	public function getConnectorConfig() {
		$configuration = $this->client->getConnectorConfig('typo3_pap_connector');
		$this->assertEquals($configuration['config_hash'], '5b1679e7aaabdd7a36f6a1cd220b51f4');
	}

	/**
	 * Test getting a configuration hash
	 *
	 * @test
	 * @return void
	 */
	public function getEvents() {
		$events = $this->client->getEvents(0);
		$this->assertTrue(count($events) > 0);
		$event = current($events);
		$this->assertEquals(array_keys($event), array(
			'id',
			'create_time',
			'object_id',
			'object_type',
			'field_name',
			'event_type')
		);
	}

	/**
	 * @test
	 * @return void
	 */
	public function getBeans() {
		$events = $this->client->getEvents(0);
		$this->assertTrue(count($events) > 0);
		$event = current($events);
		$beans = $this->client->getBeans($event['object_id']);
		var_dump($beans);
		// $this->assertTrue(is_string($derivate));
	}

	// /**
	//  * @test
	//  * @return void
	//  */
	// public function getDerivate() {
	// 	$events = $this->client->getEvents(0);
	// 	$this->assertTrue(count($events) > 0);
	// 	$event = current($events);
	// 	$derivate = $this->client->getDerivate($event['object_id']);
	// 	$this->assertTrue(is_string($derivate));
	// }

}