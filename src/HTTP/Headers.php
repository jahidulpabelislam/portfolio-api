<?php

namespace App\HTTP;

use App\Utils\ArrayCollection;

class Headers extends ArrayCollection {

    public function __construct(array $items = []) {
        foreach ($items as $header => $value) {
            if (!is_array($value)) {
                $items[$header] = [$value];
            }
        }

        parent::__construct($items);
    }

    public function set($header, $value): void {
        if (!is_array($value)) {
            $value = [$value];
        }

        parent::set($header, $value);
    }
}
