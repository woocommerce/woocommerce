<?php
/**
 * Helpers for managing connection to WooCommerce.com.
 */

namespace Automattic\WooCommerce\Internal\WCCom;

defined( 'ABSPATH' ) || exit;

/**
 * Class WCConnectionHelper.
 *
 * Helpers for managing connection to WooCommerce.com.
 */
final class ConnectionHelper {
	/**
	 * Check if WooCommerce.com account is connected.
	 *
	 * @since 4.4.0
	 * @return bool Whether account is connected.
	 */
	public static function is_connected() {
		$helper_options = get_option( 'woocommerce_helper_data', array() );
		if ( is_array( $helper_options ) && array_key_exists( 'auth', $helper_options ) && ! empty( $helper_options['auth'] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if the site is connected to WooCommerce.com.
	 *
	 * @return bool Whether the site is connected.
	 */
	public static function is_site_connected(): bool {
		$auth = \WC_Helper_Options::get( 'auth' );
		if ( ! is_array( $auth ) ) {
			return false;
		}

		return \WC_Helper::is_site_connected();
	}
}
