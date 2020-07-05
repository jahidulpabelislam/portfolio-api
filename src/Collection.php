<?php

namespace App;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;

class Collection implements ArrayAccess, Countable, IteratorAggregate {

    protected $items;
    protected $count;

    public function __construct(array $items = []) {
        $this->items = $items;
        $this->count = count($items);
    }

    public function getItems(): array {
        return $this->items;
    }

    protected function resetCount() {
        $this->count = count($this->items);
    }

    protected function doesItemExist($key) {
        return array_key_exists($key, $this->items);
    }

    public function setItem($key, $item) {
        if ($key === null) {
            $this->items[] = $item;
        }
        else {
            $this->items[$key] = $item;
        }
        $this->resetCount();
    }

    public function addItem($item) {
        $this->setItem(null, $item);
    }

    public function getItem($key) {
        return $this->items[$key];
    }

    public function removeItem($key) {
        unset($this->items[$key]);
        $this->resetCount();
    }

    // ArrayAccess //

    public function offsetExists($offset): bool {
        return $this->doesItemExist($offset);
    }

    public function offsetGet($offset) {
        return $this->getItem($offset);
    }

    public function offsetSet($offset, $item) {
        $this->setItem($offset, $item);
    }

    public function offsetUnset($offset) {
        $this->removeItem($offset);
    }

    // IteratorAggregate //

    public function getIterator(): ArrayIterator {
        return new ArrayIterator($this->items);
    }

    // Countable //

    public function count(): int {
        return $this->count;
    }

}
