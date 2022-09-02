<?php
/**
 * Mock WCAdminActiveForProvider
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

/**
 * Mock WCAdminActiveForProvider
 */
class MockWCAdminActiveForProvider {
	/**
	 * Get the number of seconds that the store has been active.
	 *
	 * @return number Number of seconds.
	 */
	public function get_wcadmin_active_for_in_seconds() {
		return 8 * DAY_IN_SECONDS;
	}
}
