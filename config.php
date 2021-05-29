<?php

use App\Config;

$config = Config::get();

$config->debug = false;

$config->db_host = "127.0.0.1";
$config->db_name = "jpi";
$config->db_username = "root";
$config->db_password = "";

// The secret key to use in Firebase's JWT
$config->portfolio_admin_secret_key = "changeme";

// A list of other domains that can call this API
$config->allowed_domains = [
    "jahidulpabelislam.com",
    "cms.jahidulpabelislam.com",
];
