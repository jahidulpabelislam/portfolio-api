<?php

namespace App\Entity;

use App\Utils\ArrayCollection;
use App\Entity;

class Collection extends ArrayCollection {

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

    public function toArray(): array {
        $array = [];

        foreach ($this->items as $key => $entity) {
            $array[$key] = $entity->toArray();
        }

        return $array;
    }

    /**
     * @param $entity Entity
     * @param $key string
     * @param $default mixed
     * @return mixed
     */
    protected static function getFromItem($entity, $key, $default = null) {
        if ($key === "id") {
            return $entity->getId();
        }

        if (isset($entity->{$key})) {
            return $entity->{$key};
        }

        if (method_exists($entity, $key)) {
            return $entity->{$key}();
        }

        $array = $entity->toArray();
        return $array[$key] ?? $default;
    }
}
