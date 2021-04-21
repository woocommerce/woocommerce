<?php
/**
 * Tests for ThemeSupport
 *
 * @package Automattic\WooCommerce\Tests\ThemeSupport
 */

namespace Automattic\WooCommerce\Tests\ThemeManagement;

use Automattic\WooCommerce\Internal\ThemeSupport;

/**
 * Tests for ThemeSupport
 */
class ThemeSupportTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var ThemeSupport
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp() {
		$this->sut = $this->get_instance_of( ThemeSupport::class );
		remove_theme_support( 'woocommerce' );
	}

	/**
	 * @testdox add_options should add the supplied options under the 'woocommerce' feature.
	 */
	public function test_add_options() {
		$actual_added_feature = null;
		$actual_added_options = null;

		$this->register_legacy_proxy_function_mocks(
			array(
				'add_theme_support' => function( $feature, ...$args ) use ( &$actual_added_feature, &$actual_added_options ) {
					$actual_added_feature = $feature;
					$actual_added_options = $args;
				},
			)
		);

		$options = array( 'foo' => 'bar' );
		$this->sut->add_options( $options );

		$this->assertEquals( 'woocommerce', $actual_added_feature );
		$this->assertEquals( $options, $actual_added_options[0] );

		$this->reset_legacy_proxy_mocks();

		$this->sut->add_options( $options );

		$actual_retrieved_options = get_theme_support( 'woocommerce' )[0];
		$this->assertEquals( $options, $actual_retrieved_options );
	}

	/**
	 * @testdox add_default_options should add the supplied options under the 'woocommerce' feature on a '_defaults' key.
	 */
	public function test_2_add_default_options() {
		$actual_added_options = array();

		$this->register_legacy_proxy_function_mocks(
			array(
				'add_theme_support' => function( $feature, ...$args ) use ( &$actual_added_options ) {
					array_push( $actual_added_options, $args );
				},
			)
		);

		$this->sut->add_default_options( array( 'foo' => 'bar' ) );
		$this->sut->add_default_options( array( 'fizz' => 'buzz' ) );

		$expected_added_options = array(
			array(
				array(
					ThemeSupport::DEFAULTS_KEY =>
						array(
							'foo' => 'bar',
						),
				),
			),
			array(
				array(
					ThemeSupport::DEFAULTS_KEY =>
						array(
							'fizz' => 'buzz',
						),
				),
			),
		);

		$this->assertEquals( $expected_added_options, $actual_added_options );

		$this->reset_legacy_proxy_mocks();

		$this->sut->add_default_options( array( 'foo' => 'bar' ) );
		$this->sut->add_default_options( array( 'fizz' => 'buzz' ) );

		$actual_retrieved_options   = get_theme_support( 'woocommerce' )[0];
		$expected_retrieved_options = array(
			ThemeSupport::DEFAULTS_KEY => array(
				'foo'  => 'bar',
				'fizz' => 'buzz',
			),
		);
		$this->assertEquals( $expected_retrieved_options, $actual_retrieved_options );
	}

	/**
	 * @testdox add_default_options should add the supplied options under the 'woocommerce' feature on a '_defaults' key.
	 */
	public function test_add_default_options() {
		$this->sut->add_default_options( array( 'foo' => 'bar' ) );
		$this->sut->add_default_options( array( 'fizz' => 'buzz' ) );

		$actual   = get_theme_support( 'woocommerce' )[0];
		$expected = array(
			ThemeSupport::DEFAULTS_KEY => array(
				'foo'  => 'bar',
				'fizz' => 'buzz',
			),
		);
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox get_option should return all the options under the 'woocommerce' feature when invoked with blank option name.
	 */
	public function test_get_option_with_no_option_name() {
		$options = array( 'foo' => 'bar' );
		$this->sut->add_options( $options );

		$actual = $this->sut->get_option();
		$this->assertEquals( $options, $actual );
	}

	/**
	 * @testdox get_option should return null if no 'woocommerce' feature exists and no default value is supplied.
	 */
	public function test_get_option_with_no_option_name_when_no_options_exist_and_no_default_value_supplied() {
		$actual = $this->sut->get_option();
		$this->assertNull( $actual );
	}

	/**
	 * @testdox get_option should return the supplied default value if no 'woocommerce' feature exists.
	 */
	public function test_get_option_with_no_option_name_when_no_options_exist_and_default_value_supplied() {
		$actual = $this->sut->get_option( '', 'DEFAULT' );
		$this->assertEquals( 'DEFAULT', $actual );
	}

	/**
	 * @testdox get_theme_support should return the value of the requested option if it exists.
	 */
	public function test_get_theme_support_with_option_name() {
		$options = array( 'foo' => array( 'bar' => 'fizz' ) );
		$this->sut->add_options( $options );

		$actual = $this->sut->get_option( 'foo::bar' );
		$this->assertEquals( 'fizz', $actual );
	}

	/**
	 * @testdox get_option should return null if the requested option doesn't exist and no default value is supplied.
	 */
	public function test_get_option_with_option_name_when_option_does_not_exist_and_no_default_value_supplied() {
		$options = array( 'foo' => array( 'bar' => 'fizz' ) );
		$this->sut->add_options( $options );

		$actual = $this->sut->get_option( 'buzz' );
		$this->assertNull( $actual );
	}

	/**
	 * @testdox get_option should return the supplied default value if the requested option doesn't exist.
	 */
	public function test_get_option_with_option_name_when_option_does_not_exist_and_default_value_supplied() {
		$options = array( 'foo' => array( 'bar' => 'fizz' ) );
		$this->sut->add_options( $options );

		$actual = $this->sut->get_option( 'buzz', 'DEFAULT' );
		$this->assertEquals( 'DEFAULT', $actual );
	}

	/**
	 * @testdox get_option should return the value of the requested option if it has been defined as a default.
	 */
	public function test_get_option_with_option_name_and_option_defined_as_default() {
		$options = array( 'foo' => array( 'bar' => 'fizz' ) );
		$this->sut->add_default_options( $options );

		$actual = $this->sut->get_option( 'foo::bar' );
		$this->assertEquals( 'fizz', $actual );
	}

	/**
	 * @testdox has_option should return false if no 'woocommerce' feature exists.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $include_defaults Whether to include defaults in the search or not.
	 */
	public function test_has_option_when_no_woocommerce_feature_is_defined( $include_defaults ) {
		$this->assertFalse( $this->sut->has_option( 'foo::bar', $include_defaults ) );
	}

	/**
	 * @testdox has_option should return false if the specified option has not been defined.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $include_defaults Whether to include defaults in the search or not.
	 */
	public function test_has_option_when_option_is_not_defined( $include_defaults ) {
		$this->sut->add_options( array( 'foo' => 'bar' ) );
		$this->assertFalse( $this->sut->has_option( 'fizz::buzz', $include_defaults ) );
	}

	/**
	 * @testdox has_option should return true if the specified option has been defined.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $include_defaults Whether to include defaults in the search or not.
	 */
	public function test_has_option_when_option_is_defined( $include_defaults ) {
		$this->sut->add_options( array( 'foo' => 'bar' ) );
		$this->assertTrue( $this->sut->has_option( 'foo', $include_defaults ) );
	}

	/**
	 * @testdox If an option has been defined as a default, has_theme_support should return true if $include_defaults is passed as true, should return false otherwise.
	 *
	 * @testWith [true, true]
	 *           [false, false]
	 *
	 * @param bool $include_defaults Whether to include defaults in the search or not.
	 * @param bool $expected_result The expected return value from the tested method.
	 */
	public function test_has_option_when_option_is_defined_as_default( $include_defaults, $expected_result ) {
		$this->sut->add_default_options( array( 'foo' => 'bar' ) );
		$this->assertEquals( $expected_result, $this->sut->has_option( 'foo', $include_defaults ) );
	}
}
