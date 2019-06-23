<?php
/**
 * The base endpoint for all requests for this API.
 *
 * PHP version 7.1+
 *
 * @version 3.1.1
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2010-2019 JPI
 */

use JPI\API\Router;

if (!defined("ROOT")) {
    define("ROOT", rtrim($_SERVER["DOCUMENT_ROOT"], " /"));
}

require_once(ROOT . "/vendor/autoload.php");

$router = new Router();
$router->performRequest();
