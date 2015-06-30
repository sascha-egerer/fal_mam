<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

$registerDriver = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Driver\\DriverRegistry');
$registerDriver->registerDriverClass(
	'Crossmedia\\FalMam\\Driver\\MamDriver',
	'MAM',
	'MAM filesystem',
	'FILE:EXT:fal_mam/Configuration/FlexForm/MamDriver.xml'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Crossmedia\FalMam\Task\EventHandler'] = array(
    'extension'        => $_EXTKEY,
    'title'            => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_scheduler.xlf:eventHandler.name',
    'description'      => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_scheduler.xlf:eventHandler.description'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Crossmedia\FalMam\Task\EventQueueHandler'] = array(
    'extension'        => $_EXTKEY,
    'title'            => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_scheduler.xlf:eventQueueHandler.name',
    'description'      => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_scheduler.xlf:eventQueueHandler.description',
    'additionalFields' => '\\Crossmedia\\FalMam\\Task\\EventQueueHandlerFieldProvider'
);

?>