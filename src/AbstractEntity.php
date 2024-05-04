<?php

declare(strict_types=1);

namespace App;

use JPI\CRUD\API\AbstractEntity as BaseEntity;
use JPI\Database;
use PDO;

abstract class AbstractEntity extends BaseEntity {

    protected static ?Database $database = null;

    public static function getDatabase(): Database {
        if (!static::$database) {
            $config = Config::get();

            static::$database = new Database(
                "mysql:host={$config->db_host};dbname={$config->db_name};charset-UTF-8",
                $config->db_username,
                $config->db_password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]
            );
        }

        return static::$database;
    }
}
