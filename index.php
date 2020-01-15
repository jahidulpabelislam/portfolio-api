<?php
/**
 * The base endpoint for all requests for this API.
 *
 * PHP version 7.1+
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2010-2020 JPI
 */

require_once(__DIR__ . "/bootstrap.php");

use App\Router;

$router = new Router();
$router->performRequest();
