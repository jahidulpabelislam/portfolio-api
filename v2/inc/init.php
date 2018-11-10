<?php
/*
 * Get all files need together
 * @author Jahidul Pabel Islam
*/

date_default_timezone_set("Europe/London");

// Using include to include all php files needed
include $_SERVER['DOCUMENT_ROOT'] . '/Config.php';  // Copy Config-sample.php and rename to Config.php then fill in necessary constant
include 'Database.php';
include 'Hasher.php'; // Copy v2/inc/Hasher-sample.php and rename to Hasher.php then update both the functions with your Hashing functionality
include 'Auth.php';  // Copy v2/inc/Auth-sample.php and rename to Auth.php then update all 3 functions with your Auth functionality
include 'Helper.php';
include 'API.php';
include 'Router.php';