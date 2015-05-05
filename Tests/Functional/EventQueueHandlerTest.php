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
	 * If the processing of an event fails it needs to be rescheduled.
	 *
	 * @test
	 * @return void
	 */
	public function failedEventsShouldBeRescheduled() {
		$dbHandler = $this->getMock('\Crossmedia\FalMam\Service\DbHandler');
		$eventQueueHandler = $this->getMock('\Crossmedia\FalMam\Task\EventQueueHandler', array('processEvent'));
		$eventQueueHandler->injectDbHandler($dbHandler);

		// $state->expects($this->once())->method('getConfigHash')->will($this->returnValue('foo'));
		$dbHandler->expects($this->exactly(2))
			->method('claimEventFromQueue')
			->will($this->onConsecutiveCalls(
				array(
					'object_id' => 'data_20150416111838_37E3DD68599BAD01C567420BE95FB3F7',
					'event_type' => 'create',
					'target' => 'file'
				),
				NULL
			));

		$eventQueueHandler->expects($this->once())
			->method('processEvent')
			->will($this->returnValue(FALSE));

		$dbHandler->expects($this->once())->method('rescheduleEvent');
		$dbHandler->expects($this->never())->method('finnishEvent');

		$eventQueueHandler->execute();
	}

	/**
	 * If the processing of an event fails it needs to be rescheduled.
	 *
	 * @test
	 * @return void
	 */
	public function successfullEventsShouldBeFinnished() {
		$dbHandler = $this->getMock('\Crossmedia\FalMam\Service\DbHandler');
		$eventQueueHandler = $this->getMock('\Crossmedia\FalMam\Task\EventQueueHandler', array('processEvent'));
		$eventQueueHandler->injectDbHandler($dbHandler);

		// $state->expects($this->once())->method('getConfigHash')->will($this->returnValue('foo'));
		$dbHandler->expects($this->exactly(2))
			->method('claimEventFromQueue')
			->will($this->onConsecutiveCalls(
				array(
					'object_id' => 'data_20150416111838_37E3DD68599BAD01C567420BE95FB3F7',
					'event_type' => 'create',
					'target' => 'file'
				),
				NULL
			));

		$eventQueueHandler->expects($this->once())
			->method('processEvent')
			->will($this->returnValue(TRUE));

		$dbHandler->expects($this->once())->method('finnishEvent');
		$dbHandler->expects($this->never())->method('rescheduleEvent');

		$eventQueueHandler->execute();
	}

	public function assertEventCallsHandlingMethods($event, $handler) {
		$eventHandler = $this->getMock('\Crossmedia\FalMam\Task\EventHandler', NULL);
		$client = $this->getMock('\Crossmedia\FalMam\Service\MamClient');
		$eventHandler->injectClient($client);
		$state = $this->getMock('\Crossmedia\FalMam\Task\EventHandlerState');
		$eventHandler->injectState($state);
		$fileHandler = $this->getMock('\Crossmedia\FalMam\Service\FileHandler');
		$eventHandler->injectFileHandler($fileHandler);
		$dbHandler = $this->getMock('\Crossmedia\FalMam\Service\DbHandler');
		$eventHandler->injectDbHandler($dbHandler);

		$state->expects($this->once())
			  ->method('getEventId')
			  ->will($this->returnValue('123'));

		$client->expects($this->once())
			   ->method('getEvents')
			   ->will($this->returnValue(array($event)));

		$client->expects($this->any())
				->method('getBeans')
				->will($this->returnValue(array(
					array(
						'id' => 'data_20150416111838_37E3DD68599BAD01C567420BE95FB3F7',
						'module_name' => 'contact',
						'mod_time' => '2015/03/26 13:59:33',
						'type' => 'default',
						'properties' => array(
							'data_preview_sequence_count' => array(
								'value' => '0'
							),
							'data_modification_date' => array(
								'value' => '16.04.2015 11:19:13'
							),
							'data_name' => array(
								'value' => 'colorsmoke4.1.tif'
							),
							'data_id' => array(
								'value' => 'data_20150416111838_37E3DD68599BAD01C567420BE95FB3F7'
							),
							'data_subsubtype' => array(
								'value' => 'image/tiff'
							),
							'data_shellpath' => array(
								'value' => '/usr/local/mam/wanzl/data/PAP-Test/Freigabestatus/'
							)
						)
					)
				)));

		foreach ($handler as $handlerName => $methods) {
			foreach ($methods as $method => $expectation) {
				$$handlerName->expects($expectation)->method($method);
			}
		}
		$eventHandler->execute();
	}
}