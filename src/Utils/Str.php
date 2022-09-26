<?php

namespace App\Utils;

class Str {

    public static function toBool(?string $string, ?bool $default = false): ?bool {
        if (in_array($string, ["", null], true)) {
            return $default;
        }

        return filter_var($string, FILTER_VALIDATE_BOOLEAN);
    }

    public static function machineToDisplay(string $value): string {
        $value = str_replace("_", " ", $value);
        return ucwords($value);
    }
}
