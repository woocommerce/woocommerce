<?php
/**
 * BypassFinalsHack class file.
 *
 * @package WooCommerce\Testing
 */

namespace Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks;

/**
 * Code hack to bypass finals.
 *
 * Removes all the "final" keywords from class definitions.
 */
final class BypassFinalsHack extends CodeHack {

	/**
	 * Hacks code by removing "final" keywords from class definitions.
	 *
	 * @param string $code The code to hack.
	 * @param string $path The path of the file containing the code to hack.
	 * @return string The hacked code.
	 */
	public function hack( $code, $path ) {
		if ( stripos( $code, 'final' ) !== false ) {
			$tokens = $this->tokenize( $code );
			$code   = '';
			foreach ( $tokens as $token ) {
				$code .= $this->is_token_of_type( $token, T_FINAL ) ? '' : $this->token_to_string( $token );
			}
		}

		return $code;
	}

	/**
	 * Revert the hack to its initial state - nothing to do since finals can't be reverted.
	 */
	public function reset() {
	}
}

