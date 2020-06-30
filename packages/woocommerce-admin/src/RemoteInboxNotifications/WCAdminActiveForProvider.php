<?php
/**
 * WCAdmin active for provider.
 *
 * @package WooCommerce Admin/Classes;
 */

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

defined( 'ABSPATH' ) || exit;

/**
 * WCAdminActiveForProvider class
 */
class WCAdminActiveForProvider {
	/**
	 * Get the number of seconds that the store has been active.
	 *
	 * @return number Number of seconds.
	 */
	public function get_wcadmin_active_for_in_seconds() {
		$install_timestamp = get_option( 'woocommerce_admin_install_timestamp' );

		return time() - $install_timestamp;
	}
}
