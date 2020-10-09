<?php

namespace App\Utils;

class ArrayCollection extends Collection {

    public function toArray(): array {
        return $this->items;
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
