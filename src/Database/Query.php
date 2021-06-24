<?php

/**
 * The middle man for querying the database from an Entity.
 * Builds the SQL queries and executes/runs them and returns in appropriate format.
 */

namespace App\Database;

class Query {

    protected $connection;
    protected $table;
    protected $debug;

    public function __construct(Connection $connection, string $table, bool $debug = false) {
        $this->connection = $connection;
        $this->table = $table;
        $this->debug = $debug;
    }

    /**
     * @param $parts array
     * @param $params array|null
     * @param $function string
     * @return array[]|array|int|null
     */
    private function execute(array $parts, ?array $params, string $function = "execute") {
        $query = implode($this->debug ? "\n" : " ", $parts);
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
    private function arrayToQueryString($value, string $separator = ", "): string {
        if (is_array($value)) {
            if (count($value) === 1) {
                $value = array_shift($value);
            } else {
                if ($this->debug) {
                    $separator .= "\n\t";
                }
                $value = implode($separator, $value);
                if ($this->debug) {
                    $value = "\n\t" . $value;
                }
            }
        }

        if (is_string($value)) {
            return $value;
        }

        return "";
    }

    /**
     * Try and force value as an array if not already
     *
     * @param $value array|string|null
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
     * @param $where string[]|string|int|null
     * @param $params array|null
     * @return array [string|null, array|null]
     */
    private function generateWhereClause($where, ?array $params): array {
        if ($where) {
            if (is_numeric($where)) {
                $params = static::initArray($params);
                $params["id"] = (int)$where;
                $where = "id = :id";
            }

            $where = $this->arrayToQueryString($where," AND ");

            return [
                "WHERE $where",
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
     * @param $params array|null
     * @param $orderBy string[]|string|null
     * @param $limit int|null
     * @param $page int|null
     * @return array [array, array|null]
     */
    protected function generateSelectQuery(
        string $table,
        $columns = "*",
        $where = null,
        ?array $params = [],
        $orderBy = null,
        ?int $limit = null,
        ?int $page = null
    ): array {
        $columns = $columns ?: "*";
        $columns = $this->arrayToQueryString($columns);

        $sqlParts = [
            "SELECT $columns",
            "FROM $table",
        ];

        [$whereClause, $params] = $this->generateWhereClause($where, $params);
        if ($whereClause) {
            $sqlParts[] = $whereClause;

            if (is_numeric($where)) {
                $sqlParts[] = "LIMIT 1";
                return [$sqlParts, $params];
            }
        }

        $orderBy = $this->arrayToQueryString($orderBy);
        if ($orderBy) {
            $sqlParts[] = "ORDER BY $orderBy";
        }

        if ($limit) {
            $limitPart = "LIMIT $limit";

            // Generate a offset, using limit & page values
            if ($page > 1) {
                $offset = $limit * ($page - 1);
                $limitPart .= " OFFSET $offset";
            }

            $sqlParts[] = $limitPart;
        }

        return [$sqlParts, $params];
    }

    /**
     * Get the page to use for a SQL query
     * Can specify the page and it will make sure it is valid
     *
     * @param $page int|string|null
     * @return int|null
     */
    protected static function getPage($page = null): ?int {
        if (is_numeric($page)) {
            $page = (int)$page;
        }

        // If invalid use page 1
        if (!$page || $page < 1) {
            $page = 1;
        }

        return $page;
    }

    /**
     * @param $columns string[]|string|null
     * @param $where string[]|string|int|null
     * @param $params array|null
     * @param $orderBy string[]|string|null
     * @param $limit int|null
     * @param $page int|string|null
     * @return Collection|array|null
     */
    public function select(
        $columns = "*",
        $where = null,
        ?array $params = null,
        $orderBy = null,
        ?int $limit = null,
        $page = null
    ) {
        $page = $limit ? static::getPage($page) : null;

        [$sqlParts, $params] = $this->generateSelectQuery(
            $this->table,
            $columns,
            $where,
            $params,
            $orderBy,
            $limit,
            $page
        );

        if (($where && is_numeric($where)) || $limit === 1) {
            return $this->execute($sqlParts, $params, "getOne");
        }

        $rows = $this->execute($sqlParts, $params, "getAll");

        $totalCount = null;
        if ($limit) {
            $count = count($rows);

            /**
             * Do a DB query to get total count if:
             *    - none found on a specific page than 1
             *    - count is the limit
             * Else we can work out the total
             */
            if ((!$count && $page > 1) || $count === $limit) {
                // Replace the SELECT part in query with a simple count
                $sqlParts[0] = "SELECT COUNT(*) as count";

                array_pop($sqlParts); // Remove the LIMIT part in query

                // Remove the ORDER BY part in query if added
                if ($orderBy) {
                    array_pop($sqlParts);
                }

                $row = $this->execute($sqlParts, $params, "getOne");
                $totalCount = $row["count"] ?? 0;
            }
            else {
                $totalCount = $limit * ($page - 1) + $count;
            }
        }

        return new Collection($rows, $totalCount, $limit, $page);
    }

    /**
     * @param $where string[]|string|int|null
     * @param $params array|null
     * @return int
     */
    public function count($where = null, ?array $params = null): int {
        $row = $this->select("COUNT(*) as total_count", $where, $params, null, 1);
        return $row["total_count"] ?? 0;
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
        foreach (array_keys($values) as $column) {
            $valuesQueries[] = "$column = :$column";
        }
        $valuesQuery = $this->arrayToQueryString($valuesQueries);

        $sqlParts = [
            ($isInsert ? "INSERT INTO" : "UPDATE") . " $this->table",
            "SET $valuesQuery",
        ];

        if (!$isInsert) {
            [$whereClause, $params] = $this->generateWhereClause($where, $params);
            if ($whereClause) {
                $sqlParts[] = $whereClause;
            }
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
     * @param $where string[]|string|int|null
     * @param $params array|null
     * @return int
     */
    public function delete($where = null, ?array $params = null): int {
        $sqlParts = ["DELETE FROM $this->table"];

        [$whereClause, $params] = $this->generateWhereClause($where, $params);
        if ($whereClause) {
            $sqlParts[] = $whereClause;
        }

        $rowsDeleted = $this->execute($sqlParts, $params);

        return $rowsDeleted;
    }
}
