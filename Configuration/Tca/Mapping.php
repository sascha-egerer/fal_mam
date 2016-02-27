<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$TCA['tx_falmam_mapping'] = array(
    'ctrl' => $TCA['tx_falmam_mapping']['ctrl'],
    'interface' => array(
        'showRecordFieldList' => 'connector_name,mam_field,fal_field'
    ),
    'feInterface' => $TCA['tx_falmam_mapping']['feInterface'],
    'columns' => array(
        'connector_name' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_mapping.connector_name',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'mam_field' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_mapping.mam_field',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'fal_field' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_mapping.fal_field',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'value_map' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:fal_mam/Resources/Private/Language/locallang_db.xml:tx_falmam_mapping.value_map',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'connector_name;;;;1-1-1, mam_field, fal_field, value_map')
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    )
);
?>