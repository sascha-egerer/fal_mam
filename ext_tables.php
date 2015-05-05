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
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'Configuration/Tca/State.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY) . 'icon_tx_falmam_state.gif',
    ),
);
t3lib_extMgm::allowTableOnStandardPages('tx_falmam_state');

$TCA['tx_falmam_log'] = array(
    'ctrl' => array(
        'title'     => 'LLL:EXT:fal_mam/locallang_db.xml:tx_falmam_log',
        'label'     => 'uid',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'Configuration/Tca/Log.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY) . 'icon_tx_falmam_log.gif',
    ),
);
t3lib_extMgm::allowTableOnStandardPages('tx_falmam_log');

$TCA['tx_falmam_event_queue'] = array(
'ctrl' => array(
        'title'     => 'LLL:EXT:fal_mam/locallang_db.xml:tx_falmam_event_queue',
        'label'     => 'uid',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'Configuration/Tca/Queue.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY) . 'icon_tx_falmam_event_queue.gif',
    ),
);
t3lib_extMgm::allowTableOnStandardPages('tx_falmam_event_queue');

$TCA['tx_falmam_mapping'] = array(
    'ctrl' => array(
        'title'     => 'LLL:EXT:fal_mam/locallang_db.xml:tx_falmam_mapping',
        'label'     => 'uid',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'Configuration/Tca/Mapping.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY) . 'icon_tx_falmam_mapping.gif',
    ),
);
t3lib_extMgm::allowTableOnStandardPages('tx_falmam_mapping');

if (TYPO3_MODE === 'BE') {

    /**
     * Registers a Backend Module
     */
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Crossmedia.' . $_EXTKEY,
        'tools',     // Make module a submodule of 'tools'
        'mam_dashboard', // Submodule key
        '',                     // Position
        array(
            'Dashboard' => 'index,configuration',
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
);


t3lib_div::loadTCA('sys_file');
t3lib_extMgm::addTCAcolumns('sys_file',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('sys_file','tx_falmam_id;;;;1-1-1');

?>