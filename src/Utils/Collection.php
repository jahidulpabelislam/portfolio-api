<?php

namespace App\Utils;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;

class Collection implements Arrayable, ArrayAccess, Countable, IteratorAggregate {

    protected $items;
    protected $count = null;

    public function __construct(array $items = []) {
        $this->items = $items;
    }

    protected function resetCount() {
        $this->count = null;
    }

    public function set($key, $item) {
        $this->items[$key] = $item;
        $this->resetCount();
    }

    public function add($item) {
        $this->items[] = $item;
        $this->resetCount();
    }

    public function removeByKey($key) {
        unset($this->items[$key]);
        $this->resetCount();
    }

    protected function doesKeyExist($key) {
        return array_key_exists($key, $this->items);
    }

    public function get($key, $default = null) {
        return $this->items[$key] ?? $default;
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
        return $this->doesKeyExist($offset);
    }

    public function offsetGet($offset) {
        return $this->get($offset);
    }

    public function offsetSet($offset, $item) {
        if ($offset === null) {
            $this->add($item);
        }
        else {
            $this->set($offset, $item);
        }
    }

    public function offsetUnset($offset) {
        $this->removeByKey($offset);
    }

    // IteratorAggregate //

    public function getIterator(): ArrayIterator {
        return new ArrayIterator($this->items);
    }

    // Countable //

    public function count(): int {
        if ($this->count === null) {
            $this->count = count($this->items);
        }

        return $this->count;
    }

    protected static function getFromItem($item, $key, $default = null) {
        $value = $default;
        if (is_object($item)) {
            if (isset($item->{$key})) {
                $value = $item->{$key};
            }
            else if (method_exists($item, $key)) {
                $value = $item->{$key}();
            }
            else if ($item instanceof Arrayable) {
                $array = $item->toArray();
                if (isset($array[$key])) {
                    $value = $array[$key];
                }
            }
        }
        else if ((is_array($item) || $item instanceof ArrayAccess) && isset($item[$key])) {
            $value = $item[$key];
        }

        return $value;
    }

    public function pluck($toPluck, $keyedBy = null): Collection {
        $plucked = new Collection();

        foreach ($this->items as $item) {
            $value = static::getFromItem($item, $toPluck);

            if ($keyedBy) {
                $keyValue = static::getFromItem($item, $keyedBy);
                $plucked->set($keyValue, $value);
            }
            else {
                $plucked->add($value);
            }
        }

        return $plucked;
    }

    public function groupBy($key): Collection {
        $collection = new Collection();

        foreach ($this->items as $item) {
            $value = static::getFromItem($item, $key);

            if (!isset($collection[$value])) {
                $collection->set($value, new static([$item]));
            }
            else {
                $collection[$value]->add($item);
            }
        }

        return $collection;
    }

}
