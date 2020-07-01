<?php

namespace App;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class Collection implements Countable, IteratorAggregate {

    protected $items;
    protected $count;

    public function __construct(array $items) {
        $this->items = $items;
        $this->count = count($items);
    }

    public function getItems(): array {
        return $this->items;
    }

    /**
     * IteratorAggregate
     */

    public function getIterator(): ArrayIterator {
        return new ArrayIterator($this->items);
    }

    /**
     * Countable
     */

    public function count(): int {
        return $this->count;
    }

}
