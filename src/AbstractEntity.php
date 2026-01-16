<?php

declare(strict_types=1);

namespace App;

use JPI\App;
use JPI\CRUD\API\AbstractEntity as BaseEntity;
use JPI\Database;
use PDO;

abstract class AbstractEntity extends BaseEntity {

    protected static ?Database $database = null;

    public static function getDatabase(): Database {
        if (!static::$database) {
            $config = App::get()->config()->db;

            static::$database = new Database(
                "mysql:host={$config->host};dbname={$config->name};charset-UTF-8",
                $config->username,
                $config->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]
            );
        }

        return static::$database;
    }
}
