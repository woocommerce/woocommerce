<?php
/**
 * DotNotation tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers\DotNotation;


/**
 * class WC_Admin_Tests_RemoteInboxNotifications_Transformers_DotNotation
 */
class WC_Admin_Tests_RemoteInboxNotifications_Transformers_DotNotation extends WC_Unit_Test_Case {

	/**
	 * Test validate method returns false when 'path' argument is missing
	 */
	public function test_validate_returns_false_when_path_argument_is_missing() {
		$array_column = new DotNotation();
		$result       = $array_column->validate( (object) array() );
		$this->assertFalse( false, $result );
	}

	/**
	 * Test it can get value by index
	 */
	public function test_it_can_get_value_by_index() {
		$arguments    = (object) array( 'path' => '0' );
		$dot_notation = new DotNotation();
		$item         = array( 'name' => 'test' );
		$items        = array( $item );

		$result = $dot_notation->transform( $items, $arguments );
		$this->assertEquals( $result, $item );
	}

	/**
	 * Test it returns default value when value is not an array.
	 */
	public function test_it_returns_default_value_when_value_is_not_an_array() {
		$arguments  = (object) array( 'path' => 'teams.ghidorah' );
		$default    = 'default value';
		$array_keys = new DotNotation();

		$result = $array_keys->transform( 'invalid value', $arguments, $default );
		$this->assertEquals( $default, $result );
	}

	/**
	 * Test it get getvalue by dot notation.
	 */
	public function test_it_can_get_value_by_dot_notation() {
		$arguments = (object) array( 'path' => 'teams.mothra' );

		$items = array(
			'teams' => array(
				'mothra' => 'nice!',
			),
		);

		$dot_notation = new DotNotation();
		$result       = $dot_notation->transform( $items, $arguments );
		$this->assertEquals( 'nice!', $result );
	}

	/**
	 * Test it returns default value when path is undefined
	 */
	public function test_it_can_get_default_value_by_dot_notation() {
		$arguments = (object) array( 'path' => 'teams.property_that_does_not_exist' );

		$items = array(
			'teams' => array(
				'mothra' => 'nice!',
			),
		);

		$dot_notation = new DotNotation();
		$default      = 'default value';
		$result       = $dot_notation->transform( $items, $arguments, $default );
		$this->assertEquals( $default, $result );
	}
}
