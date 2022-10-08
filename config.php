<?php

$config->debug = false;

$config->db_host = "overrideme";
$config->db_name = "overrideme";
$config->db_username = "overrideme";
$config->db_password = "overrideme";

// The secret key to use in Firebase's JWT
$config->portfolio_admin_secret_key = "overrideme";

// A list of other domains that can call this API
$config->allowed_domains = [
    "jahidulpabelislam.com",
    "cms.jahidulpabelislam.com",
];
