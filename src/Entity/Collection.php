<?php

namespace App\Entity;

use App\Arrayable;
use App\Database\Collection as DBCollection;

class Collection extends DBCollection {

    public function toArray(): array {
        $array = [];

        foreach ($this->items as $key => $entity) {
            $array[$key] = $entity->toArray();
        }

        return $array;
    }

}
