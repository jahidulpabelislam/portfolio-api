<?php
/*
 * The base endpoint for all request for this API.
 * @author Jahidul Pabel Islam
*/

use JPI\API;

include $_SERVER['DOCUMENT_ROOT'] . '/config.php';  // Copy config-sample.php and rename to config.php then fill in necessary constant

API\Router::performRequest();

// autoload function
function __autoload($class) {
	$parts = explode('\\', $class);
	$filename = end($parts);
	$file = "inc/$filename.php";
	require_once($file);
}