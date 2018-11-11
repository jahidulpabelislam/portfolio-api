<?php
/*
 * The base endpoint for all request for this API.
 * @author Jahidul Pabel Islam
*/

use JPI\API\Router;

Router::performRequest();

// autoload function
function __autoload($class) {

	// The string(s) in the namespace that aren't real folder(s)
	$namespaceNotInFile = "JPI\API\\";

	// Remove any strings that are in the namespace but isn't actually a folder
	$folders = str_replace($namespaceNotInFile, '', $class);
	
	// Replace the namespaces back slashes into forward slashes for folder/file paths
	$folders = str_replace('\\', '/', $folders);

	// Create the full file path to the class file
	$file = "classes/$folders.php";

	require_once($file);
}