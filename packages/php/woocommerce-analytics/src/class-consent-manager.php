<?php
/**
 * Consent management for WP Consent API integration
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

/**
 * Manages consent checking for WooCommerce Analytics
 */
class Consent_Manager {

	/**
	 * WP Consent API's consent type we check for analytics tracking
	 */
	const WP_CONSENT_API_STATISTICS_TYPE = 'statistics';

	/**
	 * Check if the WP Consent API is active, meaning a consent plugin may decide on statistics consent.
	 *
	 * @since 0.18.1
	 *
	 * @return bool
	 */
	public static function is_consent_api_active() {
		return function_exists( 'wp_has_consent' );
	}

	/**
	 * Check if a consent plugin decides on statistics consent. Without a consent type, the WP Consent API grants every category.
	 *
	 * @since 0.18.1
	 *
	 * @return bool
	 */
	public static function is_consent_managed() {
		if ( ! self::is_consent_api_active() ) {
			return false;
		}

		// Only a stand-in defines wp_has_consent() alone, and it ships no script for the client to wait for.
		if ( ! function_exists( 'wp_get_consent_type' ) ) {
			return false;
		}

		if ( \wp_get_consent_type() ) {
			return true;
		}

		// A consent plugin that sets the consent type in the browser, for example from the visitor's region, sets this instead.
		return (bool) apply_filters( 'wp_consent_api_waitfor_consent_hook', false ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Reads a WP Consent API filter.
	}

	/**
	 * Check if user has consent for analytics tracking
	 *
	 * @return bool
	 */
	public static function has_analytics_consent() {
		if ( ! self::is_consent_api_active() ) {
			// If WP Consent API is not available, default to true for backward compatibility
			return true;
		}

		// Without a consent type the WP Consent API grants every category, even after the shopper denied statistics in the browser.
		if ( function_exists( 'wp_get_consent_type' ) && ! \wp_get_consent_type() && self::is_statistics_consent_denied() ) {
			return false;
		}

		return \wp_has_consent( self::WP_CONSENT_API_STATISTICS_TYPE );
	}

	/**
	 * Get the name of the WP Consent API cookie that stores the shopper's statistics choice, or an empty string without the WP Consent API.
	 *
	 * @since 0.18.2
	 *
	 * @return string
	 */
	public static function get_statistics_consent_cookie_name() {
		if ( ! self::is_consent_api_active() ) {
			return '';
		}

		$prefix = apply_filters( 'wp_consent_cookie_prefix', 'wp_consent' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Reads a WP Consent API filter.
		if ( ! is_string( $prefix ) ) {
			return '';
		}

		return $prefix . '_' . self::WP_CONSENT_API_STATISTICS_TYPE;
	}

	/**
	 * Check if the shopper denied statistics consent, as recorded in the WP Consent API consent cookie.
	 *
	 * @return bool
	 */
	private static function is_statistics_consent_denied() {
		$cookie_name = self::get_statistics_consent_cookie_name();

		return '' !== $cookie_name && isset( $_COOKIE[ $cookie_name ] ) && 'deny' === $_COOKIE[ $cookie_name ];
	}
}
