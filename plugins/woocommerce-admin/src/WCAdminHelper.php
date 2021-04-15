<?php
/**
 * WCAdminHelper
 *
 * Helper class for generic WCAdmin functions.
 */

namespace Automattic\WooCommerce\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class WCAdminHelper
 */
class WCAdminHelper {
	/**
	 * WC Admin timestamp option name.
	 */
	const WC_ADMIN_TIMESTAMP_OPTION = 'woocommerce_admin_install_timestamp';

	/**
	 * Get the number of seconds that the store has been active.
	 *
	 * @return number Number of seconds.
	 */
	public static function get_wcadmin_active_for_in_seconds() {
		$install_timestamp = get_option( self::WC_ADMIN_TIMESTAMP_OPTION );

		if ( false === $install_timestamp ) {
			$install_timestamp = time();
			update_option( self::WC_ADMIN_TIMESTAMP_OPTION, $install_timestamp );
		}

		return time() - $install_timestamp;
	}


	/**
	 * Test how long WooCommerce Admin has been active.
	 *
	 * @param int $seconds Time in seconds to check.
	 * @return bool Whether or not WooCommerce admin has been active for $seconds.
	 */
	public static function is_wc_admin_active_for( $seconds ) {
		$wc_admin_active_for = self::get_wcadmin_active_for_in_seconds();

		return ( $wc_admin_active_for >= $seconds );
	}
}
