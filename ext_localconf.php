<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

$registerDriver = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Driver\\DriverRegistry');
$registerDriver->registerDriverClass(
	'Crossmedia\\FalMam\\Driver\\MamDriver',
	'MAM',
	'MAM filesystem',
	'FILE:EXT:fal_ftp/Configuration/FlexForm/MamDriver.xml'
);

?>