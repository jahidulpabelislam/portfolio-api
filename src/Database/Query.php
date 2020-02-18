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

        if (!$value && !is_string($value)) {
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
        if (is_array($value))  {
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
                "WHERE {$where}\n",
                $params,
            ];
        }

        return [
            null,
            $params
        ];
    }

    /**
     * @param $table string
     * @param $select string[]|string|null
     * @param $where string[]|string|int|null
     * @param $orderBy string[]|string|null
     * @param $params array|null
     * @param $limit int|string|null
     * @param $page int|string|null
     * @return array[string, array|null]
     */
    protected static function generateSelectQuery(string $table, $select = "*", $where = null, $orderBy = null, ?array $params = [], $limit = null, $page = null): array {
        $select = $select?: "*";
        $select = static::arrayToQueryString($select);

        $query = "SELECT {$select}\n"
               . "FROM {$table}\n";

        [$whereClause, $params] = static::generateWhereClause($where, $params);

        if ($whereClause) {
            $query .= $whereClause;

            if (is_numeric($where)) {
                $query .= "LIMIT 1;";
                return [$query, $params];
            }
        }

        $orderBy = static::arrayToQueryString($orderBy);
        if ($orderBy) {
            $query .= "ORDER BY {$orderBy}\n";
        }

        if ($limit) {
            $query .= "LIMIT {$limit}";

            // Generate a offset, using limit & page values
            if ($page > 1) {
                $offset = $limit * ($page - 1);
                $query .= " OFFSET {$offset}";
            }
        }

        return [trim($query) . ";", $params];
    }

    /**
     * @param $select string[]|string|null
     * @param $where string[]|string|int|null
     * @param $orderBy string[]|string|null
     * @param $params array|null
     * @param $limit int|string|null
     * @param $page int|string|null
     * @return array|null
     */
    public function select($select = "*", $where = null, $orderBy = null, ?array $params = null, ?int $limit = null, ?int $page = null): ?array {
        [$query, $params] = static::generateSelectQuery($this->table, $select, $where, $orderBy, $params, $limit, $page);

        if (($where && is_numeric($where)) || $limit == 1) {
            return $this->connection->getOne($query, $params);
        }

        return $this->connection->getAll($query, $params);
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

        $query = $isInsert ? "INSERT INTO " : "UPDATE ";
        $query .= "{$this->table}\n";
        $query .= "SET {$valuesQuery}";

        [$whereClause, $params] = static::generateWhereClause($where, $params);
        if ($whereClause) {
            $query .= "\n{$whereClause}";
        }
        $query = trim($query) . ";";

        return $this->connection->execute($query, $params);
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
        $query = "DELETE FROM {$this->table}";

        [$whereClause, $params] = static::generateWhereClause($where, $params);
        if ($whereClause) {
            $query .= "\n{$whereClause}";
        }

        $query = trim($query) . ";";

        $rowsDeleted = $this->connection->execute($query, $params);

        return $rowsDeleted;
    }

}
