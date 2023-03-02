<?php

namespace App\Entity;

use JPI\ORM\Entity\QueryBuilder;

interface FilterableInterface {

    public static function getFilterableColumns(): array;

    public static function addFiltersToQuery(QueryBuilder $query, array $filters): void;
}
