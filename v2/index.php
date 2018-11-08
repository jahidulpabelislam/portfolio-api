<?php
/*
 * The base endpoint for all request for this API.
 * @author Jahidul Pabel Islam
*/

use JPI\API;

// Include initialisation file to include all files needed for API operations
include 'inc/init.php';

API\Router::performRequest();