<?php

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Utilities\I18nUtil;

/**
 * A collection of tests for the internationalization utility class.
 */
class I18nUtilTest extends \WC_Unit_Test_Case {
	/**
	 * A reflected instance of I18nUtil that can be manipulated for testing.
	 *
	 * @var \ReflectionClass
	 */
	private static $reflection;

	/**
	 * Do once before all the tests in this class.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		self::$reflection = new \ReflectionClass( I18nUtil::class );
	}

	/**
	 * Do before each test in this class.
	 *
	 * @return void
	 * @throws \ReflectionException If the units property does not exist on the class.
	 */
	public function set_up() {
		parent::set_up();

		// Ensure unit strings are not already cached before the gettext filter gets added.
		$units_prop = self::$reflection->getProperty( 'units' );
		$units_prop->setAccessible( true );
		$units_prop->setValue( I18nUtil::class, null );

		add_filter( 'gettext', array( __CLASS__, 'filter_gettext' ) );
	}

	/**
	 * Do after each test in this class.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'gettext', array( __CLASS__, 'filter_gettext' ) );

		parent::tear_down();
	}

	/**
	 * Simulate a string getting translated.
	 *
	 * @param string $translated The "translated" string before we modify it.
	 *
	 * @return string
	 */
	public static function filter_gettext( $translated ): string {
		return sprintf( '%s (translated)', $translated );
	}

	/**
	 * @testdox `get_weight_unit_label` should return a translated string when it's a valid weight unit.
	 */
	public function test_get_valid_weight_unit_label() {
		$actual   = I18nUtil::get_weight_unit_label( 'oz' );
		$expected = 'oz (translated)';

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox `get_weight_unit_label` should return the original input string when the input is not a recognized weight unit.
	 */
	public function test_get_invalid_weight_unit_label() {
		$actual   = I18nUtil::get_weight_unit_label( 'chz' ); // Cheezeburgers.
		$expected = 'chz';

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox `get_dimensions_unit_label` should return a translated string when it's a valid dimensions unit.
	 */
	public function test_get_valid_dimensions_unit_label() {
		$actual   = I18nUtil::get_dimensions_unit_label( 'yd' );
		$expected = 'yd (translated)';

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox `get_dimensions_unit_label` should return the original input string when the input is not a recognized dimensions unit.
	 */
	public function test_get_invalid_dimensions_unit_label() {
		$actual   = I18nUtil::get_weight_unit_label( 'pc' ); // Parsecs.
		$expected = 'pc';

		$this->assertEquals( $expected, $actual );
	}
}
