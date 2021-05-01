<?php

namespace App\HTTP;

use App\Utils\ArrayCollection;

class Headers extends ArrayCollection {

    public function set($header, $value): void {
        if (!is_array($value)) {
            $value = [$value];
        }

        parent::set($header, $value);
    }

}
