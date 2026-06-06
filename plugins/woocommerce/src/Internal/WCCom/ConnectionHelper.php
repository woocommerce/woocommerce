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
		$auth           = is_array( $helper_options ) ? $helper_options['auth'] ?? array() : array();

		return is_array( $auth ) && ! empty( $auth['access_token'] ) && ! empty( $auth['access_token_secret'] );
	}
}
