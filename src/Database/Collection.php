<?php

namespace App\Database;

use App\Utils\Collection as BaseCollection;

class Collection extends BaseCollection {

    protected $totalCount;
    protected $limit;
    protected $page;

    public function __construct(array $items = [], int $totalCount = null, int $limit = null, int $page = null) {
        parent::__construct($items);
        $this->totalCount = $totalCount ?? null;
        $this->limit = $limit;
        $this->page = $page;
    }

    public function toArray(): array {
        return $this->items;
    }

    public function getTotalCount(): int {
        return $this->totalCount ?? $this->count();
    }

    public function getLimit(): ?int {
        return $this->limit;
    }

    public function getPage(): ?int {
        return $this->page;
    }

    /**
     * @param $item array
     * @param $key string
     * @param $default mixed
     * @return string|int|float|null
     */
    protected static function getFromItem($item, $key, $default = null) {
        return $item[$key] ?? $default;
    }

}
