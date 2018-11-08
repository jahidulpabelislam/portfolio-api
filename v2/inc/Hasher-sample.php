<?php
/*
 * All the functions needed for the hashing doing for this API Authentication
 * @author Jahidul Pabel Islam
*/

namespace JPI\API;

class HasherSample {

	/*
	 * This will generate a hashed value of given string
	 */
	public static function generate($string) {

		// Generate a hashed on given string

		$hashed = $string;

		return $hashed;
	}

	// This will be used to compare a raw string against a stored hash
	public static function check($string, $hash) {

		// Compare a raw string against a hash of what it should be

		return $string === $hash;

	}
}