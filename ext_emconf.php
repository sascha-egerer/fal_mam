<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "fal_ftp".
 *
 * Auto generated 16-04-2015 11:04
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'FAL MAM Driver',
	'description' => 'Provides a Driver for the Crossmedia MAM.',
	'category' => 'plugin',
	'version' => '1.1.2',
	'state' => 'stable',
	'uploadfolder' => true,
	'createDirs' => '',
	'clearcacheonload' => true,
	'author' => 'Marc Neuhaus',
	'author_email' => 'mneuhaus@famelo.com',
	'constraints' =>
	array (
		'depends' =>
		array (
			'filemetadata' => '*',
			'php' => '5.3.3-0.0.0',
			'typo3' => '6.2.0-8.7.99',
		),
		'conflicts' =>
		array (
		),
		'suggests' =>
		array (
		),
	),
);

