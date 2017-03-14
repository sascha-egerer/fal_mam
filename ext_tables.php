<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
$TCA['tx_falmam_state'] = array(
    'ctrl' => array(
        'title'     => 'LLL:EXT:fal_mam/locallang_db.xml:tx_falmam_state',
        'label'     => 'uid',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/Tca/State.php',
        'iconfile'          => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/tx_falmam_state.gif',
    ),
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_falmam_state');

$TCA['tx_falmam_event_queue'] = array(
'ctrl' => array(
        'title'     => 'LLL:EXT:fal_mam/locallang_db.xml:tx_falmam_event_queue',
        'label'     => 'uid',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/Tca/Queue.php',
        'iconfile'          => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/tx_falmam_event_queue.gif',
    ),
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_falmam_event_queue');

$TCA['tx_falmam_mapping'] = array(
    'ctrl' => array(
        'title'     => 'LLL:EXT:fal_mam/locallang_db.xml:tx_falmam_mapping',
        'label'     => 'uid',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/Tca/Mapping.php',
        'iconfile'          => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/tx_falmam_mapping.gif',
    ),
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_falmam_mapping');

$TCA['tx_falmam_log'] = array(
    'ctrl' => array(
        'title'     => 'LLL:EXT:fal_mam/locallang_db.xml:tx_falmam_log',
        'label'     => 'uid',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/Tca/Log.php',
        'iconfile'          => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/tx_falmam_log.gif',
    ),
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_falmam_log');

if (TYPO3_MODE === 'BE') {

    /**
     * Registers a Backend Module
     */
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Crossmedia.' . $_EXTKEY,
        'user',     // Make module a submodule of 'user'
        'mam_dashboard', // Submodule key
        '',                     // Position
        array(
            'Dashboard' => 'index,configuration,sync,skipHistory,analyze',
        ),
        array(
            'access' => 'user,group',
            'icon'   => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_dashboard.xlf',
        )
    );

}

$tempColumns = array(
    'tx_falmam_id' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:sys_file.tx_falmam_id',
        'config' => array(
            'type' => 'input',
            'size' => '30',
        )
    ),
    'tx_falmam_derivate_suffix' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:sys_file.tx_falmam_derivate_suffix',
        'config' => array(
            'type' => 'input',
            'size' => '30',
        )
    ),
);


//\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadTCA('sys_file');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_file',$tempColumns,1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('sys_file','tx_falmam_id;;;;1-1-1,tx_falmam_derivate_suffix;;;;1-1-1');