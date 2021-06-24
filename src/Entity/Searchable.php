<?php

namespace App\Entity;

trait Searchable {

    protected static function getSearchableColumns(): array {
        return static::$searchableColumns ?? array_keys(static::$defaultColumns);
    }

    /**
     * Build where clause(s) for like searches on searchable columns
     *
     * @param $value string
     * @return array
     */
    public static function buildSearchQuery(string $value): array {
        $words = explode(" ", $value);
        $wordsReversed = array_reverse($words);

        $params = [
            "search" => "%" . implode("%", $words) . "%",
            "searchReversed" => "%" . implode("%", $wordsReversed) . "%",
        ];

        $where = [];
        foreach (static::getSearchableColumns() as $column) {
            $where[] = "$column LIKE :search";
            $where[] = "$column LIKE :searchReversed";
        }

        return [
            "where" => ["(\n\t\t" . implode("\n\t\tOR ", $where) . "\n\t)"],
            "params" => $params,
        ];
    }
}
