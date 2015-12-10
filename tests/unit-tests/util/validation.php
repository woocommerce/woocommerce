<?php
namespace WooCommerce\Tests\Util;

/**
 * Class Validation.
 * @package WooCommerce\Tests\Util
 * @since 2.3
 */
class Validation extends \WC_Unit_Test_Case {
	/**
	 * Test is_email().
	 *
	 * @since 2.3
	 */
	public function test_is_email() {
		$this->assertEquals( 'email@domain.com', \WC_Validation::is_email( 'email@domain.com' ) );
		$this->assertFalse( \WC_Validation::is_email( 'not a mail' ) );
		$this->assertFalse( \WC_Validation::is_email( 'http://test.com' ) );
	}

	/**
	 * Data provider for test_is_phone.
	 *
	 * @since 2.4
	 */
	public function data_provider_test_is_phone() {
		return array(
			array( true, \WC_Validation::is_phone( '+00 000 00 00 000' ) ),
			array( true, \WC_Validation::is_phone( '+00-000-00-00-000' ) ),
			array( true, \WC_Validation::is_phone( '(000) 00 00 000' ) ),
			array( false, \WC_Validation::is_phone( '+00.000.00.00.000' ) ),
			array( false, \WC_Validation::is_phone( '+00 aaa dd ee fff' ) )
		);
	}

	/**
	 * Test is_phone().
	 *
	 * @dataProvider data_provider_test_is_phone
	 * @since 2.3
	 */
	public function test_is_phone( $assert, $values ) {
		$this->assertEquals( $assert, $values );
	}

	/**
	 * Data provider for test_is_postcode().
	 *
	 * @since 2.4
	 */
	public function data_provider_test_is_postcode() {
		$generic = array(
			array( true, \WC_Validation::is_postcode( '99999', 'IT' ) ),
			array( true, \WC_Validation::is_postcode( '99999', 'IT' ) ),
			array( true, \WC_Validation::is_postcode( '9999', 'IT' ) ),
			array( true, \WC_Validation::is_postcode( 'ABC 999', 'IT' ) ),
			array( true, \WC_Validation::is_postcode( 'ABC-999', 'IT' ) ),
			array( false, \WC_Validation::is_postcode( 'ABC_123', 'IT' ) )
		);

		$gb = array(
			array( true, \WC_Validation::is_postcode( 'A9 9AA', 'GB' ) ),
			array( false, \WC_Validation::is_postcode( '99999', 'GB' ) )
		);

		$us = array(
			array( true, \WC_Validation::is_postcode( '99999', 'US' ) ),
			array( true, \WC_Validation::is_postcode( '99999-9999', 'US' ) ),
			array( false, \WC_Validation::is_postcode( 'ABCDE', 'US' ) ),
			array( false, \WC_Validation::is_postcode( 'ABCDE-9999', 'US' ) )
		);

		$ch = array(
			array( true, \WC_Validation::is_postcode( '9999', 'CH' ) ),
			array( false, \WC_Validation::is_postcode( '99999', 'CH' ) ),
			array( false, \WC_Validation::is_postcode( 'ABCDE', 'CH' ) )
		);

		$br = array(
			array( true, \WC_Validation::is_postcode( '99999-999', 'BR' ) ),
			array( true, \WC_Validation::is_postcode( '99999999', 'BR' ) ),
			array( false, \WC_Validation::is_postcode( '99999 999', 'BR' ) ),
			array( false, \WC_Validation::is_postcode( '99999-ABC', 'BR' ) )
		);

		return array_merge( $generic, $gb, $us, $ch, $br );
	}

	/**
	 * Test is_postcode().
	 *
	 * @dataProvider data_provider_test_is_postcode
	 * @since 2.4
	 */
	public function test_is_postcode( $assert, $values ) {
		$this->assertEquals( $assert, $values );
	}

	/**
	 * Data provider for test_is_GB_postcode.
	 *
	 * @since 2.4
	 */
	public function data_provider_test_is_GB_postcode() {
		return array(
			array( true, \WC_Validation::is_GB_postcode( 'AA9A 9AA' ) ),
			array( true, \WC_Validation::is_GB_postcode( 'A9A 9AA' ) ),
			array( true, \WC_Validation::is_GB_postcode( 'A9 9AA' ) ),
			array( true, \WC_Validation::is_GB_postcode( 'A99 9AA' ) ),
			array( true, \WC_Validation::is_GB_postcode( 'AA99 9AA' ) ),
			array( true, \WC_Validation::is_GB_postcode( 'BFPO 801' ) ),
			array( false, \WC_Validation::is_GB_postcode( '99999' ) ),
			array( false, \WC_Validation::is_GB_postcode( '9999 999' ) ),
			array( false, \WC_Validation::is_GB_postcode( '999 999' ) ),
			array( false, \WC_Validation::is_GB_postcode( '99 999' ) ),
			array( false, \WC_Validation::is_GB_postcode( '9A A9A' ) )
		);
	}

	/**
	 * Test is_GB_postcode().
	 *
	 * @dataProvider data_provider_test_is_GB_postcode
	 * @since 2.4
	 */
	public function test_is_GB_postcode( $assert, $values ) {
		$this->assertEquals( $assert, $values );
	}

	/**
	 * Data provider for test_format_postcode.
	 *
	 * @since 2.4
	 */
	public function data_provider_test_format_postcode() {
		return array(
			array( '99999', \WC_Validation::format_postcode( '99999', 'IT' ) ),
			array( '99999', \WC_Validation::format_postcode( ' 99999 ', 'IT' ) ),
			array( '99999', \WC_Validation::format_postcode( '999 99', 'IT' ) ),
			array( 'ABCDE', \WC_Validation::format_postcode( 'abcde', 'IT' ) ),
			array( 'AB CDE', \WC_Validation::format_postcode( 'abcde', 'GB' ) ),
			array( 'AB CDE', \WC_Validation::format_postcode( 'abcde', 'CA' ) )
		);
	}

	/**
	 * Test format_postcode().
	 *
	 * @dataProvider data_provider_test_format_postcode
	 * @since 2.4
	 */
	public function test_format_postcode( $assert, $values ) {
		$this->assertEquals( $assert, $values );
	}

	/**
	 * Test format_phone().
	 *
	 * @since 2.4
	 */
	public function test_format_phone() {
		$this->assertEquals( '+00-000-00-00-000', \WC_Validation::format_phone( '+00.000.00.00.000' ) );
		$this->assertEquals( '+00 000 00 00 000', \WC_Validation::format_phone( '+00 000 00 00 000' ) );
	}
}
