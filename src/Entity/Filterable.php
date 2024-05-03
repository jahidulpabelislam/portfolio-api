<?php

declare(strict_types=1);

namespace App\Entity;

use JPI\ORM\Entity\QueryBuilder;

trait Filterable {

    public static function getFilterableColumns(): array {
        return static::$filterableColumns ?? static::getColumns();
    }

    public static function addFiltersToQuery(QueryBuilder $query, array $filters): void {
        foreach (static::getFilterableColumns() as $column) {
            if (array_key_exists($column, $filters)) {
                $query->where($column, "=", $filters[$column]);
            }
        }
    }
}
