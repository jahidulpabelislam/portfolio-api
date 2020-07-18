<?php

namespace App\Entity;

trait Searchable {

    protected static function getSearchableColumns(): array {
        if (isset(static::$searchableColumns)) {
            return static::$searchableColumns;
        }

        return array_keys(static::$defaultColumns);
    }

    /**
     * Build where clause(s) for like searches on searchable columns
     *
     * @param $search string
     * @return array
     */
    public static function buildSearchQuery(string $search): array {
        $words = explode(" ", $search);
        $wordsReversed = array_reverse($words);

        $searchFormatted = "%" . implode("%", $words) . "%";
        $searchFormattedReversed = "%" . implode("%", $wordsReversed) . "%";

        $params = [
            "search" => $searchFormatted,
            "searchReversed" => $searchFormattedReversed,
        ];

        $where = [];
        foreach (static::getSearchableColumns() as $column) {
            $where[] = "{$column} LIKE :search";
            $where[] = "{$column} LIKE :searchReversed";
        }

        return [
            "where" => ["(\n\t\t" . implode("\n\t\tOR ", $where) . "\n\t)"],
            "params" => $params,
        ];
    }

}
