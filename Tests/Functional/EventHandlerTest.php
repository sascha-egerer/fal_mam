<?php
namespace Crossmedia\FalMam\Tests\Unit\Functional\Repository;

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Functional test for the DataHandler
 */
class EventHandlerTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

	/** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface The object manager */
	protected $objectManager;

	protected $coreExtensionsToLoad = array('scheduler');

	protected $testExtensionsToLoad = array('typo3conf/ext/fal_mam');


	/**
	 * When new events are returned by the getEvents method the state needs
	 * to be updated to the last returned event id
	 *
	 * @test
	 * @return void
	 */
	public function lastEventIdIsSavedToState() {
		$client = $this->getMock('\Crossmedia\FalMam\Service\MamClient');
		$state = $this->getMock('\Crossmedia\FalMam\Task\EventHandlerState');
		$eventHandler = $this->getMock('\Crossmedia\FalMam\Task\EventHandler', array('saveEvents'));
		$eventHandler->injectClient($client);
		$eventHandler->injectState($state);

		$state->expects($this->once())->method('getConfigHash')->will($this->returnValue('foo'));
		$client->expects($this->exactly(2))
			->method('getEvents')
			->will($this->onConsecutiveCalls(
				array(
					array(
						'object_id' => 'data_20150416111838_37E3DD68599BAD01C567420BE95FB3F7',
						'event_type' => 2,
						'object_type' => 0
					)
				),
				array()
			));

		$eventHandler->expects($this->once())
					->method('saveEvents')
					->with($this->equalTo(
			array(
				"tx_falmam_event_queue" => array(
					'NEW' => array(
						"pid" => NULL,
						"event_id" => NULL,
						"event_type" => 'create',
						"target" => 'metadata',
						"object_id" => 'data_20150416111838_37E3DD68599BAD01C567420BE95FB3F7',
						"status" => 'NEW'
					)
				)
			)
		));
		$eventHandler->execute();
	}

}