<?php
/*
 * The base endpoint for all requests for this API.
 *
 * PHP version 7
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version 3
 * @link https://github.com/jahidulpabelislam/portfolio-api/
 * @copyright 2014-2018 JPI
*/

require_once($_SERVER["DOCUMENT_ROOT"] .  "/vendor/autoload.php");

use JPI\API\Router;

Router::performRequest();