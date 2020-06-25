<?php
/**
 * Tests for StringUtils
 *
 * @package WooCommerce\Tests\Utils
 */

use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utils\StringUtils;

/**
 * Tests for StringUtils
 */
class StringUtilsTests extends WC_Unit_Test_Case {

	/**
	 * @testdox get_class_short_name should return the proper class short name of the supplied object.
	 */
	public function test_get_class_short_name_retrieves_short_name_of_object() {
		$object = new StringUtils();

		$this->assertEquals( 'StringUtils', StringUtils::get_class_short_name( $object ) );
	}

	/**
	 * @testdox get_class_short_name should return the input unmodified if it's already a class short name.
	 */
	public function test_get_class_short_name_retrieves_short_name_strings_unmodified() {
		$this->assertEquals( 'StringUtils', StringUtils::get_class_short_name( 'StringUtils' ) );
	}

	/**
	 * @testdox get_class_short_name should return the input converted to a class short name if it's a class full name.
	 */
	public function test_get_class_short_name_retrieves_full_name_strings_shortened() {
		$this->assertEquals( 'StringUtils', StringUtils::get_class_short_name( StringUtils::class ) );
	}
}
