<?php

namespace App\Entity;

trait Searchable {

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

        if ($searchValue) {
            // Split each word in search
            $searchWords = explode(" ", $searchValue);
            $searchString = "%" . implode("%", $searchWords) . "%";

            $searchesReversed = array_reverse($searchWords);
            $searchStringReversed = "%" . implode("%", $searchesReversed) . "%";

            $whereParams["search"] = $searchString;
            $whereParams["searchReversed"] = $searchStringReversed;
        }

        $searchWhereClauses = [];
        foreach (static::$searchableColumns as $column) {
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
