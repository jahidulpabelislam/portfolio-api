<?php

namespace App\Database;

use App\Collection as BaseCollection;

class Collection extends BaseCollection {

    protected $totalCount;
    protected $limit;
    protected $page;

    public function __construct(array $items = [], int $totalCount = null, int $limit = null, int $page = null) {
        parent::__construct($items);
        $this->totalCount = $totalCount ?? $this->count;
        $this->limit = $limit;
        $this->page = $page;
    }

    public function getTotalCount(): int {
        return $this->totalCount;
    }

    public function getLimit(): ?int {
        return $this->limit;
    }

    public function getPage(): ?int {
        return $this->page;
    }

}
