<?php
/**
 * ArrayKeys tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers\ArrayFlatten;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_Transformers_ArrayKeys
 */
class WC_Admin_Tests_RemoteInboxNotifications_Transformers_ArrayFlatten extends WC_Unit_Test_Case {
	/**
	 * Test it returns default value when value is not an array
	 */
	public function test_it_returns_default_value_when_value_is_not_an_array() {
		$default    = 'default value';
		$array_keys = new ArrayFlatten();
		$result     = $array_keys->transform( 'invalid value', null, $default );
		$this->assertEquals( $default, $result );
	}

	/**
	 * Test it returns flatten array
	 */
	public function test_it_returns_flatten_array() {
		$items = array(
			array(
				'member1',
			),
			array(
				'member2',
			),
			array(
				'member3',
			),
		);

		$array_keys = new ArrayFlatten();
		$result     = $array_keys->transform( $items );
		$this->assertEquals( array( 'member1', 'member2', 'member3' ), $result );
	}
}
