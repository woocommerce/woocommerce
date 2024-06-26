<?php
/**
 * ArrayValues tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers\ArrayValues;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_Transformers_ArrayValues
 */
class WC_Admin_Tests_RemoteInboxNotifications_Transformers_ArrayValues extends WC_Unit_Test_Case {
	/**
	 * Test it returns default value when value is not an array
	 */
	public function test_it_returns_default_value_when_value_is_not_an_array() {
		$default    = 'default value';
		$array_keys = new ArrayValues();
		$result     = $array_keys->transform( 'invalid value', null, $default );
		$this->assertEquals( $default, $result );
	}

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
