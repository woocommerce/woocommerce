<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\POSSessionHandler;

defined( 'ABSPATH' ) || exit;

/**
 * POSUtils class
 *
 * Utility functions for Point of Sale (POS) functionality in the Store API.
 */
class POSUtils {

	/**
	 * Check if the current request is using a POS session.
	 *
	 * @return bool True if the current session is a POS session.
	 */
	public static function is_pos_session(): bool {
		if ( ! function_exists( 'WC' ) || is_null( WC()->session ) ) {
			return false;
		}

		return WC()->session instanceof POSSessionHandler;
	}

	/**
	 * Check if the current user has permission to use POS checkout.
	 *
	 * Requires the user to be authenticated and have the manage_woocommerce capability.
	 *
	 * @return bool True if the current user can use POS checkout.
	 */
	public static function current_user_can_pos(): bool {
		return is_user_logged_in() && current_user_can( 'manage_woocommerce' );
	}
}
