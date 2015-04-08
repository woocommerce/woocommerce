<?php
namespace WooCommerce\Tests\Util;

/**
 * Class Validation
 * @package WooCommerce\Tests\Util
 * @since 2.3
 */
class Validation extends \WC_Unit_Test_Case {
	/**
	 * Test is_email()
	 *
	 * @since 2.3
	 */
	public function test_is_email() {
		$this->assertEquals( 'email@domain.com', \WC_Validation::is_email( 'email@domain.com' ) );
		$this->assertFalse( \WC_Validation::is_email( 'not a mail' ) );
		$this->assertFalse( \WC_Validation::is_email( 'http://test.com' ) );
	}
	/**
	 * Test is_phone()
	 *
	 * @since 2.3
	 */
	public function test_is_phone() {
		$this->assertTrue( \WC_Validation::is_phone( '+00 000 00 00 000' ) );
		$this->assertTrue( \WC_Validation::is_phone( '+00-000-00-00-000' ) );
		$this->assertTrue( \WC_Validation::is_phone( '(000) 00 00 000' ) );
		$this->assertFalse( \WC_Validation::is_phone( '+00.000.00.00.000' ) );
		$this->assertFalse( \WC_Validation::is_phone( '+00 aaa dd ee fff' ) );
	}
	/**
	 * Test is_postcode()
	 *
	 * @since 2.4
	 */
	public function test_is_postcode() {
		// Generic postcodes
		$this->assertTrue( \WC_Validation::is_postcode( '99999', 'IT' ) );
		$this->assertTrue( \WC_Validation::is_postcode( '9999', 'IT' ) );
		$this->assertTrue( \WC_Validation::is_postcode( 'ABC 999', 'IT' ) );
		$this->assertTrue( \WC_Validation::is_postcode( 'ABC-999', 'IT' ) );
		$this->assertFalse( \WC_Validation::is_postcode( 'ABC_123', 'IT' ) );
		// GB postcodes
		$this->assertTrue( \WC_Validation::is_postcode( 'A9 9AA', 'GB' ) );
		$this->assertFalse( \WC_Validation::is_postcode( '99999', 'GB' ) );
		// US postcodes
		$this->assertTrue( \WC_Validation::is_postcode( '99999', 'US' ) );
		$this->assertTrue( \WC_Validation::is_postcode( '99999-9999', 'US' ) );
		$this->assertFalse( \WC_Validation::is_postcode( 'ABCDE', 'US' ) );
		$this->assertFalse( \WC_Validation::is_postcode( 'ABCDE-9999', 'US' ) );
		// CH postcodes
		$this->assertTrue( \WC_Validation::is_postcode( '9999', 'CH' ) );
		$this->assertFalse( \WC_Validation::is_postcode( '99999', 'CH' ) );
		$this->assertFalse( \WC_Validation::is_postcode( 'ABCDE', 'CH' ) );
		// BR postcodes
		$this->assertTrue( \WC_Validation::is_postcode( '99999-999', 'BR' ) );
		$this->assertTrue( \WC_Validation::is_postcode( '99999999', 'BR' ) );
		$this->assertFalse( \WC_Validation::is_postcode( '99999 999', 'BR' ) );
		$this->assertFalse( \WC_Validation::is_postcode( '99999-ABC', 'BR' ) );
	}
	/**
	 * Test is_GB_postcode()
	 *
	 * @since 2.4
	 */
	public function test_is_GB_postcode() {
		$this->assertTrue( \WC_Validation::is_GB_postcode( 'AA9A 9AA' ) );
		$this->assertTrue( \WC_Validation::is_GB_postcode( 'A9A 9AA' ) );
		$this->assertTrue( \WC_Validation::is_GB_postcode( 'A9 9AA' ) );
		$this->assertTrue( \WC_Validation::is_GB_postcode( 'A99 9AA' ) );
		$this->assertTrue( \WC_Validation::is_GB_postcode( 'AA99 9AA' ) );
		$this->assertTrue( \WC_Validation::is_GB_postcode( 'BFPO 801' ) );
		$this->assertFalse( \WC_Validation::is_GB_postcode( '99999' ) );
		$this->assertFalse( \WC_Validation::is_GB_postcode( '9999 999' ) );
		$this->assertFalse( \WC_Validation::is_GB_postcode( '999 999' ) );
		$this->assertFalse( \WC_Validation::is_GB_postcode( '99 999' ) );
		$this->assertFalse( \WC_Validation::is_GB_postcode( '9A A9A' ) );
	}
	/**
	 * Test format_postcode()
	 *
	 * @since 2.4
	 */
	public function test_format_postcode() {
		$this->assertEquals( '99999', \WC_Validation::format_postcode( '99999', 'IT' ) );
		$this->assertEquals( '99999', \WC_Validation::format_postcode( ' 99999 ', 'IT' ) );
		$this->assertEquals( '99999', \WC_Validation::format_postcode( '999 99', 'IT' ) );
		$this->assertEquals( 'ABCDE', \WC_Validation::format_postcode( 'abcde', 'IT' ) );
		$this->assertEquals( 'AB CDE', \WC_Validation::format_postcode( 'abcde', 'GB' ) );
		$this->assertEquals( 'AB CDE', \WC_Validation::format_postcode( 'abcde', 'CA' ) );
	}
	/**
	 * Test format_phone()
	 *
	 * @since 2.4
	 */
	public function test_format_phone() {
		$this->assertEquals( '+00-000-00-00-000', \WC_Validation::format_phone( '+00.000.00.00.000' ) );
		$this->assertEquals( '+00 000 00 00 000', \WC_Validation::format_phone( '+00 000 00 00 000' ) );
	}
}
