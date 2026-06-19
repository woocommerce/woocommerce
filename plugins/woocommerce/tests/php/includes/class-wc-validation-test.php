<?php
/**
 * Validation functions tests
 *
 * @package WooCommerce\Tests\Validation.
 */

/**
 * Class WC_Validation_Test.
 */
class WC_Validation_Test extends \WC_Unit_Test_Case {
	/**
	 * Data provider for test_is_phone()
	 */
	public function data_provider_test_is_phone() {
		$enPhones = array(
			array( true, WC_Validation::is_phone( '+00 000 00 00 000' ) ),
			array( true, WC_Validation::is_phone( '+00-000-00-00-000' ) ),
			array( true, WC_Validation::is_phone( '(000) 00 00 000' ) ),
			array( true, WC_Validation::is_phone( '+00.000.00.00.000' ) ),
			array( false, WC_Validation::is_phone( '+00 aaa dd ee fff' ) ),
		);

		// Check non-English phone numbers, This test check Persian (Farsi) phone numbers.
		$faPhoneValidation = function ( $valid, $phone, $country ) {
			$phone = str_replace(
				array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
				range( 0, 9 ),
				$phone
			);

			return preg_match( '/^(0|0098|\+98)?(9\d{9}|[1-8]\d{9,10})$/', $phone );
		};
		add_filter( 'woocommerce_validate_phone', $faPhoneValidation, 10, 3 );
		$faPhones = array(
			array( true, WC_Validation::is_phone( '+۹۸۹۱۵۱۱۱۲۲۳۳' ) ),
			array( true, WC_Validation::is_phone( '۰۰۹۸۹۱۵۱۱۱۲۲۳۳' ) ),
			array( true, WC_Validation::is_phone( '۰۹۱۵۱۱۱۲۲۳۳' ) ),
		);
		remove_filter( 'woocommerce_validate_phone', $faPhoneValidation );

		return array_merge( $enPhones, $faPhones );
	}

	/**
	 * Test phone validation.
	 *
	 * @param mixed $assert Expected value.
	 * @param mixed $values Actual value.
	 *
	 * @dataProvider data_provider_test_is_phone
	 */
	public function test_is_phone( $assert, $values ) {
		$this->assertEquals( $assert, $values );
	}

	/**
	 * Data provider for test_is_postcode().
	 */
	public function data_provider_test_is_postcode(): array {
		$cz = array(
			array( true, '115 03', 'CZ' ),
			array( true, 'CZ-115 03', 'CZ' ),
		);

		$se = array(
			array( true, '123 45', 'SE' ),
			array( true, '12345', 'SE' ),
			array( false, '12 345', 'SE' ),
			array( false, 'ABC 45', 'SE' ),
		);

		$li = array(
			array( true, '9482', 'LI' ),
			array( true, '9495', 'LI' ),
			array( false, '8512', 'LI' ),
			array( false, '0123', 'LI' ),
			array( false, '948A', 'LI' ),
		);

		return array_merge( $cz, $se, $li );
	}

	/**
	 * Test postcode validation.
	 *
	 * @dataProvider data_provider_test_is_postcode
	 *
	 * @param bool   $expected Expected result.
	 * @param string $postcode Postcode param for is_postcode.
	 * @param string $country Country param for is_postcode.
	 */
	public function test_is_postcode( bool $expected, string $postcode, string $country ): void {
		$this->assertSame( $expected, WC_Validation::is_postcode( $postcode, $country ) );
	}
}
