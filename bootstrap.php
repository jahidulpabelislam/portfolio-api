<?php

define("ROOT", __DIR__);

require_once __DIR__ . "/vendor/autoload.php";

require_once __DIR__ . "/config.php";

if (file_exists(__DIR__ . "/config.local.php")) {
    require_once __DIR__ . "/config.local.php";
}
