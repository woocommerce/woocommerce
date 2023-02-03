<?php

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Utilities\I18nUtil;

/**
 * A collection of tests for the internationalization utility class.
 */
class I18nUtilTest extends \WC_Unit_Test_Case {

	/**
	 * @testdox `get_weight_unit_label` should return the input when it's a valid weight unit and the locale is en_US.
	 */
	public function test_get_valid_weight_unit_label() {
		$actual   = I18nUtil::get_weight_unit_label( 'oz' );
		$expected = 'oz';

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox `get_weight_unit_label` should return an empty string when the input is an invalid weight unit.
	 */
	public function test_get_invalid_weight_unit_label() {
		$actual   = I18nUtil::get_weight_unit_label( 'chz' ); // Cheezeburgers.
		$expected = '';

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox `get_dimensions_unit_label` should return the input when it's a valid dimensions unit and the locale is en_US.
	 */
	public function test_get_valid_dimensions_unit_label() {
		$actual   = I18nUtil::get_dimensions_unit_label( 'yd' );
		$expected = 'yd';

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox `get_dimensions_unit_label` should return an empty string when the input is an invalid dimensions unit.
	 */
	public function test_get_invalid_dimensions_unit_label() {
		$actual   = I18nUtil::get_weight_unit_label( 'pc' ); // Parsecs.
		$expected = '';

		$this->assertEquals( $expected, $actual );
	}
}
