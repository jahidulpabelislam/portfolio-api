<?php
/**
 * The base endpoint for all requests for this API.
 *
 * PHP version 7
 *
 * @version 3.1.1
 * @link https://github.com/jahidulpabelislam/portfolio-api/
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2010-2019 JPI
 */

if (!defined("ROOT")) {
    define("ROOT", rtrim($_SERVER["DOCUMENT_ROOT"], " /"));
}

require_once(ROOT . "/vendor/autoload.php");

use JPI\API\Router;

$router = new Router();
$router->performRequest();
