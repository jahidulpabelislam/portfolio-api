<?php

declare(strict_types=1);

$config->db = [
    "host" => "overrideme",
    "name" => "overrideme",
    "username" => "overrideme",
    "password" => "overrideme",
];

$config->portfolio_admin_secret_key = "overrideme";

$config->allowed_domains = [
    \JPI\App::get()::DOMAINS[$environment],
    "cms." . \JPI\App::get()::DOMAINS[$environment],
];
