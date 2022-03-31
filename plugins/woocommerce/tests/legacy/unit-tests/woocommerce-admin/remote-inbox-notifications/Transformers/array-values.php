<?php
/**
 * ArrayValues tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\Transformers\ArrayValues;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_Transformers_ArrayValues
 */
class WC_Admin_Tests_RemoteInboxNotifications_Transformers_ArrayValues extends WC_Unit_Test_Case {
	/**
	 * Test it returns array values.
	 */
	public function test_it_returns_array_values() {
		$items        = array(
			'size'  => 'XL',
			'color' => 'gold',
		);
		$array_values = new ArrayValues();
		$result       = $array_values->transform( $items );
		$this->assertEquals( array( 'XL', 'gold' ), $result );
	}
}
