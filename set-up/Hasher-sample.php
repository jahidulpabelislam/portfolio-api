<?php
/*
 * All the functions needed for the hashing needed for this API's Authentication.
 *
 * PHP version 7
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version 2.1.0
 * @link https://github.com/jahidulpabelislam/portfolio-api/
 * @since Class available since Release: v2.0.0
 * @copyright 2010-2018 JPI
*/

namespace JPI\API;

if (!defined("ROOT")) {
    die();
}

class Hasher {

    /*
     * This will generate a hashed value of given string
     *
     * @param $string string The raw string to hash
     * @return string The newly generated hashed value
     */
    public static function generate(string $string): string {

        // TODO Generate a hashed on given string

        $hashedString = $string;

        return $hashedString;
    }

    /**
     * This will be used to compare a raw string against a stored hash
     *
     * @param $string string The raw string to check
     * @param $hash string The hashed value to check against
     * @return bool Whether or no raw matches hashed value
     */
    public static function check(string $string, string $hash): bool {

        // TODO Compare a raw string against a hash of what it should be

        return $string === $hash;
    }
}
