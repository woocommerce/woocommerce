<?php
/**
 * ArrayKeys tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers\ArrayKeys;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_Transformers_ArrayKeys
 */
class WC_Admin_Tests_RemoteInboxNotifications_Transformers_ArrayKeys extends WC_Unit_Test_Case {
	/**
	 * Test it returns default value when value is not an array
	 */
	public function test_it_returns_default_value_when_value_is_not_an_array() {
		$default    = 'default value';
		$array_keys = new ArrayKeys();
		$result     = $array_keys->transform( 'invalid value', null, $default );
		$this->assertEquals( $default, $result );
	}

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
