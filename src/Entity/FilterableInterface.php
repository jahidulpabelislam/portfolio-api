<?php

namespace App\Entity;

interface FilterableInterface {

    public static function getFilterableColumns(): array;

    /**
     * Generate where(s) to filter entities by values.
     *
     * @param $filters array The fields to search for within searchable columns (if any)
     * @return array [string[], array] Generated SQL where clause(s) and an associative array containing any params for query
     */
    public static function buildQueryFromFilters(array $filters): array;
}
