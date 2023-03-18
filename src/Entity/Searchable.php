<?php

namespace App\Entity;

use JPI\ORM\Entity\QueryBuilder;
use JPI\Database\Query\Clause\Where\OrCondition as WhereOrCondition;

trait Searchable {

    public static function getSearchableColumns(): array {
        return static::$searchableColumns ?? array_keys(static::$defaultColumns);
    }

    public static function addSearchToQuery(QueryBuilder $query, string $value): void {
        $words = explode(" ", $value);

        $query->params([
            "search" => "%" . implode("%", $words) . "%",
            "searchReversed" => "%" . implode("%", array_reverse($words)) . "%",
        ]);

        $where = new WhereOrCondition($query);
        foreach (static::getSearchableColumns() as $column) {
            $where
                ->where("$column LIKE :search")
                ->where("$column LIKE :searchReversed")
            ;
        }

        $query->where((string)$where);
    }
}
