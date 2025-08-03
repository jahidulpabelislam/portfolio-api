<?php

declare(strict_types=1);

$this->db_host = "overrideme";
$this->db_name = "overrideme";
$this->db_username = "overrideme";
$this->db_password = "overrideme";

$this->portfolio_admin_secret_key = "overrideme";

$this->allowed_domains = [
    \JPI\App::get()::DOMAINS[$environment],
    "cms." . \JPI\App::get()::DOMAINS[$environment],
];
