<?php
/**
 * ArrayKeys tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\Transformers\ArrayKeys;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_Transformers_ArrayKeys
 */
class WC_Admin_Tests_RemoteInboxNotifications_Transformers_ArrayKeys extends WC_Unit_Test_Case {
	/**
	 * Test it returns array values.
	 */
	public function test_it_returns_array_values() {
		$items = array(
			'size'  => 'XL',
			'color' => 'gold',
		);

		$array_keys = new ArrayKeys();
		$result     = $array_keys->transform( $items );
		$this->assertEquals( array( 'size', 'color' ), $result );
	}
}
