<?php
/**
 * Unit tests for validation.
 *
 * @package WooCommerce\Tests\Util
 */

/**
 * Class WC_Tests_Validation.
 * @since 2.3
 */
class WC_Tests_Validation extends WC_Unit_Test_Case {
	/**
	 * Test is_email().
	 *
	 * @since 2.3
	 */
	public function test_is_email() {
		$this->assertEquals( 'email@domain.com', WC_Validation::is_email( 'email@domain.com' ) );
		$this->assertFalse( WC_Validation::is_email( 'not a mail' ) );
		$this->assertFalse( WC_Validation::is_email( 'http://test.com' ) );
	}

	/**
	 * Data provider for test_is_phone.
	 *
	 * @since 2.4
	 */
	public function data_provider_test_is_phone() {
		return array(
			array( true, WC_Validation::is_phone( '+00 000 00 00 000' ) ),
			array( true, WC_Validation::is_phone( '+00-000-00-00-000' ) ),
			array( true, WC_Validation::is_phone( '(000) 00 00 000' ) ),
			array( true, WC_Validation::is_phone( '+00.000.00.00.000' ) ),
			array( false, WC_Validation::is_phone( '+00 aaa dd ee fff' ) ),
		);
	}

	/**
	 * Test is_phone().
	 *
	 * @param mixed $assert Expected value.
	 * @param mixed $values Actual value.
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
		$it = array(
			array( true, WC_Validation::is_postcode( '99999', 'IT' ) ),
			array( false, WC_Validation::is_postcode( '9999', 'IT' ) ),
			array( false, WC_Validation::is_postcode( 'ABC 999', 'IT' ) ),
			array( false, WC_Validation::is_postcode( 'ABC-999', 'IT' ) ),
			array( false, WC_Validation::is_postcode( 'ABC_123', 'IT' ) ),
		);

		$gb = array(
			array( true, WC_Validation::is_postcode( 'A9 9AA', 'GB' ) ),
			array( false, WC_Validation::is_postcode( '99999', 'GB' ) ),
		);

		$us = array(
			array( true, WC_Validation::is_postcode( '99999', 'US' ) ),
			array( true, WC_Validation::is_postcode( '99999-9999', 'US' ) ),
			array( false, WC_Validation::is_postcode( 'ABCDE', 'US' ) ),
			array( false, WC_Validation::is_postcode( 'ABCDE-9999', 'US' ) ),
		);

		$ch = array(
			array( true, WC_Validation::is_postcode( '9999', 'CH' ) ),
			array( false, WC_Validation::is_postcode( '99999', 'CH' ) ),
			array( false, WC_Validation::is_postcode( 'ABCDE', 'CH' ) ),
		);

		$br = array(
			array( true, WC_Validation::is_postcode( '99999-999', 'BR' ) ),
			array( true, WC_Validation::is_postcode( '99999999', 'BR' ) ),
			array( false, WC_Validation::is_postcode( '99999 999', 'BR' ) ),
			array( false, WC_Validation::is_postcode( '99999-ABC', 'BR' ) ),
		);

		$ca = array(
			array( true, WC_Validation::is_postcode( 'A9A 9A9', 'CA' ) ),
			array( true, WC_Validation::is_postcode( 'A9A9A9', 'CA' ) ),
			array( true, WC_Validation::is_postcode( 'a9a9a9', 'CA' ) ),
			array( false, WC_Validation::is_postcode( 'D0A 9A9', 'CA' ) ),
			array( false, WC_Validation::is_postcode( '99999', 'CA' ) ),
			array( false, WC_Validation::is_postcode( 'ABC999', 'CA' ) ),
			array( false, WC_Validation::is_postcode( '0A0A0A', 'CA' ) ),
		);

		$nl = array(
			array( true, WC_Validation::is_postcode( '3852GC', 'NL' ) ),
			array( true, WC_Validation::is_postcode( '3852 GC', 'NL' ) ),
			array( true, WC_Validation::is_postcode( '3852 gc', 'NL' ) ),
			array( false, WC_Validation::is_postcode( '3852SA', 'NL' ) ),
			array( false, WC_Validation::is_postcode( '3852 SA', 'NL' ) ),
			array( false, WC_Validation::is_postcode( '3852 sa', 'NL' ) ),
		);

		$si = array(
			array( true, WC_Validation::is_postcode( '1234', 'SI' ) ),
			array( true, WC_Validation::is_postcode( '1000', 'SI' ) ),
			array( true, WC_Validation::is_postcode( '9876', 'SI' ) ),
			array( false, WC_Validation::is_postcode( '12345', 'SI' ) ),
			array( false, WC_Validation::is_postcode( '0123', 'SI' ) ),
		);

		$ba = array(
			array( true, WC_Validation::is_postcode( '71000', 'BA' ) ),
			array( true, WC_Validation::is_postcode( '78256', 'BA' ) ),
			array( true, WC_Validation::is_postcode( '89240', 'BA' ) ),
			array( false, WC_Validation::is_postcode( '61000', 'BA' ) ),
			array( false, WC_Validation::is_postcode( '7850', 'BA' ) ),
		);

		$jp = array(
			array( true, WC_Validation::is_postcode( '1340088', 'JP' ) ),
			array( true, WC_Validation::is_postcode( '134-0088', 'JP' ) ),
			array( false, WC_Validation::is_postcode( '1340-088', 'JP' ) ),
			array( false, WC_Validation::is_postcode( '12345', 'JP' ) ),
			array( false, WC_Validation::is_postcode( '0123', 'JP' ) ),
		);

		return array_merge( $it, $gb, $us, $ch, $br, $ca, $nl, $si, $ba, $jp );
	}

	/**
	 * Test is_postcode().
	 *
	 * @param mixed $assert Expected value.
	 * @param mixed $values Actual value.
	 *
	 * @dataProvider data_provider_test_is_postcode
	 * @since 2.4
	 */
	public function test_is_postcode( $assert, $values ) {
		$this->assertEquals( $assert, $values );
	}

