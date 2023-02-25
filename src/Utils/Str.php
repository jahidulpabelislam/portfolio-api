<?php

namespace App\Utils;

class Str {

    public static function machineToDisplay(string $value): string {
        $value = str_replace("_", " ", $value);
        return ucwords($value);
    }
}
