<?php
namespace Crossmedia\FalMam\Tests\Unit\Functional;

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Functional test for the DataHandler
 */
class MamClientTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

	/** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface The object manager */
	protected $objectManager;

	protected $testExtensionsToLoad = array('typo3conf/ext/fal_mam');

	/**
	 * @test
	 * @expectedException \Crossmedia\FalMam\Error\MamApiException
	 * @return void
	 */
	public function wrongLoginShouldThrowException() {
		$client = $this->getMock('\Crossmedia\FalMam\Service\MamClient', array('doGetRequest'));

		$client->expects($this->once())
			->method('doGetRequest')
			->will($this->returnValue('{
			"info": {
			"start_execution": "2015-04-21 10:05:35",
				"microseconds": "28669766"
			},
			"code": -1,
			"message": "Login not possible"
		}'));
		// $client->setBaseUrl('http://wanzl.4allportal.net/wanzl-dam/');
		// $client->setUsername('foo');
		// $client->setPassword('bar');
		$client->login();
	}

	/**
	 * @test
	 * @return void
	 */
	public function loginReturnsASessionId() {
		$client = $this->getMock('\Crossmedia\FalMam\Service\MamClient', array('doGetRequest', 'logout'));

		$client->expects($this->once())
			->method('doGetRequest')
			->will($this->returnValue('{
			"result": {
				"sessionID": "409798_UserManagement_userIdKey_1_8802ac89f39b3083e3e32b41951fc882",
				"serverTimeStamp": {
					"year": 2015,
					"month": 3,
					"dayOfMonth": 21,
					"hourOfDay": 10,
					"minute": 23,
					"second": 4
				}
			},
			"info": {
				"start_execution": "2015-04-21 10:23:04",
				"microseconds": "73105888"
			},
			"code": 0
		}'));
		// $client->setBaseUrl('http://wanzl.4allportal.net/wanzl-dam/');
		// $client->setUsername('foo');
		// $client->setPassword('bar');
		$client->login();

		$this->assertEquals($client->getSessionId(), '409798_UserManagement_userIdKey_1_8802ac89f39b3083e3e32b41951fc882');
	}

	/**
	 * @test
	 * @group focus
	 * @return void
	 */
	public function clientAutomaticallyLogsInAndFetchesConfigHash() {
		$client = $this->getMock('\Crossmedia\FalMam\Service\MamClient', array('doGetRequest', 'logout'));

		$GLOBALS['TYPO3_CONF_VARS']["EXT"]["extConf"]['fal_mam'] = serialize(array(
			'fal_mam.' => array(
				'base_url' => 'http://foobar.com/',
				'connector_name' => 'typo3_pap_connector',
				'username' => 'foo',
				'password' => 'bar',
				'customer' => ''
			)
		));

		$client->expects($this->exactly(2))
			->method('doGetRequest')
			->will($this->returnValueMap(array(
				array(
					// Login call
					'http://foobar.com/rest?service=PAPRemoteService&method=login&parameter=["foo","bar",""]',
					'{
						"result": {
							"sessionID": "409798_UserManagement_userIdKey_1_8802ac89f39b3083e3e32b41951fc882",
							"serverTimeStamp": {
								"year": 2015,
								"month": 3,
								"dayOfMonth": 21,
								"hourOfDay": 10,
								"minute": 23,
								"second": 4
							}
						},
						"info": {
							"start_execution": "2015-04-21 10:23:04",
							"microseconds": "73105888"
						},
						"code": 0
					}',
				),
				array(
					// get configuration call
					'http://foobar.com/rest?service=PAPRemoteService&method=getConnectorConfig&parameter=["409798_UserManagement_userIdKey_1_8802ac89f39b3083e3e32b41951fc882","typo3_pap_connector"]',
					'{
						"result": {
							"connector_name": "typo3_pap_connector",
							"conditions": [
								"data_subsubtype = \'image/tiff\'"
							],
							"eventTypeConditions": [ ],
							"view_name": "connector_typo3",
							"derivates": [
								"web",
								"print"
							],
							"userId": "1",
							"config_hash": "8af655c834c49c5abee0cd267d5d12f8"
						},
						"module_name": "mam",
						"info": {
							"start_execution": "2015-04-21 10:27:13",
							"microseconds": "85724149"
						},
						"code": 0
					}'
				),
				array(
					// logout on destruct
					'http://foobar.com/rest?service=PAPRemoteService&method=logout&parameter=["409798_UserManagement_userIdKey_1_8802ac89f39b3083e3e32b41951fc882"]',
					'{
						"module_name": "mam",
						"info": {
							"start_execution": "2015-04-21 10:27:13",
							"microseconds": "85724149"
						},
						"code": 0
					}'
				)
			)
		));
		$client->initialize();

		// $client->setBaseUrl('http://wanzl.4allportal.net/wanzl-dam/');
		// $client->setUsername('foo');
		// $client->setPassword('bar');

		$this->assertEquals('foo', $client->getUsername());
		$this->assertEquals('bar', $client->getPassword());
		$this->assertEquals('409798_UserManagement_userIdKey_1_8802ac89f39b3083e3e32b41951fc882', $client->getSessionId());
		$this->assertEquals('8af655c834c49c5abee0cd267d5d12f8', $client->getConfigHash());
	}

	/**
	 * Test getting a configuration hash
	 *
	 * @test
	 * @return void
	 */
	public function getConnectorConfig() {
		$client = $this->getMock('\Crossmedia\FalMam\Service\MamClient', array('doGetRequest'));

		$client->expects($this->once())
			->method('doGetRequest')
			->will($this->returnValue('{
				"result": {
					"connector_name": "typo3_pap_connector",
					"conditions": [
						"data_subsubtype = \'image/tiff\'"
					],
					"eventTypeConditions": [ ],
					"view_name": "connector_typo3",
					"derivates": [
						"web",
						"print"
					],
					"userId": "1",
					"config_hash": "8af655c834c49c5abee0cd267d5d12f8"
				},
				"module_name": "mam",
				"info": {
					"start_execution": "2015-04-21 10:27:13",
					"microseconds": "85724149"
				},
				"code": 0
		}'));
		// $client->setBaseUrl('http://wanzl.4allportal.net/wanzl-dam/');
		// $client->setSessionId('409798_UserManagement_userIdKey_1_8802ac89f39b3083e3e32b41951fc882');

		$configuration = $client->getConnectorConfig('typo3_pap_connector');
		$this->assertEquals($configuration['config_hash'], '8af655c834c49c5abee0cd267d5d12f8');
	}

	/**
	 * @test
	 * @return void
	 */
	public function synchronize() {
		$client = $this->getMock('\Crossmedia\FalMam\Service\MamClient', array('doGetRequest'));

		$client->expects($this->once())
			->method('doGetRequest')
			->will($this->returnValue('{
			"result": {
				"event_id": 63,
				"ids": [
					"125",
					"126ac917-879c-48e3-aac9-17879c28e31d"
				]
			},
			"module_name": "contact",
			"info": {
				"start_execution": "2015-04-21 11:52:50",
				"microseconds": "1391695"
			},
			"code": 0
		}'));

		// $client->setBaseUrl('http://wanzl.4allportal.net/wanzl-dam/');
		// $client->setConnectorName('contact_pap_connector');
		// $client->setConfigHash('8af655c834c49c5abee0cd267d5d12f8');
		// $client->setSessionId('409887_UserManagement_userIdKey_1_47efd20c4ae6fe660fce85fb2b3318f3');

		$result = $client->synchronize(0);
		$this->assertCount(2, $result['ids']);
		$this->assertEquals(63, $result['event_id']);
	}

	/**
	 * @test
	 * @return void
	 */
	public function getEvents() {
		$client = $this->getMock('\Crossmedia\FalMam\Service\MamClient', array('doGetRequest'));

		$client->expects($this->once())
			->method('doGetRequest')
			->will($this->returnValue('{
				"result": [
					{
						"id": 333,
						"create_time": "2015/04/13 16:56:19",
						"object_id": "data_20150407145531_80E9C54EF77FCB3E9E740631F0DAE0DA",
						"object_type": 0,
						"field_name": "data_name",
						"event_type": 1
					},
					{
						"id": 334,
						"create_time": "2015/04/13 16:56:19",
						"object_id": "data_20150407145531_80E9C54EF77FCB3E9E740631F0DAE0DA",
						"object_type": 1,
						"field_name": "web",
						"event_type": 1
					}
				],
				"module_name": "mam",
				"info": {
					"start_execution": "2015-04-21 10:32:56",
					"microseconds": "2239846"
				},
				"code": 0
		}'));

		// $client->setBaseUrl('http://wanzl.4allportal.net/wanzl-dam/');
		// $client->setConnectorName('typo3_pap_connector');
		// $client->setConfigHash('8af655c834c49c5abee0cd267d5d12f8');
		// $client->setSessionId('409798_UserManagement_userIdKey_1_8802ac89f39b3083e3e32b41951fc882');

		$events = $client->getEvents(0);
		$this->assertTrue(count($events) > 0);
		$event = current($events);
		$this->assertEquals($event['id'], 333);
		$this->assertEquals($event['create_time'], '2015/04/13 16:56:19');
		$this->assertEquals($event['object_id'], 'data_20150407145531_80E9C54EF77FCB3E9E740631F0DAE0DA');
		$this->assertEquals($event['object_type'], 0);
		$this->assertEquals($event['field_name'], 'data_name');
		$this->assertEquals($event['event_type'], 1);
	}

	/**
	 * @test
	 * @return void
	 */
	public function getBeans() {
		$client = $this->getMock('\Crossmedia\FalMam\Service\MamClient', array('doGetRequest'));

		$client->expects($this->once())
			->method('doGetRequest')
			->will($this->returnValue('{
				"result": [{
					"id": "data_20150416111838_37E3DD68599BAD01C567420BE95FB3F7",
					"module_name": "contact",
					"mod_time": "2015/03/26 13:59:33",
					"type": "default",
					"properties": {
						"data_preview_sequence_count": {
							"value": "0"
						},
						"data_modification_date": {
							"value": "16.04.2015 11:19:13"
						},
						"data_name": {
							"value": "colorsmoke4.1.tif"
						},
						"data_id": {
							"value": "data_20150416111838_37E3DD68599BAD01C567420BE95FB3F7"
						},
						"data_subsubtype": {
							"value": "image/tiff"
						},
						"data_shellpath": {
							"value": "/usr/local/mam/wanzl/data/PAP-Test/Freigabestatus/"
						}
					}
				}],
				"relations": {},
				"info": {
					"start_execution": "2015-03-27 12:44:48",
					"microseconds": "1199461107"
				},
				"type": "default",
				"code": 0
		}'));

		$beans = $client->getBeans('data_20150416111838_37E3DD68599BAD01C567420BE95FB3F7');

		$this->assertCount(1, $beans);
		$this->assertEquals($beans[0]['id'], 'data_20150416111838_37E3DD68599BAD01C567420BE95FB3F7');
		$this->assertEquals($beans[0]['properties']['data_name'], 'colorsmoke4.1.tif');
	}

	/**
	 * @test
	 * @return void
	 */
	public function getDerivate() {
		$client = $this->getMock('\Crossmedia\FalMam\Service\MamClient', array('doGetRequest'));

		$client->expects($this->once())
			->method('doGetRequest')
			->will($this->returnValue('...file-data...'));

		$derivate = $client->getDerivate($event['object_id']);
		$this->assertEquals('...file-data...', $derivate);
	}

}