<?php

namespace Automattic\WooCommerce\Testing\CodeHacking\Hacks;

/**
 * Code hack to bypass finals.
 *
 * Removes all the "final" keywords from class definitions.
 */
final class BypassFinalsHack extends CodeHack {

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
}
