<?php

namespace App;

use App\Database\Connection;
use App\Database\Query;

trait DatabaseAware {

    /**
     * @var Connection
     */
    protected static $db;

    public static function getDB(): Connection {
        if (!static::$db) {
            $config = Config::get();
            static::$db = new Connection([
                "host" => $config->db_host,
                "database" => $config->db_name,
                "username" => $config->db_username,
                "password" => $config->db_password,
            ]);
        }

        return static::$db;
    }

    public static function getQuery(): Query {
        return new Query(static::getDB(), static::$tableName);
    }

}
