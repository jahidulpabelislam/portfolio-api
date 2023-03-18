<?php

declare(strict_types=1);

namespace App\Utils;

class Str {

    public static function machineToDisplay(string $value): string {
        $value = str_replace("_", " ", $value);
        return ucwords($value);
    }
}
