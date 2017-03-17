<?php

$GLOBALS['TCA']['tx_falmam_log'] = array(
    'ctrl' => [
        'title'     => 'LLL:EXT:fal_mam/locallang_db.xml:tx_falmam_log',
        'label'     => 'uid',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('fal_mam') . 'Configuration/Tca/Log.php',
        'iconfile'          => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('fal_mam') . 'Resources/Public/Icons/tx_falmam_log.gif',
    ],
    'interface' => array(
        'showRecordFieldList' => 'hidden,connector_name,config_hash,event_id,start_time,end_time,event_count,runtime'
    ),
    'feInterface' => $TCA['tx_falmam_log']['feInterface'],
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config'  => array(
                'type'    => 'check',
                'default' => '0'
            )
        ),
        'connector_name' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_log.connector_name',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'config_hash' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_log.config_hash',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'event_id' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_log.event_id',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'start_time' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_log.start_time',
            'config' => array(
                'type'     => 'input',
                'size'     => '12',
                'max'      => '20',
                'eval'     => 'datetime',
                'checkbox' => '0',
                'default'  => '0'
            )
        ),
        'end_time' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_log.end_time',
            'config' => array(
                'type'     => 'input',
                'size'     => '12',
                'max'      => '20',
                'eval'     => 'datetime',
                'checkbox' => '0',
                'default'  => '0'
            )
        ),
        'event_count' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_log.event_count',
            'config' => array(
                'type'     => 'input',
                'size'     => '4',
                'max'      => '4',
                'eval'     => 'int',
                'checkbox' => '0',
                'range'    => array(
                    'upper' => '1000',
                    'lower' => '10'
                ),
                'default' => 0
            )
        ),
        'runtime' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_log.runtime',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'double2',
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'hidden;;1;;1-1-1, connector_name, config_hash, event_id, start_time, end_time, event_count, runtime')
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    )
);
?>