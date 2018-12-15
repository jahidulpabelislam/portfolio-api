<?php
/*
 * All the functions needed for the hashing needed for this API's Authentication.
 *
 * PHP version 7
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version 2
 * @link https://github.com/jahidulpabelislam/portfolio-api/
 * @since Class available since Release: v2
 * @copyright 2014-2018 JPI
*/

namespace JPI\API;

if (!defined("ROOT")) {
	die();
}

class Hasher {

	/*
	 * This will generate a hashed value of given string
	 */
	public static function generate($string) {

		// TODO Generate a hashed on given string

		$hashed = $string;

		return $hashed;
	}

	// This will be used to compare a raw string against a stored hash
	public static function check($string, $hash) {

		// TODO Compare a raw string against a hash of what it should be

		return $string === $hash;

	}
}