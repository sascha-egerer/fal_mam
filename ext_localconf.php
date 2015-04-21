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
    'description'      => 'This task handles pending events from the mam api',
    'additionalFields' => '\\Crossmedia\\FalMam\\Task\\EventHandlerFieldProvider'
);

?>