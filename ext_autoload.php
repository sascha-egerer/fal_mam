<?php
$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('fal_mam');
$path = $extensionPath . 'Resources/PHP/Requests/library';
$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
$classMap = array();
foreach($objects as $fileName => $object){
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
	if ($fileExtension !== 'php') {
		continue;
	}
	$className = str_replace($path, '', $fileName);
	$className = ltrim($className, '/');
	$className = str_replace('/', '\\', $className);
	$className = str_replace('.' . $fileExtension, '', $className);

	//use original class names
	$className = ltrim(str_replace('\\', '_', $className), '_');
	//fix path separator
	$fileName = str_replace('\\', '/', $fileName);

	$classMap[$className] = $fileName;
}

#echo '<pre>' . var_export($classMap, true) . '</pre>';
#die();

return $classMap;
// return array(
// 	'Requests' => $basePath . 'Requests.php',
// );
?>