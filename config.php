<?php

if (!defined("API_VERSION")) {
    define("API_VERSION", "3");
}

// The Host/IP of database server
if (!defined("DB_HOST")) {
    define("DB_HOST", "127.0.0.1");
}

// Database name to use in server
if (!defined("DB_NAME")) {
    define("DB_NAME", "jpi");
}

// Username to database
if (!defined("DB_USERNAME")) {
    define("DB_USERNAME", "root");
}

// Password for the user above
if (!defined("DB_PASSWORD")) {
    define("DB_PASSWORD", "");
}

// The secret key to use in Firebase's JWT
if (!defined("PORTFOLIO_ADMIN_SECRET_KEY")) {
    define("PORTFOLIO_ADMIN_SECRET_KEY", "changeme");
}

// A list of other domains that can call this API
if (!defined("ALLOWED_DOMAINS")) {
    define("ALLOWED_DOMAINS",  [
        "jahidulpabelislam.com",
        "cms.jahidulpabelislam.com",
    ]);
}
