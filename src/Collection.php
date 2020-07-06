<?php

namespace App;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;

class Collection implements Arrayable, ArrayAccess, Countable, IteratorAggregate {

    protected $items;
    protected $count;

    public function __construct(array $items = []) {
        $this->items = $items;
        $this->count = count($items);
    }

    protected function resetCount() {
        $this->count = count($this->items);
    }

    public function set($key, $item) {
        if ($key === null) {
            $this->items[] = $item;
        }
        else {
            $this->items[$key] = $item;
        }
        $this->resetCount();
    }

    public function add($item) {
        $this->set(null, $item);
    }

    public function remove($key) {
        unset($this->items[$key]);
        $this->resetCount();
    }

    protected function doesItemExist($key) {
        return array_key_exists($key, $this->items);
    }

    public function get($key) {
        return $this->items[$key];
    }

    public function getItems(): array {
        return $this->items;
    }

    public function toArray(): array {
        $array = [];

        foreach ($this->items as $key => $item) {
            if ($item instanceof Arrayable) {
                $array[$key] = $item->toArray();
            }
            else {
                $array[$key] = $item;
            }
        }

        return $array;
    }

    // ArrayAccess //

    public function offsetExists($offset): bool {
        return $this->doesItemExist($offset);
    }

    public function offsetGet($offset) {
        return $this->get($offset);
    }

    public function offsetSet($offset, $item) {
        $this->set($offset, $item);
    }

    public function offsetUnset($offset) {
        $this->remove($offset);
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
