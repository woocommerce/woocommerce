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
	 * Most recent immutable source transformation, independent of active mocks.
	 *
	 * @var array{source: string, path: string, output: string}|null
	 */
	private $last_transformation;


	/**
	 * Hacks code by removing "final" keywords from class definitions.
	 *
	 * @param string $code The code to hack.
	 * @param string $path The path of the file containing the code to hack.
	 * @return string The hacked code.
	 */
	public function hack( $code, $path ) {
		if ( null !== $this->last_transformation && $code === $this->last_transformation['source'] && $path === $this->last_transformation['path'] ) {
			return $this->last_transformation['output'];
		}
		$source = $code;

		if ( stripos( $code, 'final' ) !== false ) {
			$tokens = $this->tokenize( $code );
			$code   = '';
			foreach ( $tokens as $token ) {
				$code .= $this->is_token_of_type( $token, T_FINAL ) ? '' : $this->token_to_string( $token );
			}
		}

		$this->last_transformation = array(
			'source' => $source,
			'path'   => $path,
			'output' => $code,
		);

		return $code;
	}

	/**
	 * Revert the hack to its initial state - nothing to do since finals can't be reverted.
	 */
	public function reset() {
	}
}

