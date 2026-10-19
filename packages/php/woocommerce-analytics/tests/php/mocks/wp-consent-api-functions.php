<?php
/**
 * Stand-in for the WP Consent API plugin. Load it only in a separate process: a function cannot be undefined.
 *
 * @package automattic/woocommerce-analytics
 */

if ( ! function_exists( 'wp_has_consent' ) ) {
	/**
	 * Mock of the WP Consent API check.
	 *
	 * @param string $category Consent category.
	 * @return bool
	 */
	function wp_has_consent( $category ) { // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed, VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return false;
	}
}
