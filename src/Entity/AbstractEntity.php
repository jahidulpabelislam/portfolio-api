<?php

namespace App\Entity;

use App\Core;
use JPI\Database\Connection;
use JPI\ORM\Entity as BaseEntity;
use JPI\ORM\Entity\Collection;
use JPI\Utils\Arrayable;

abstract class AbstractEntity extends BaseEntity implements Arrayable {

    protected static $dbConnection = null;

    public static function getDatabaseConnection(): Connection {
        if (!static::$dbConnection) {
            $config = Core::get()->getConfig();
            static::$dbConnection = new Connection([
                "host" => $config->db_host,
                "database" => $config->db_name,
                "username" => $config->db_username,
                "password" => $config->db_password,
            ]);
        }

        return static::$dbConnection;
    }

    /**
     * Always return a collection if multiple.
     *
     * @param string[]|string|int|null $where
     * @param array|null $params
     * @param int|string|null $limit
     * @param int|string|null $page
     * @return \JPI\ORM\Entity\Collection|static|null
     */
    public static function get($where = null, ?array $params = null, $limit = null, $page = null) {
        $result = parent::get($where, $params, $limit, $page);

        if (
            $result instanceof Collection ||
            $result === null ||
            $limit == 1 ||
            ($where && is_numeric($where))
        ) {
            return $result;
        }

        return new Collection($result);
    }

    public function toArray(): array {
        $array = [
            "id" => $this->getId(),
        ];

        foreach ($this->columns as $column => $value) {
            $array[$column] = $value;
        }

        return $array;
    }
}
