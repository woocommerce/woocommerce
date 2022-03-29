<?php
/**
 * ArrayColumn tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\Transformers\ArrayColumn;


/**
 * class WC_Admin_Tests_RemoteInboxNotifications_Transformers_ArrayColumn
 */
class WC_Admin_Tests_RemoteInboxNotifications_Transformers_ArrayColumn extends WC_Unit_Test_Case {
	/**
	 * Test validate method returns false when 'key' argument is missing
	 */
	public function test_validate_returns_false_when_key_argument_is_missing() {
		$array_column = new ArrayColumn();
		$result       = $array_column->validate( (object) array() );
		$this->assertFalse( false, $result );
	}

	/**
	 * Test it returns value by array column.
	 */
	public function test_it_returns_value_by_array_column() {
		$items = array(
			array(
				'name' => 'mothra',
			),
			array(
				'name' => 'gezora',
			),
			array(
				'name' => 'ghidorah',
			),
		);

		$arguments    = (object) array( 'key' => 'name' );
		$array_column = new ArrayColumn();
		$result       = $array_column->transform( $items, $arguments );
		$expected     = array( 'mothra', 'gezora', 'ghidorah' );
		$this->assertEquals( $expected, $result );
	}
}
