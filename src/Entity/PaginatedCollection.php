<?php

namespace App\Entity;

class PaginatedCollection extends Collection {

    protected $totalCount;
    protected $limit;
    protected $page;

    public function __construct(array $items = [], int $totalCount = null, int $limit = null, int $page = null) {
        parent::__construct($items);
        $this->totalCount = $totalCount ?? null;
        $this->limit = $limit;
        $this->page = $page;
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
}
