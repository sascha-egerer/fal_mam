<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

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