	/**
	 * Data provider for test_is_gb_postcode.
	 *
	 * @since 2.4
	 */
	public function data_provider_test_is_gb_postcode() {
		return array(
			array( true, WC_Validation::is_gb_postcode( 'AA9A 9AA' ) ),
			array( true, WC_Validation::is_gb_postcode( 'A9A 9AA' ) ),
			array( true, WC_Validation::is_gb_postcode( 'A9 9AA' ) ),
			array( true, WC_Validation::is_gb_postcode( 'A99 9AA' ) ),
			array( true, WC_Validation::is_gb_postcode( 'AA99 9AA' ) ),
			array( true, WC_Validation::is_gb_postcode( 'BFPO 801' ) ),
			array( false, WC_Validation::is_gb_postcode( '99999' ) ),
			array( false, WC_Validation::is_gb_postcode( '9999 999' ) ),
			array( false, WC_Validation::is_gb_postcode( '999 999' ) ),
			array( false, WC_Validation::is_gb_postcode( '99 999' ) ),
			array( false, WC_Validation::is_gb_postcode( '9A A9A' ) ),
		);
	}

	/**
	 * Test is_gb_postcode().
	 *
	 * @param mixed $assert Expected value.
	 * @param mixed $values Actual value.
	 *
	 * @dataProvider data_provider_test_is_gb_postcode
	 * @since 2.4
	 */
	public function test_is_gb_postcode( $assert, $values ) {
		$this->assertEquals( $assert, $values );
	}

	/**
	 * Data provider for test_format_postcode.
	 *
	 * @since 2.4
	 */
	public function data_provider_test_format_postcode() {
		return array(
			array( '99999', WC_Validation::format_postcode( '99999', 'IT' ) ),
			array( '99999', WC_Validation::format_postcode( ' 99999 ', 'IT' ) ),
			array( '99999', WC_Validation::format_postcode( '999 99', 'IT' ) ),
			array( 'ABCDE', WC_Validation::format_postcode( 'abcde', 'IT' ) ),
			array( 'AB CDE', WC_Validation::format_postcode( 'abcde', 'GB' ) ),
			array( 'AB CDE', WC_Validation::format_postcode( 'abcde', 'CA' ) ),
		);
	}

	/**
	 * Test format_postcode().
	 *
	 * @param mixed $assert Expected value.
	 * @param mixed $values Actual value.
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
		$this->assertEquals( '+00-000-00-00-000', WC_Validation::format_phone( '+00.000.00.00.000' ) );
		$this->assertEquals( '+00 000 00 00 000', WC_Validation::format_phone( '+00 000 00 00 000' ) );
	}
}
