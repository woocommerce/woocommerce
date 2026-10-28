<?php
/**
 * Stand-in for a plugin that defines only the WP Consent API check. Load it only in a separate process.
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
