<?php
/**
 * ArraySearch tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\Transformers\ArraySearch;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_Transformers_ArraySearch
 */
class WC_Admin_Tests_RemoteInboxNotifications_Transformers_ArraySearch extends WC_Unit_Test_Case {
	/**
	 * Test validate method returns false when 'value' argument is missing
	 */
	public function test_validate_returns_false_when_value_argument_is_missing() {
		$array_column = new ArraySearch();
		$result       = $array_column->validate( (object) array() );
		$this->assertFalse( false, $result );
	}

	/**
	 * Test it returns null if value is not found.
	 */
	public function test_it_returns_null_if_value_is_not_found() {
		$items        = array( 'item1', 'item2' );
		$arguments    = (object) array( 'value' => 'item3' );
		$array_column = new ArraySearch();
		$result       = $array_column->transform( $items, $arguments );
		$this->assertEquals( null, $result );
	}

	/**
	 * Test it returns value by array value.
	 */
	public function test_it_returns_value_by_array_value() {
		$items        = array( 'item1', 'item2' );
		$arguments    = (object) array( 'value' => 'item2' );
		$array_column = new ArraySearch();
		$result       = $array_column->transform( $items, $arguments );
		$this->assertEquals( 'item2', $result );
	}
}
