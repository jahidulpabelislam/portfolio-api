<?php

namespace App\Entity;

use App\Database\Collection as DBCollection;
use App\Entity;

class Collection extends DBCollection {

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
