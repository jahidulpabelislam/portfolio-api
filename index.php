<?php

/**
 * The base endpoint for all requests for this API.
 */

require_once __DIR__ . "/bootstrap.php";

use App\Core;

$core = Core::get();
$core->handleRequest();
