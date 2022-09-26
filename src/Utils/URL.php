<?php

namespace App\Utils;

use JPI\Utils\URL as BaseClass;

class URL extends BaseClass {

    public static function addTrailingSlash(string $url): string {
        $url = static::removeTrailingSlash($url);

        // If the last bit includes a full stop, assume its a file... so don't add trailing slash
        $withoutProtocol = str_replace(["https://", "http://"], "", $url);
        $splitPaths = explode("/", $withoutProtocol);
        $count = count($splitPaths);
        if ($count > 1 && strpos($splitPaths[$count - 1], ".")) {
            return $url;
        }

        return "$url/";
    }
}
