<?php
$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('fal_mam');
$path = $extensionPath . 'Resources/PHP/Requests/library';
$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
$classMap = array();
foreach($objects as $fileName => $object){
	if ($object->getExtension() !== 'php') {
		continue;
	}
	$className = str_replace($path, '', $fileName);
	$className = ltrim($className, '/');
	$className = str_replace('/', '\\', $className);
	$className = str_replace('.' . $object->getExtension(), '', $className);
	$classMap[$className] = $fileName;
}
return $classMap;
// return array(
// 	'Requests' => $basePath . 'Requests.php',
// );
?>