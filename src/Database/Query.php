<?php
/**
 * The middle man for querying the database from an Entity.
 * Builds the SQL queries and executes/runs them and returns in appropriate format.
 *
 * PHP version 7.1+
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @since Class available since Release: v3.8.5
 * @copyright 2010-2020 JPI
 */

namespace App\Database;

class Query {

    protected $connection;
    protected $table;

    public function __construct(Connection $connection, string $table) {
        $this->connection = $connection;
        $this->table = $table;
    }

    private function execute(array $parts, ?array $params, string $function = "execute") {
        $query = implode("\n", $parts);
        $query .= ";";
        return $this->connection->{$function}($query, $params);
    }

    /**
     * Convenient function to pluck/get out the single value from an array if it's the only value.
     * Then build a string value if an array.
     *
     * @param $value string[]|string|null
     * @param $separator string
     * @return string
     */
    private static function arrayToQueryString($value, string $separator = ",\n\t"): string {
        if ($value && is_array($value) && count($value) === 1) {
            $value = array_shift($value);
        }

        if (is_array($value)) {
            $value = "\n\t" . implode($separator, $value);
        }

        if (!$value || !is_string($value)) {
            return "";
        }

        return $value;
    }

    /**
     * Try and force value as an array if not already
     *
     * @param $value array|mixed
     * @return array
     */
    private static function initArray($value): array {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            return [$value];
        }

        return [];
    }

    /**
     * @param $where array|string|int|null
     * @param $params array|null
     * @return array[string, array|null]
     */
    private static function generateWhereClause($where, ?array $params): array {
        if ($where) {
            if (is_numeric($where)) {
                $params = static::initArray($params);
                $params["id"] = $where;
                $where = "id = :id";
            }

            $where = static::arrayToQueryString($where, "\n\tAND ");

            return [
                "WHERE {$where}",
                $params,
            ];
        }

        return [
            null,
            $params,
        ];
    }

    /**
     * @param $table string
     * @param $columns string[]|string|null
     * @param $where string[]|string|int|null
     * @param $orderBy string[]|string|null
     * @param $params array|null
     * @param $limit int|string|null
     * @param $page int|string|null
     * @return array[string, array|null]
     */
    protected static function generateSelectQuery(string $table, $columns = "*", $where = null, $orderBy = null, ?array $params = [], ?int $limit = null, ?int $page = null): array {
        $columns = $columns?: "*";
        $columns = static::arrayToQueryString($columns);

        $sqlParts = [
            "SELECT {$columns}",
            "FROM {$table}",
        ];

        [$whereClause, $params] = static::generateWhereClause($where, $params);

        if ($whereClause) {
            $sqlParts[] = $whereClause;

            if (is_numeric($where)) {
                $sqlParts[] = "LIMIT 1";
                return [$sqlParts, $params];
            }
        }

        $orderBy = static::arrayToQueryString($orderBy);
        if ($orderBy) {
            $sqlParts[] = "ORDER BY {$orderBy}";
        }

        if ($limit) {
            $limitPart = "LIMIT {$limit}";

            // Generate a offset, using limit & page values
            if ($page > 1) {
                $offset = $limit * ($page - 1);
                $limitPart .= " OFFSET {$offset}";
            }

            $sqlParts[] = $limitPart;
        }

        return [$sqlParts, $params];
    }

    /**
     * @param $columns string[]|string|null
     * @param $where string[]|string|int|null
     * @param $orderBy string[]|string|null
     * @param $params array|null
     * @param $limit int|string|null
     * @param $page int|string|null
     * @return array|null
     */
    public function select($columns = "*", $where = null, $orderBy = null, ?array $params = null, ?int $limit = null, ?int $page = null): ?array {
        [$sqlParts, $params] = static::generateSelectQuery($this->table, $columns, $where, $orderBy, $params, $limit, $page);

        if (($where && is_numeric($where)) || $limit === 1) {
            return $this->execute($sqlParts, $params, "getOne");
        }

        return $this->execute($sqlParts, $params, "getAll");
    }

    /**
     * @param $where string[]|string|int|null
     * @param $params array|null
     * @return int
     */
    public function count($where = null, ?array $params = null): int {
        $row = $this->select("COUNT(*) as total_count", $where, null, $params, 1);
        return $row['total_count'] ?? 0;
    }

    /**
     * @param $values array
     * @param $where string[]|string|int|null
     * @param $params array|null
     * @param $isInsert bool
     * @return int
     */
    protected function insertOrUpdate(array $values, $where = null, ?array $params = null, bool $isInsert = true): int {
        $params = static::initArray($params);
        $params = array_merge($params, $values);

        $valuesQueries = [];
        foreach ($values as $column => $value) {
            $valuesQueries[] = "{$column} = :{$column}";
        }
        $valuesQuery = static::arrayToQueryString($valuesQueries);

        $sqlParts = [
            ($isInsert ? "INSERT INTO " : "UPDATE ") . $this->table,
            "SET {$valuesQuery}",
        ];

        [$whereClause, $params] = static::generateWhereClause($where, $params);
        if ($whereClause) {
            $sqlParts[] = $whereClause;
        }

        return $this->execute($sqlParts, $params);
    }

    /**
     * @param $values array
     * @return int|null
     */
    public function insert(array $values): ?int {
        $rowsAffected = $this->insertOrUpdate($values);
        if ($rowsAffected > 0) {
            return $this->connection->getLastInsertedId();
        }

        return null;
    }

    /**
     * @param $values array
     * @param $where string[]|string|int|null
     * @param $params array|null
     * @return int
     */
    public function update(array $values, $where = null, ?array $params = null): int {
        return $this->insertOrUpdate($values, $where, $params, false);
    }

    /**
     * @param $where array|string|int|null
     * @param $params array|null
     * @return int
     */
    public function delete($where = null, ?array $params = null): int {
        $sqlParts = ["DELETE FROM {$this->table}"];

        [$whereClause, $params] = static::generateWhereClause($where, $params);
        if ($whereClause) {
            $sqlParts[] = $whereClause;
        }

        $rowsDeleted = $this->execute($sqlParts, $params);

        return $rowsDeleted;
    }

}
