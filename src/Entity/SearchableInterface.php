<?php

namespace App\Entity;

interface SearchableInterface {

    public static function getSearchableColumns(): array;

    /**
     * Build where clause(s) for like searches on searchable columns
     *
     * @param $value string
     * @return array
     */
    public static function buildSearchQuery(string $value): array;
}
