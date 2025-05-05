<?php
/**
 * Mock WCAdminActiveForProvider
 *
 * @package WooCommerce\Admin\Tests\RemoteSpecs
 */

declare( strict_types = 1 );

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
