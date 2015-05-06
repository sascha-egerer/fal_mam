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

	protected $testStoragePath;

	/**
	 * Set up creates a test instance and database.
	 *
	 * This method should be called with parent::setUp() in your test cases!
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->testStoragePath = ORIGINAL_ROOT . 'fileadmin/';
		$tmpfile = tempnam($this->testStoragePath, 'functional-test-');
		unlink($tmpfile);
		$this->testStoragePath = $tmpfile . '/';
		mkdir($this->testStoragePath, 0777, TRUE);

	}

	/**
	 * @return void
	 */
	public function tearDown() {
		$this->removeDirectory($this->testStoragePath);
	}

	public function removeDirectory($path) {
		$files = array_diff(scandir($path), array('.', '..'));
		foreach ($files as $file) {
			$subPath = $path . '/' . $file;
			if (is_dir($subPath)) {
				$this->removeDirectory($subPath);
			} else {
				unlink($subPath);
			}
		}
		return rmdir($path);
	}

	/**
	 * If the processing of an event fails it needs to be rescheduled.
	 *
	 * @test
	 * @return void
	 */
	public function failedEventsShouldBeRescheduled() {
		$eventQueueHandler = $this->getMock('\Crossmedia\FalMam\Task\EventQueueHandler', array('processEvent', 'claimEventFromQueue', 'rescheduleEvent', 'finnishEvent'));

		// $state->expects($this->once())->method('getConfigHash')->will($this->returnValue('foo'));
		$eventQueueHandler->expects($this->exactly(2))
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

		$eventQueueHandler->expects($this->once())->method('rescheduleEvent');
		$eventQueueHandler->expects($this->never())->method('finnishEvent');

		$eventQueueHandler->execute();
	}

	/**
	 * If the processing of an event succeeds it needs to be finnished
	 *
	 * @test
	 * @return void
	 */
	public function successfullEventsShouldBeFinnished() {
		$eventQueueHandler = $this->getMock('\Crossmedia\FalMam\Task\EventQueueHandler', array('processEvent', 'claimEventFromQueue', 'rescheduleEvent', 'finnishEvent'));

		// $state->expects($this->once())->method('getConfigHash')->will($this->returnValue('foo'));
		$eventQueueHandler->expects($this->exactly(2))
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

		$eventQueueHandler->expects($this->once())->method('finnishEvent');
		$eventQueueHandler->expects($this->never())->method('rescheduleEvent');

		$eventQueueHandler->execute();
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function createMetadataEventShouldFailIfNoFileIsFound() {
		extract($this->getEventQueueHandler(array('fileExists')));

		// $eventQueueHandler->expects($this->once())->method('createAsset');
		$eventQueueHandler->expects($this->once())->method('fileExists')->will($this->returnValue(FALSE));

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
		extract($this->getEventQueueHandler(array('fileExists')));

		$eventQueueHandler->expects($this->once())->method('createAsset');
		$eventQueueHandler->expects($this->once())->method('fileExists')->will($this->returnValue(TRUE));

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
		extract($this->getEventQueueHandler(array('fileExists')));

		$eventQueueHandler->expects($this->once())->method('createAsset');
		$eventQueueHandler->expects($this->once())->method('fileExists')->will($this->returnValue(TRUE));
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
		extract($this->getEventQueueHandler(array('deleteAsset')));

		$eventQueueHandler->expects($this->once())->method('deleteAsset');

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
		extract($this->getEventQueueHandler(array('deleteAsset')));

		$eventQueueHandler->expects($this->once())->method('deleteAsset');

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
		extract($this->getEventQueueHandler(array('updateAsset')));

		$eventQueueHandler->expects($this->once())->method('updateAsset');

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
		extract($this->getEventQueueHandler(array('moveFile'), '/new-filepath/'));

		$eventQueueHandler->expects($this->once())->method('moveFile')->with(
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

		$resourceStorage = $this->getMock('\TYPO3\CMS\Core\Resource\ResourceStorage', array('moveFile', 'getUid'), array(
			$this->getMock('\TYPO3\CMS\Core\Resource\Driver\DriverInterface'),
			array()
		));
		$eventQueueHandler->injectResourceStorage($resourceStorage);
		$resourceStorage->expects($this->any())->method('getUid')->will($this->returnValue(1));

		$file = $this->getMock('\TYPO3\CMS\Core\Resource\File', array(), array(array(), $resourceStorage));

		$resourceFactory = $this->getMock('\TYPO3\CMS\Core\Resource\ResourceFactory', array('getObjectFromCombinedIdentifier', 'getFileObject'));
		$eventQueueHandler->injectResourceFactory($resourceFactory);
		$resourceFactory->expects($this->any())
					->method('getObjectFromCombinedIdentifier')
					->will($this->returnValue($file));
		$resourceFactory->expects($this->any())
					->method('getFileObject')
					->will($this->returnValue($file));

		$state = $this->getMock('\Crossmedia\FalMam\Task\EventHandlerState');
		$eventQueueHandler->injectState($state);

		$configuration = $this->getMock('\Crossmedia\FalMam\Service\Configuration');
		$eventQueueHandler->injectConfiguration($configuration);

		return array(
			'eventQueueHandler' => $eventQueueHandler,
			'client'=> $client,
			'state'=> $state,
			'resourceFactory' => $resourceFactory,
			'file' => $file
		);
	}
}