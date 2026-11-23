<?php
/**
 * Stand-in for the WP Consent API plugin. Load it only in a separate process: a function cannot be undefined.
 *
 * @package automattic/woocommerce-analytics
 */

require_once __DIR__ . '/wp-has-consent-function.php';

if ( ! function_exists( 'wp_get_consent_type' ) ) {
	/**
	 * Mock of the WP Consent API consent type, which consent plugins set through the same filter.
	 *
	 * @return string
	 */
	function wp_get_consent_type() { // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
		return apply_filters( 'wp_get_consent_type', '' );
	}
}
