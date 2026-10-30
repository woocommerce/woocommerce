<?php
/**
 * Validation functions tests
 *
 * @package WooCommerce\Tests\Validation.
 */

use Automattic\WooCommerce\Internal\Utilities\PostcodeValidation;

/**
 * Class WC_Validation_Test.
 */
class WC_Validation_Test extends \WC_Unit_Test_Case {
	/**
	 * Data provider for test_is_phone().
	 */
	public function data_provider_test_is_phone(): array {
		return array(
			array( true, '+00 000 00 00 000', null ),
			array( true, '+00-000-00-00-000', null ),
			array( true, '(000) 00 00 000', null ),
			array( true, '+00.000.00.00.000', null ),
			array( false, '+00 aaa dd ee fff', null ),
		);
	}

	/**
	 * Test phone validation (default behaviour).
	 *
	 * @dataProvider data_provider_test_is_phone
	 *
	 * @param bool        $expected Expected result.
	 * @param string      $phone    Phone number to validate.
	 * @param string|null $country  Country code.
	 */
	public function test_is_phone( bool $expected, string $phone, ?string $country ): void {
		$this->assertSame( $expected, WC_Validation::is_phone( $phone, $country ) );
	}

	/**
	 * The woocommerce_validate_phone filter can override the validation result.
	 */
	public function test_is_phone_filter_can_override_result(): void {
		$callback = function ( $valid, $phone, $country ) {
			if ( 'IR' === $country ) {
				$phone = str_replace(
					array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
					range( 0, 9 ),
					$phone
				);

				return (bool) preg_match( '/^(0|0098|\+98)?(9\d{9}|[1-8]\d{9,10})$/', $phone );
			}

			return $valid;
		};

		add_filter( 'woocommerce_validate_phone', $callback, 10, 3 );
		try {
			$this->assertTrue( WC_Validation::is_phone( '+۹۸۹۱۵۱۱۱۲۲۳۳', 'IR' ) );
			$this->assertTrue( WC_Validation::is_phone( '۰۰۹۸۹۱۵۱۱۱۲۲۳۳', 'IR' ) );
			$this->assertTrue( WC_Validation::is_phone( '۰۹۱۵۱۱۱۲۲۳۳', 'IR' ) );
		} finally {
			remove_filter( 'woocommerce_validate_phone', $callback, 10 );
		}
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
			array( true, '12 345', 'SE' ),
			array( false, 'ABC 45', 'SE' ),
		);

		$li = array(
			array( true, '9482', 'LI' ),
			array( true, '9495', 'LI' ),
			array( true, 'FL-9495', 'LI' ),
			array( false, '8512', 'LI' ),
			array( false, '0123', 'LI' ),
			array( false, '948A', 'LI' ),
		);

		$lv = array(
			array( true, 'LV-1050', 'LV' ),
			array( true, 'lv-1050', 'LV' ),
			// Older versions accepted the postcode without the country prefix.
			array( true, '1050', 'LV' ),
			array( true, 'LV-0123', 'LV' ),
			array( false, 'LV-105', 'LV' ),
			array( false, '10500', 'LV' ),
			array( false, 'ZZ-1050', 'LV' ),
			array( false, 'LV-ABCD', 'LV' ),
			// The country prefix without a separator, as produced by wc_normalize_postcode().
			array( true, 'LV1050', 'LV' ),
			array( true, 'lv1050', 'LV' ),
			array( false, 'LVLV1050', 'LV' ),
			// Spaces and hyphens are ignored wherever they appear, so the result
			// is the same for the typed value and the formatted one.
			array( true, 'LV 1050', 'LV' ),
			array( true, "LV\t1050", 'LV' ),
			array( true, 'LV--1050', 'LV' ),
			array( true, 'LV  1050', 'LV' ),
			array( true, "LV-1050\n", 'LV' ),
			array( true, 'LV-1050 ', 'LV' ),
			array( false, 'LV_1050', 'LV' ),
			array( false, 'LV-1050x', 'LV' ),
		);

		// 'BFP O12' is what wc_format_postcode() makes of 'BFPO 12'.
		$gb = array(
			array( true, 'SW1A 1AA', 'GB' ),
			array( true, 'SW1A  1AA', 'GB' ),
			array( true, 'sw1a1aa', 'GB' ),
			array( true, 'BFPO 12', 'GB' ),
			array( true, 'BFP O12', 'GB' ),
			array( true, 'GIR 0AA', 'GB' ),
			array( false, 'SW1A 1A', 'GB' ),
			array( false, '12345', 'GB' ),
		);

