<?php

namespace App\Entity;

use JPI\ORM\Entity\QueryBuilder;

interface SearchableInterface {

    public static function getSearchableColumns(): array;

    public static function addSearchToQuery(QueryBuilder $query, string $value): void;
}
