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
     * @inheritDoc
     *
     * Add in where clauses for like searches on searchable columns
     *
     * @param array $params
     * @return array|null
     */
    public static function generateWhereClausesFromParams(array $params): ?array {
        $result = parent::generateWhereClausesFromParams($params);

        $searchValue = $params["search"] ?? null;

        if (!$searchValue) {
            return $result;
        }

        $whereClauses = $result["where"];
        $whereParams = $result["params"];

        // Split each word in search
        $searchWords = explode(" ", $searchValue);
        $searchString = "%" . implode("%", $searchWords) . "%";

        $searchesReversed = array_reverse($searchWords);
        $searchStringReversed = "%" . implode("%", $searchesReversed) . "%";

        $whereParams["search"] = $searchString;
        $whereParams["searchReversed"] = $searchStringReversed;

        $searchWhereClauses = [];
        foreach (static::getSearchableColumns() as $column) {
            $searchWhereClauses[] = "{$column} LIKE :search";
            $searchWhereClauses[] = "{$column} LIKE :searchReversed";
        }

        array_unshift($whereClauses, "(\n\t\t" . implode("\n\t\tOR ", $searchWhereClauses) . "\n\t)");

        return [
            "where" => $whereClauses,
            "params" => $whereParams,
        ];
    }

}
