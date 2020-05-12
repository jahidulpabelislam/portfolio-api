<?php

namespace App;

class Utilities {

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
        $url = self::removeTrailingSlash($url);

        return $url;
    }

    public static function addTrailingSlash(string $url): string {
        $url = self::removeTrailingSlash($url);

        return "{$url}/";
    }

    public static function addLeadingSlash(string $url): string {
        $url = self::removeLeadingSlash($url);

        return "/{$url}";
    }

    public static function stringToBoolean(?string $string, ?bool $default = false): ?bool {
        if (in_array($string, ['', null], true)) {
            return $default;
        }

        return filter_var($string, FILTER_VALIDATE_BOOLEAN);
    }

}
