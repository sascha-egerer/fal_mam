<?php
namespace Crossmedia\FalMam\Tests\Unit\Functional\Repository;

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Functional test for the DataHandler
 */
class FileHandlerTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

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
		extract($this->getFileHandlerMock());

		$dbHandler->expects($this->once())->method('getFileObject');
		$resourceStorage->expects($this->once())->method('moveFile');

		$fileHandler->moveFile('1234', 'foo.png', '/new-filepath/');
	}

	public function getFileHandlerMock($mockMethods = NULL) {
		$fileHandler = $this->getMock('\Crossmedia\FalMam\Service\FileHandler', $mockMethods);

		$resourceStorage = $this->getMock('\TYPO3\CMS\Core\Resource\ResourceStorage', array('moveFile'), array(
			$this->getMock('\TYPO3\CMS\Core\Resource\Driver\DriverInterface'),
			array()
		));
		$fileHandler->setResourceStorage($resourceStorage);

		$dbHandler = $this->getMock('\Crossmedia\FalMam\Service\DbHandler');
		$fileHandler->injectDbHandler($dbHandler);

		return array(
			'fileHandler' => $fileHandler,
			'resourceStorage'=> $resourceStorage,
			'dbHandler'=> $dbHandler
		);
	}
}