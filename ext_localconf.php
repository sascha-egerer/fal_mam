<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

$registerDriver = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Driver\\DriverRegistry');
$registerDriver->registerDriverClass(
	'Crossmedia\\FalMam\\Driver\\MamDriver',
	'MAM',
	'MAM filesystem',
	'FILE:EXT:fal_mam/Configuration/FlexForm/MamDriver.xml'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['\Crossmedia\FalMam\Task\EventHandler'] = array(
    'extension'        => $_EXTKEY,
    'title'            => 'TYPO3 MAM EventHandler',
    'description'      => 'This task fetches events and writes them to a queue'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['\Crossmedia\FalMam\Task\EventQueueHandler'] = array(
    'extension'        => $_EXTKEY,
    'title'            => 'TYPO3 MAM Event Queue Handler',
    'description'      => 'This task takes events out of the queue and processes them',
    'additionalFields' => '\\Crossmedia\\FalMam\\Task\\EventQueueHandlerFieldProvider'
);

?>