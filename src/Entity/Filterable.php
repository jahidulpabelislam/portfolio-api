<?php

namespace App\Entity;

trait Filterable {

    protected static function getFilterableColumns(): array {
        return static::$filterableColumns ?? array_keys(static::$defaultColumns);
    }

    /**
     * Generate where(s) to filter entities by values.
     *
     * @param $filters array The fields to search for within searchable columns (if any)
     * @return array [string[], array] Generated SQL where clause(s) and an associative array containing any params for query
     */
    public static function buildQueryFromFilters(array $filters): array {
        $where = [];
        $params = [];
        foreach (static::getFilterableColumns() as $column) {
            if (!empty($filters[$column])) {
                $where[] = "{$column} = :{$column}";
                $params[$column] = $filters[$column];
            }
        }

        return [
            "where" => $where,
            "params" => $params,
        ];
    }

}
