<?php

namespace Automattic\WooCommerce\Testing\CodeHacking\Hacks;

/**
 * Base class to define Hacks for CodeHacker.
 *
 * This class is included for convenience only, any class having a 'public function hack($code, $path)'
 * can be used as a hack class for CodeHacker.
 */
abstract class CodeHack {

	/**
	 * The hack method to implement.
	 *
	 * @param string $code The code to hack.
	 * @param string $path The path of the file containing the code to hack.
	 * @return string The hacked code.
	 */
	abstract public function hack( $code, $path);

	/**
	 * Tokenize PHP source code.
	 *
	 * @param string $code PHP code to tokenize.
	 * @return array Tokenized code.
	 */
	protected function tokenize( $code ) {
		return PHP_VERSION_ID >= 70000 ? token_get_all( $code, TOKEN_PARSE ) : token_get_all( $code );
	}

	/**
	 * Check if a token is of a given type.
	 *
	 * @param mixed $token Token to check.
	 * @param int   $type Type of token to check (see https://www.php.net/manual/en/tokens.php)
	 * @return bool True if it's a token of the given type, false otherwise.
	 */
	protected function is_token_of_type( $token, $type ) {
		return is_array( $token ) && $type === $token[0];
	}

	/**
	 * Return the type of a given token.
	 *
	 * @param mixed $token Token to check.
	 * @return mixed|null Type of token (see https://www.php.net/manual/en/tokens.php), or null if it's a character.
	 */
	protected function token_type_of( $token ) {
		return is_array( $token ) ? $token[0] : null;
	}

	/**
	 * Converts a token to its string representation.
	 *
	 * @param $token Token to convert.
	 * @return mixed String representation of the token.
	 */
	protected function token_to_string( $token ) {
		return is_array( $token ) ? $token[1] : $token;
	}

	/**
	 * Checks if a string ends with a certain substring.
	 * This method is added to help processing path names within 'hack' methods needing to do so.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle The substring to search for.
	 * @return bool True if the $haystack ends with $needle, false otherwise.
	 */
	protected function string_ends_with( $haystack, $needle ) {
		$length = strlen( $needle );
		if ( $length == 0 ) {
			return true;
		}

		return ( substr( $haystack, -$length ) === $needle );
	}
}