		// Google's postal prefixes, and the ones older versions accepted, are optional.
		$prefix = array(
			array( true, 'MD-2001', 'MD' ),
			array( true, 'MD2001', 'MD' ),
			array( true, '2001', 'MD' ),
			array( true, 'AZ 1000', 'AZ' ),
			array( true, 'L-1234', 'LU' ),
			array( true, 'CH-2544', 'CH' ),
			array( true, 'SI-1000', 'SI' ),
			array( true, 'AX-22100', 'AX' ),
			array( false, 'US90210', 'US' ),
			array( false, 'PT1234-567', 'PT' ),
			array( true, 'DK-1000', 'DK' ),
			array( true, 'SK-115 03', 'SK' ),
			array( true, 'CP 1101', 'SV' ),
			array( true, '1101', 'SV' ),
			array( false, 'AX-22100', 'FI' ),
			array( false, 'ZZ-2001', 'MD' ),
			array( false, 'MDMD2001', 'MD' ),
			array( true, '00100', 'FI' ),
			array( true, 'FI-00100', 'FI' ),
			array( true, '10000', 'HR' ),
			array( true, 'HR-10000', 'HR' ),
			array( true, '12345', 'SE' ),
			array( true, 'SE-12345', 'SE' ),
			array( true, '01100', 'LT' ),
			array( true, 'LT-01100', 'LT' ),
			array( false, '100', 'AD' ),
			array( true, 'AD100', 'AD' ),
			array( false, 'ADAD100', 'AD' ),
			array( false, '15094', 'BB' ),
			array( true, 'BB15094', 'BB' ),
			array( false, 'BBBB15094', 'BB' ),
		);

		$ie = array(
			array( true, 'D02 X285', 'IE' ),
			array( true, 'D6W 1234', 'IE' ),
			array( false, 'ABCDEFG', 'IE' ),
			array( false, 'B23F854', 'IE' ),
		);

		// Argentina accepts the old four digit codes and the lettered ones.
		$ar = array(
			array( true, '1425', 'AR' ),
			array( true, 'C1425ABC', 'AR' ),
			array( false, '142', 'AR' ),
		);

		$pt = array(
			array( true, '1000-205', 'PT' ),
			array( true, '1000205', 'PT' ),
			array( false, '1000', 'PT' ),
		);

		// Formats that older versions accepted pass next to Google's format.
		$legacy = array(
			array( true, '1209', 'PY' ),
			array( true, '001001', 'PY' ),
			array( false, '12345', 'PY' ),
			array( true, '12345', 'SO' ),
			array( true, 'JH 09010', 'SO' ),
			array( true, 'A1234B', 'EC' ),
			array( true, '090105', 'EC' ),
			array( true, '123456', 'IL' ),
			array( true, 'AD999', 'AD' ),
		);

		$misc = array(
			array( true, '100-01', 'TW' ),
			array( true, '910001', 'CO' ),
			array( false, '91001', 'CO' ),
			array( true, '500234', 'TT' ),
			array( false, '50023', 'TT' ),
			array( true, '2 000', 'AU' ),
			array( true, '90210 ', 'US' ),
			array( true, '90210-1234', 'US' ),
			array( false, '9021', 'US' ),
			array( false, '2000#', 'AU' ),
			array( false, 'anything#', 'XX' ),
			array( true, 'anything', 'XX' ),
		);

		return array_merge( $cz, $se, $li, $lv, $gb, $prefix, $ie, $ar, $pt, $legacy, $misc );
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

	/**
	 * A rule that does not compile makes the server accept any postcode for
	 * that country, so every generated rule must compile.
	 */
	public function test_every_postcode_rule_compiles(): void {
		$rules = PostcodeValidation::get_rules();

		$this->assertGreaterThan( 100, count( $rules ) );
		foreach ( array_keys( $rules ) as $country ) {
			$this->assertNotNull( PostcodeValidation::validate( '', $country ), "The {$country} rule does not compile." );
		}
	}

	/**
	 * The woocommerce_validate_postcode filter can still override a shared rule.
	 */
	public function test_postcode_filter_can_override_shared_rule(): void {
		$callback = static function ( $valid, $postcode, $country ) {
			return 'US' === $country && 'ABCDE' === $postcode ? true : $valid;
		};

		add_filter( 'woocommerce_validate_postcode', $callback, 10, 3 );
		try {
			$this->assertTrue( WC_Validation::is_postcode( 'ABCDE', 'US' ) );
		} finally {
			remove_filter( 'woocommerce_validate_postcode', $callback, 10 );
		}
	}
}
