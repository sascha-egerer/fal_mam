<?php
namespace Crossmedia\FalMam\Tests\Unit\Functional;

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Resource\ResourceFactory;
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
		chdir(PATH_site);


		$this->testStoragePath = 'fileadmin/__functional-test/';
		if (file_exists(PATH_site . $this->testStoragePath)) {
			$this->removeDirectory(PATH_site . $this->testStoragePath);
		}

		mkdir(PATH_site . $this->testStoragePath, 0777, TRUE);
		sleep(1);

		$this->importDataSet('Base.xml');

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'sys_file_storage',
			'uid=1',
			array(
				'configuration' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="basePath">
                    <value index="vDEF">fileadmin/__functional-test/</value>
                </field>
                <field index="pathType">
                    <value index="vDEF">relative</value>
                </field>
                <field index="caseSensitive">
                    <value index="vDEF">1</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>',
				'is_online' => 1
			)
		);

		$this->backendUser = $this->setUpBackendUserFromFixture(1);
		// By default make tests on live workspace
		$this->backendUser->workspace = 0;

		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeLanguageObject();
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

	public function importDataSet($path) {
		parent::importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/fal_mam/Tests/Functional/Fixtures/' . $path);
	}

	public function mockFile($filepath = 'filepath/foo.png', $derivateSuffix = '') {
		$absoluteFilepath = $this->testStoragePath . $filepath;
		if (!file_exists(dirname(PATH_site . $absoluteFilepath))) {
			mkdir(dirname(PATH_site . $absoluteFilepath), 0777, TRUE);
		}

		if (file_exists(PATH_site . $absoluteFilepath)) {
			unlink(PATH_site . $absoluteFilepath);
			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
				'sys_file',
				'1'
			);
		}

		touch(PATH_site . $absoluteFilepath);

		$fileObject = ResourceFactory::getInstance()->getObjectFromCombinedIdentifier('1:/' . $filepath);
		$fileObject->_getMetaData();

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'sys_file',
			'uid=1',
			array(
				'tx_falmam_id' => '1234',
				'tx_falmam_derivate_suffix' => $derivateSuffix
			)
		);
	}

	/**
	 * Initialize backend user
	 *
	 * @param int $userUid uid of the user we want to initialize. This user must exist in the fixture file
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 * @throws Exception
	 */
	protected function setUpBackendUserFromFixture($userUid) {
		$this->importDataSet('BeUsers.xml');
		$database = $this->getDatabaseConnection();
		$userRow = $database->exec_SELECTgetSingleRow('*', 'be_users', 'uid = ' . $userUid);

		/** @var $backendUser \TYPO3\CMS\Core\Authentication\BackendUserAuthentication */
		$backendUser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$sessionId = $backendUser->createSessionId();
		$_SERVER['HTTP_COOKIE'] = 'be_typo_user=' . $sessionId . '; path=/';
		$backendUser->id = $sessionId;
		$backendUser->sendNoCacheHeaders = FALSE;
		$backendUser->dontSetCookie = TRUE;
		$backendUser->createUserSession($userRow);

		$GLOBALS['BE_USER'] = $backendUser;
		$GLOBALS['BE_USER']->start();
		if (!is_array($GLOBALS['BE_USER']->user) || !$GLOBALS['BE_USER']->user['uid']) {
			throw new Exception(
				'Can not initialize backend user',
				1377095807
			);
		}
		$GLOBALS['BE_USER']->backendCheckLogin();

		return $backendUser;
	}

	public function getEventQueueHandler($mockMethods = NULL, $filepath = '/filepath/') {
		$eventQueueHandler = $this->getMock('\Crossmedia\FalMam\Task\EventQueueHandler', $mockMethods);

		$client = $this->getMock('\Crossmedia\FalMam\Service\MamClient');
		$eventQueueHandler->injectClient($client);

		$state = $this->getMock('\Crossmedia\FalMam\Task\EventHandlerState');
		$eventQueueHandler->injectState($state);

		$configuration = $this->getMock('\Crossmedia\FalMam\Service\Configuration');
		$eventQueueHandler->injectConfiguration($configuration);
		$configuration->base_path = $this->testStoragePath;
		$configuration->connector_name = 'some_connector';

		return array(
			'eventQueueHandler' => $eventQueueHandler,
			'client'=> $client,
			'state'=> $state,
			'resourceFactory' => $resourceFactory,
			'file' => $file,
			'configuration' => $configuration
		);
	}

	public function assetEventStatus($status, $uid = 1) {
		$event = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'tx_falmam_event_queue', 'uid=' . $uid);
		$this->assertEquals($status, $event['status'], 'Failed to assert, that the event status was set to done');
	}

	/**
	 * If the processing of an event succeeds it needs to be finnished
	 *
	 * @test
	 * @return void
	 */
	public function successfullEventsShouldBeFinnished() {
		$this->importDataSet('CreateMetadataEvent.xml');
		extract($this->getEventQueueHandler(array('callHook')));
		$this->mockFile('filepath/foo.png');

		$client->expects($this->any())
				->method('getBeans')
				->will($this->returnValue(array(
					array(
						'id' => '1234',
						'type' => 'file',
						'properties' => array(
							'data_name' => 'foo.png',
							'data_shellpath' => $this->testStoragePath . '/filepath/'
						)
					)
		)));

		$eventQueueHandler->execute();

		$this->assetEventStatus('DONE', 1);
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function createMetadataEventShouldFailIfNoFileIsFound() {
		$this->importDataSet('CreateMetadataEvent.xml');
		extract($this->getEventQueueHandler());

		$client->expects($this->any())
				->method('getBeans')
				->will($this->returnValue(array(
					array(
						'id' => '1234',
						'type' => 'file',
						'properties' => array(
							'data_name' => 'does-not-exist.png',
							'data_shellpath' => $this->testStoragePath
						)
					)
		)));

		$eventQueueHandler->execute();

		$event = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'tx_falmam_event_queue', 'uid=1');
		$this->assertGreaterThan(time(), $event['skipuntil'], 'Failed to assert, that skipuntil was set');
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function createFileEventShouldCreateTheFile() {
		$this->importDataSet('CreateFileEvent.xml');
		extract($this->getEventQueueHandler(array('callHook')));

		$client->expects($this->any())
				->method('getBeans')
				->will($this->returnValue(array(
					array(
						'id' => '1234',
						'type' => 'file',
						'properties' => array(
							'data_name' => 'foo.png',
							'data_shellpath' => $this->testStoragePath . '/filepath/'
						)
					)
		)));

		$eventQueueHandler->expects($this->once())->method('callHook')->with($this->equalTo('fileCreated'));

		$client->expects($this->once())->method('saveDerivate');
		$eventQueueHandler->execute();
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function createMetadataEventShouldCreateTheAssetIfTheFileExists() {
		$this->importDataSet('CreateMetadataEvent.xml');
		extract($this->getEventQueueHandler(array('callHook')));
		$this->mockFile('filepath/foo.png');

		$client->expects($this->any())
				->method('getBeans')
				->will($this->returnValue(array(
					array(
						'id' => '1234',
						'type' => 'file',
						'properties' => array(
							'data_name' => 'foo.png',
							'data_shellpath' => $this->testStoragePath . '/filepath/'
						)
					)
		)));

		$eventQueueHandler->expects($this->at(0))->method('callHook')->with($this->equalTo('mapMetadata'));
		$eventQueueHandler->expects($this->at(1))->method('callHook')->with($this->equalTo('assetUpdated'));

		$eventQueueHandler->execute();

		$this->assetEventStatus('DONE', 1);

		$file = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'sys_file', 'uid=1');
		$this->assertEquals('/filepath/foo.png', $file['identifier']);
		$this->assertEquals(1234, $file['tx_falmam_id']);
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function createBothEventShouldCreateTheAssetAndFile() {
		$this->importDataSet('CreateBothEvent.xml');
		extract($this->getEventQueueHandler(array('callHook')));
		$this->mockFile('filepath/foo.png');

		$client->expects($this->any())
				->method('getBeans')
				->will($this->returnValue(array(
					array(
						'id' => '1234',
						'type' => 'file',
						'properties' => array(
							'data_name' => 'foo.png',
							'data_shellpath' => $this->testStoragePath . '/filepath/'
						)
					)
		)));

		$client->expects($this->once())->method('saveDerivate');
		$eventQueueHandler->execute();

		$this->assetEventStatus('DONE', 1);

		$file = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'sys_file', 'uid=1');
		$this->assertEquals('/filepath/foo.png', $file['identifier']);
		$this->assertEquals(1234, $file['tx_falmam_id']);
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function deleteMetadataEventShouldDeleteAssetAndFile() {
		$this->importDataSet('DeleteMetadataEvent.xml');
		extract($this->getEventQueueHandler(array('callHook')));
		$this->mockFile('filepath/foo.png');

		$eventQueueHandler->expects($this->once())->method('callHook')->with($this->equalTo('assetDeleted'));

		$eventQueueHandler->execute();

		$this->assetEventStatus('DONE', 1);

		$file = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'sys_file', 'uid=1');
		$this->assertFalse($file, 'There should\'nt be a sys_file with uid 1 anymore ');
		$this->assertFalse(file_exists($this->testStoragePath . '/filepath/foo.png'), 'File should not exist anymore!');
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function deleteFileEventShouldDeleteAssetAndField() {
		$this->importDataSet('DeleteFileEvent.xml');
		extract($this->getEventQueueHandler(array('callHook')));
		$this->mockFile('filepath/foo.png');

		$eventQueueHandler->expects($this->once())->method('callHook')->with($this->equalTo('assetDeleted'));

		$eventQueueHandler->execute();

		$this->assetEventStatus('DONE', 1);

		$file = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'sys_file', 'uid=1');
		$this->assertFalse($file, 'There should\'nt be a sys_file with uid 1 anymore ');
		$this->assertFalse(file_exists($this->testStoragePath . '/filepath/foo.png'), 'File should not exist anymore!');
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function deleteBothEventShouldDeleteAssetAndField() {
		$this->importDataSet('DeleteBothEvent.xml');
		extract($this->getEventQueueHandler(array('callHook')));
		$this->mockFile('filepath/foo.png');

		$eventQueueHandler->execute();

		$this->assetEventStatus('DONE', 1);

		$file = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'sys_file', 'uid=1');
		$this->assertFalse($file, 'There should\'nt be a sys_file with uid 1 anymore ');
		$this->assertFalse(file_exists($this->testStoragePath . '/filepath/foo.png'), 'File should not exist anymore!');
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function deleteMetadataShouldDeleteAssetEvenIfFileIsAlreadyMissing() {
		$this->importDataSet('DeleteMetadataEvent.xml');
		extract($this->getEventQueueHandler(array('callHook')));
		$this->mockFile('filepath/foo.png');
		unlink($this->testStoragePath . '/filepath/foo.png');

		$eventQueueHandler->execute();

		$this->assetEventStatus('DONE', 1);

		$file = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'sys_file', 'uid=1');
		$this->assertFalse($file, 'There should\'nt be a sys_file with uid 1 anymore ');
		$this->assertFalse(file_exists($this->testStoragePath . '/filepath/foo.png'), 'File should not exist anymore!');
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function updateMetadata() {
		$this->importDataSet('UpdateMetadataEvent.xml');
		extract($this->getEventQueueHandler(array('callHook')));
		$this->mockFile('filepath/foo.png');

		$client->expects($this->any())
				->method('getBeans')
				->will($this->returnValue(array(
					array(
						'id' => '1234',
						'type' => 'file',
						'properties' => array(
							'data_name' => 'foo.png',
							'data_shellpath' => $this->testStoragePath . '/filepath/'
						)
					)
		)));
		$eventQueueHandler->expects($this->at(0))->method('callHook')->with($this->equalTo('mapMetadata'));
		$eventQueueHandler->expects($this->at(1))->method('callHook')->with($this->equalTo('assetUpdated'));

		$eventQueueHandler->execute();

		$this->assetEventStatus('DONE', 1);

		$file = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'sys_file', 'uid=1');
		$this->assertEquals('/filepath/foo.png', $file['identifier']);
		$this->assertGreaterThanOrEqual(time() - 1, $file['tstamp']);
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function updateFileContents() {
		$this->importDataSet('UpdateFileEvent.xml');
		extract($this->getEventQueueHandler(array('callHook')));
		$this->mockFile('filepath/foo.png');

		$client->expects($this->any())
				->method('getBeans')
				->will($this->returnValue(array(
					array(
						'id' => '1234',
						'type' => 'file',
						'properties' => array(
							'data_name' => 'foo.png',
							'data_shellpath' => $this->testStoragePath . '/filepath/'
						)
					)
		)));
		$eventQueueHandler->expects($this->once())->method('callHook')->with($this->equalTo('fileUpdated'));

		$client->expects($this->once())->method('saveDerivate');
		$eventQueueHandler->execute();
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function updateFilePath() {
		$this->importDataSet('UpdateMetadataEvent.xml');
		extract($this->getEventQueueHandler(array('callHook')));
		$this->mockFile('filepath/foo.png');

		$client->expects($this->any())
				->method('getBeans')
				->will($this->returnValue(array(
					array(
						'id' => '1234',
						'type' => 'file',
						'properties' => array(
							'data_name' => 'foo.png',
							'data_shellpath' => $this->testStoragePath . '/new-filepath/'
						)
					)
		)));

		$eventQueueHandler->expects($this->at(0))->method('callHook')->with($this->equalTo('mapMetadata'));
		$eventQueueHandler->expects($this->at(1))->method('callHook')->with($this->equalTo('assetUpdated'));

		$eventQueueHandler->execute();

		$this->assetEventStatus('DONE', 1);

		$file = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'sys_file', 'uid=1');
		$this->assertEquals('/new-filepath/foo.png', $file['identifier']);
		$this->assertGreaterThanOrEqual(time() - 1, $file['tstamp']);

		$this->assertFalse(file_exists($this->testStoragePath . '/filepath/foo.png'), 'Old file was not moved/deleted!');
		$this->assertTrue(file_exists($this->testStoragePath . '/new-filepath/foo.png'), 'File was not moved!');
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function emptyFoldersAreRemoved() {
		$this->importDataSet('UpdateMetadataEvent.xml');
		extract($this->getEventQueueHandler(array('callHook')));
		$this->mockFile('filepath/subpath/foo.png');

		$client->expects($this->any())
				->method('getBeans')
				->will($this->returnValue(array(
					array(
						'id' => '1234',
						'type' => 'file',
						'properties' => array(
							'data_name' => 'foo.png',
							'data_shellpath' => $this->testStoragePath . '/new-filepath/subpath/'
						)
					)
		)));
		$eventQueueHandler->execute();

		$this->assetEventStatus('DONE', 1);

		$file = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'sys_file', 'uid=1');
		$this->assertEquals('/new-filepath/subpath/foo.png', $file['identifier']);
		$this->assertGreaterThanOrEqual(time() - 1, $file['tstamp']);

		$this->assertFalse(file_exists($this->testStoragePath . '/filepath/subpath/foo.png'), 'Old file was not moved/deleted!');
		$this->assertTrue(file_exists($this->testStoragePath . '/new-filepath/subpath/foo.png'), 'File was not moved!');

		$this->assertFalse(is_dir($this->testStoragePath . '/filepath/subpath'), 'empty folder was not deleted');
		$this->assertFalse(is_dir($this->testStoragePath . '/filepath'), 'empty folder was not deleted');
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function derivateExtensionIsSaved() {
		$this->importDataSet('CreateFileEvent.xml');
		extract($this->getEventQueueHandler(array('callHook')));
		$this->mockFile('filepath/foo.png.jpg');

		$client->expects($this->any())
				->method('getBeans')
				->will($this->returnValue(array(
					array(
						'id' => '1234',
						'type' => 'file',
						'properties' => array(
							'data_name' => 'foo.png',
							'data_shellpath' => $this->testStoragePath . '/filepath/'
						)
					)
		)));

		$client->expects($this->once())->method('saveDerivate')->will($this->returnValue('jpg'));
		$eventQueueHandler->execute();

		$file = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'sys_file', 'uid=1');
		$this->assertEquals('/filepath/foo.png.jpg', $file['identifier']);
		$this->assertEquals('jpg', $file['tx_falmam_derivate_suffix']);
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function metadataUpdateRespectsDerivateExtension() {
		$this->importDataSet('UpdateMetadataEvent.xml');
		extract($this->getEventQueueHandler(array('callHook')));
		$this->mockFile('filepath/foo.png.jpg', 'jpg');

		$client->expects($this->any())
				->method('getBeans')
				->will($this->returnValue(array(
					array(
						'id' => '1234',
						'type' => 'file',
						'properties' => array(
							'data_name' => 'foo2.png',
							'data_shellpath' => $this->testStoragePath . '/filepath/subpath/'
						)
					)
		)));

		$eventQueueHandler->execute();

		$file = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'sys_file', 'uid=1');
		$this->assertEquals('/filepath/subpath/foo2.png.jpg', $file['identifier']);
		$this->assertEquals('jpg', $file['tx_falmam_derivate_suffix']);
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function metadataMappingWorks() {
		$this->importDataSet('UpdateMetadataEvent.xml');
		extract($this->getEventQueueHandler(array('callHook')));
		$this->mockFile('filepath/foo.png');

		$configuration->mapping = array(
			'some_field' => array(
				'fal_field' => 'title'
			)
		);

		$client->expects($this->any())
				->method('getBeans')
				->will($this->returnValue(array(
					array(
						'id' => '1234',
						'type' => 'file',
						'properties' => array(
							'data_name' => 'foo.png',
							'data_shellpath' => $this->testStoragePath . '/filepath/',
							'some_field' => 'foo'
						)
					)
		)));
		$eventQueueHandler->expects($this->at(0))->method('callHook')->with($this->equalTo('mapMetadata'));
		$eventQueueHandler->expects($this->at(1))->method('callHook')->with($this->equalTo('assetUpdated'));

		$eventQueueHandler->execute();

		$this->assetEventStatus('DONE', 1);

		$metadata = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'sys_file_metadata', 'file=1');
		$this->assertEquals('foo', $metadata['title']);
	}

	/**
	 *
	 * @test
	 * @group focus
	 * @return void
	 */
	public function metadataMappingWorksWithValueMap() {
		$this->importDataSet('UpdateMetadataEvent.xml');
		extract($this->getEventQueueHandler(array('callHook')));
		$this->mockFile('filepath/foo.png');

		$configuration->mapping = array(
			'some_field' => array(
				'fal_field' => 'title',
				'value_map' => array(
					'foo' => 'bar'
				)
			)
		);

		$client->expects($this->any())
				->method('getBeans')
				->will($this->returnValue(array(
					array(
						'id' => '1234',
						'type' => 'file',
						'properties' => array(
							'data_name' => 'foo.png',
							'data_shellpath' => $this->testStoragePath . '/filepath/',
							'some_field' => 'foo'
						)
					)
		)));
		$eventQueueHandler->expects($this->at(0))->method('callHook')->with($this->equalTo('mapMetadata'));
		$eventQueueHandler->expects($this->at(1))->method('callHook')->with($this->equalTo('assetUpdated'));

		$eventQueueHandler->execute();

		$this->assetEventStatus('DONE', 1);

		$metadata = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'sys_file_metadata', 'file=1');
		$this->assertEquals('bar', $metadata['title']);
	}
}