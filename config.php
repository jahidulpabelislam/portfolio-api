<?php

use App\Config;

Config::get()->debug = false;

Config::get()->db_host = "127.0.0.1";
Config::get()->db_name = "jpi";
Config::get()->db_username = "root";
Config::get()->db_password = "";

// The secret key to use in Firebase's JWT
Config::get()->portfolio_admin_secret_key = "changeme";

// A list of other domains that can call this API
Config::get()->allowed_domains = [
    "jahidulpabelislam.com",
    "cms.jahidulpabelislam.com",
];
