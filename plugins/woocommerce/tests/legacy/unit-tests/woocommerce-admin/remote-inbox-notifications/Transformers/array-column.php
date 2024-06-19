<?php
/**
 * ArrayColumn tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers\ArrayColumn;


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
	 * Test validate method returns false when argument->key is not valid type
	 */
	public function test_validate_returns_false_when_argument_key_is_not_valid_type() {
		$array_column = new ArrayColumn();
		$result       = $array_column->validate(
			(object) array(
				'key' => true,
			)
		);
		$this->assertFalse( false, $result );

		$result = $array_column->validate(
			(object) array(
				'key' => array(),
			)
		);
		$this->assertFalse( false, $result );
	}

	/**
	 * Test it returns default value when value is not an array
	 */
	public function test_it_returns_default_value_when_value_is_not_an_array() {
		$arguments    = (object) array( 'key' => 'name' );
		$array_column = new ArrayColumn();
		$default      = 'default value';
		$result       = $array_column->transform( 'invalid value', $arguments, $default );
		$this->assertEquals( $default, $result );
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
