<?php
/**
 * ArrayValues tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\Transformers\Count;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_Transformers_ArrayValues
 */
class WC_Admin_Tests_RemoteInboxNotifications_Transformers_ArrayCount extends WC_Unit_Test_Case {
	/**
	 * Test it returns array count.
	 */
	public function test_it_returns_array_count() {
		$items        = array( 'one', 'two', 'three', 'four' );
		$array_values = new Count();
		$result       = $array_values->transform( $items );
		$this->assertEquals( 4, $result );
	}
}
