<?php

namespace App\Entity;

use App\Core;
use JPI\Database;
use JPI\ORM\Entity as BaseEntity;
use JPI\ORM\Entity\Collection;
use JPI\Utils\Arrayable;
use PDO;

abstract class AbstractEntity extends BaseEntity implements Arrayable {

    protected static $database = null;

    public static function getDatabase(): Database {
        if (!static::$database) {
            $config = Core::get()->getConfig();

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
