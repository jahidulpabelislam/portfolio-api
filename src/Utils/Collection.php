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

            $array[$key] = (array) $item;
        }

        return $array;
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
}
