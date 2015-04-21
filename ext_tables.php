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
?>