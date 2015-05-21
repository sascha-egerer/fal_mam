<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$TCA['tx_falmam_state'] = array(
    'ctrl' => $TCA['tx_falmam_state']['ctrl'],
    'interface' => array(
        'showRecordFieldList' => 'hidden,connector_name,config_hash,event_id,sync_id,sync_offset,notified'
    ),
    'feInterface' => $TCA['tx_falmam_state']['feInterface'],
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
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_state.connector_name',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'config_hash' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_state.config_hash',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'event_id' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_state.event_id',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'sync_id' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_state.sync_id',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'sync_offset' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_state.sync_offset',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'notified' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_event_queue.notified',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'hidden;;1;;1-1-1, connector_name, config_hash, event_id, sync_id, sync_offset, notified')
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    )
);