<?php

namespace App\Utils;

class StringHelper {

    public static function removeLeadingSlash(string $url): string {
        if ($url[0] === "/") {
            $url = substr($url, 1);
        }

        return $url;
    }

    public static function removeTrailingSlash(string $url): string {
        if (substr($url, -1) === "/") {
            $url = substr($url, 0, -1);
        }

        return $url;
    }

    public static function removeSlashes(string $url): string {
        $url = self::removeLeadingSlash($url);
        return self::removeTrailingSlash($url);
    }

    public static function addTrailingSlash(string $url): string {
        $url = self::removeTrailingSlash($url);

        // If the last bit includes a full stop, assume its a file...
        // so don't add trailing slash
        $withoutProtocol = str_replace(["https://", "http://"], "", $url);
        $splitPaths = explode("/", $withoutProtocol);
        $count = count($splitPaths);
        if ($count > 1 && !is_dir($url)) {
            $lastPath = $splitPaths[$count - 1] ?? null;
            if ($lastPath && strpos($lastPath, ".")) {
                return $url;
            }
        }

        return "$url/";
    }

    public static function addLeadingSlash(string $url): string {
        $url = self::removeLeadingSlash($url);

        return "/$url";
    }

    public static function stringToBoolean(?string $string, ?bool $default = false): ?bool {
        if (in_array($string, ["", null], true)) {
            return $default;
        }

        return filter_var($string, FILTER_VALIDATE_BOOLEAN);
    }

}
