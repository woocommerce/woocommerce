<?php
/**
 * Stand-in for the WP Consent API plugin that reads the consent cookie as WP Consent API 2.0 does. Load it only in a separate process.
 *
 * @package automattic/woocommerce-analytics
 */

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

if ( ! function_exists( 'wp_has_consent' ) ) {
	/**
	 * Mirrors WP Consent API 2.0's wp_has_consent(), which grants every category when no consent type is set.
	 *
	 * @param string $category Consent category.
	 * @return bool
	 */
	function wp_has_consent( $category ) { // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
		$consent_type = wp_get_consent_type();
		$cookie_name  = apply_filters( 'wp_consent_cookie_prefix', 'wp_consent' ) . '_' . $category;
		$cookie       = $_COOKIE[ $cookie_name ] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( ! $consent_type ) {
			$has_consent = true;
		} elseif ( false !== strpos( $consent_type, 'optout' ) && null === $cookie ) {
			$has_consent = true;
		} else {
			$has_consent = 'allow' === $cookie;
		}

		return apply_filters( 'wp_has_consent', $has_consent, $category, null );
	}
}
