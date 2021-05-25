<?php

define("ROOT", __DIR__);

if (file_exists(__DIR__ . "/config.local.php")) {
    require_once __DIR__ . "/config.local.php";
}

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/vendor/autoload.php";
