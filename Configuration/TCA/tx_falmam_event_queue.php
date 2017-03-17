<?php

$GLOBALS['TCA']['tx_falmam_event_queue'] = array(
    'ctrl' => array(
        'title' => 'LLL:EXT:fal_mam/locallang_db.xml:tx_falmam_event_queue',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('fal_mam') . 'Resources/Public/Icons/tx_falmam_event_queue.gif',
    ),
    'interface' => array(
        'showRecordFieldList' => 'event_id,status,runtime,object_id,event_type',
    ),
    'types' => array(
        '0' => array('showitem' => 'event_id;;;;1-1-1, status, runtime, object_id, event_type, target'),
    ),
    'palettes' => array(
        '1' => array('showitem' => ''),
    ),
    'columns' => array(
        'event_id' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_event_queue.event_id',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            ),
        ),
        'status' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_event_queue.status',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            ),
        ),
        'runtime' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_event_queue.runtime',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            ),
        ),
        'object_id' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_event_queue.object_id',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            ),
        ),
        'event_type' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_event_queue.event_type',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            ),
        ),
        'target' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_event_queue.target',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'exclude' => 1,
            ),
        ),
        'skipuntil' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_event_queue.skipuntil',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            ),
        ),
    )
);