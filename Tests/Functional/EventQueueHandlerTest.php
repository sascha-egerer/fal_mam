<?php
namespace Crossmedia\FalMam\Tests\Unit\Functional\Repository;

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Functional test for the DataHandler
 */
class EventQueueHandlerTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

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
	 * If the processing of an event succeeds it needs to be finnished
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

	/**
	 *
	 * @test
	 * @return void
	 */
	public function createMetadataEventShouldFailIfNoFileIsFound() {
		extract($this->getEventQueueHandler());

		// $dbHandler->expects($this->once())->method('createAsset');
		$fileHandler->expects($this->once())->method('fileExists')->will($this->returnValue(FALSE));

		$result = $eventQueueHandler->processEvent(array(
			'object_id' => 'foo',
			'event_type' => 'create',
			'target' => 'metadata'
		));

		$this->assertFalse($result, 'processCreateEvent should fail if the file does not exist');
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function createFileEventShouldCreateTheFile() {
		extract($this->getEventQueueHandler());

		$client->expects($this->once())->method('saveDerivate')->with(
			'/filepath/foo.png',
			'1234'
		);

		$eventQueueHandler->processEvent(array(
			'object_id' => '1234',
			'event_type' => 'create',
			'target' => 'file'
		));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function createMetadataEventShouldCreateTheAssetIfTheFileExists() {
		extract($this->getEventQueueHandler());

		$dbHandler->expects($this->once())->method('createAsset');
		$fileHandler->expects($this->once())->method('fileExists')->will($this->returnValue(TRUE));

		$eventQueueHandler->processEvent(array(
			'object_id' => '1234',
			'event_type' => 'create',
			'target' => 'metadata'
		));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function createBothEventShouldCreateTheAssetAndFile() {
		extract($this->getEventQueueHandler());

		$dbHandler->expects($this->once())->method('createAsset');
		$fileHandler->expects($this->once())->method('fileExists')->will($this->returnValue(TRUE));
		$client->expects($this->once())->method('saveDerivate')->with(
			'/filepath/foo.png',
			'1234'
		);

		$eventQueueHandler->processEvent(array(
			'object_id' => '1234',
			'event_type' => 'create',
			'target' => 'both'
		));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function deleteMetadataEventShouldDeleteAssetAndField() {
		extract($this->getEventQueueHandler());

		$dbHandler->expects($this->once())->method('deleteAsset');

		$eventQueueHandler->processEvent(array(
			'object_id' => '1234',
			'event_type' => 'delete',
			'target' => 'metadata'
		));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function deleteFileEventShouldDeleteAssetAndField() {
		extract($this->getEventQueueHandler());

		$dbHandler->expects($this->once())->method('deleteAsset');

		$eventQueueHandler->processEvent(array(
			'object_id' => '1234',
			'event_type' => 'delete',
			'target' => 'file'
		));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function updateMetadata() {
		extract($this->getEventQueueHandler());

		$dbHandler->expects($this->once())->method('updateAsset');

		$eventQueueHandler->processEvent(array(
			'object_id' => '1234',
			'event_type' => 'update',
			'target' => 'metadata'
		));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function updateFileContents() {
		extract($this->getEventQueueHandler());

		$client->expects($this->once())->method('saveDerivate')->with(
			'/filepath/foo.png',
			'1234'
		);

		$eventQueueHandler->processEvent(array(
			'object_id' => '1234',
			'event_type' => 'update',
			'target' => 'file'
		));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function updateFilePath() {
		extract($this->getEventQueueHandler(NULL, '/new-filepath/'));

		$fileHandler->expects($this->once())->method('moveFile')->with(
			'1234',
			'foo.png',
			'/new-filepath/'
		);

		$client->expects($this->once())->method('saveDerivate')->with(
			'/new-filepath/foo.png',
			'1234'
		);

		$eventQueueHandler->processEvent(array(
			'object_id' => '1234',
			'event_type' => 'update',
			'target' => 'file'
		));
	}

	public function getEventQueueHandler($mockMethods = NULL, $filepath = '/filepath/') {
		$eventQueueHandler = $this->getMock('\Crossmedia\FalMam\Task\EventQueueHandler', $mockMethods);

		$client = $this->getMock('\Crossmedia\FalMam\Service\MamClient');
		$eventQueueHandler->injectClient($client);
		$client->expects($this->any())
				->method('getBeans')
				->will($this->returnValue(array(
					array(
						'id' => '1234',
						'type' => 'file',
						'properties' => array(
							'data_name' => 'foo.png',
							'data_shellpath' => $filepath
						)
					)
		)));

		$state = $this->getMock('\Crossmedia\FalMam\Task\EventHandlerState');
		$eventQueueHandler->injectState($state);

		$fileHandler = $this->getMock('\Crossmedia\FalMam\Service\FileHandler');
		$eventQueueHandler->injectFileHandler($fileHandler);

		$dbHandler = $this->getMock('\Crossmedia\FalMam\Service\DbHandler');
		$eventQueueHandler->injectDbHandler($dbHandler);

		return array(
			'eventQueueHandler' => $eventQueueHandler,
			'client'=> $client,
			'state'=> $state,
			'fileHandler'=> $fileHandler,
			'dbHandler'=> $dbHandler
		);
	}
}