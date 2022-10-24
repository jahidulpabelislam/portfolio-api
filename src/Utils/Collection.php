<?php

namespace App\Utils;

use JPI\Utils\Collection as BaseCollection;
use ArrayAccess;

class Collection extends BaseCollection implements Arrayable {

    public function toArray(): array {
        $array = [];

        foreach ($this->items as $key => $item) {
            if ($item instanceof Arrayable) {
                $item = $item->toArray();
            }

            $array[$key] = $item;
        }

        return $array;
    }

    protected static function getFromItem($item, string $key) {
        if (is_array($item)) {
            return $item[$key] ?? null;
        }

        if (is_object($item)) {
            if ($item instanceof ArrayAccess && isset($item[$key])) {
                return $item[$key];
            }

            if (isset($item->{$key})) {
                return $item->{$key};
            }

            if (method_exists($item, $key)) {
                return $item->{$key}();
            }

            if ($item instanceof Arrayable) {
                $array = $item->toArray();
                if (isset($array[$key])) {
                    return $array[$key];
                }
            }
        }

        return null;
    }
}
