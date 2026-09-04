<?php
declare( strict_types=1 );

/**
 * Tests for the WC_Countries class.
 */
class WC_Countries_Test extends \WC_Unit_Test_Case {
	/**
	 * Tests for `get_country_from_alpha_3_code`.
	 *
	 * @param mixed $country_code Country code to test.
	 * @param mixed $expected     Expected result.
	 * @return void
	 *
	 * @dataProvider provide_test_get_country_from_alpha_3_code
	 */
	public function test_get_country_from_alpha_3_code( $country_code, $expected ) {
		$this->assertEquals( $expected, wc()->countries->get_country_from_alpha_3_code( $country_code ) );
	}

	/**
	 * @testdox 'get_address_fields' carries a locale-only 'hidden' flag through the merge with the default fields.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $hidden Value of the 'hidden' flag set via the locale filter.
	 * @return void
	 */
	public function test_get_address_fields_includes_hidden_flag_from_locale( $hidden ) {
		$locale_filter = function ( $locale ) use ( $hidden ) {
			$locale['ES']['postcode']['hidden'] = $hidden;
			return $locale;
		};
		add_filter( 'woocommerce_get_country_locale', $locale_filter );

		$fields = ( new WC_Countries() )->get_address_fields( 'ES', 'billing_' );

		remove_filter( 'woocommerce_get_country_locale', $locale_filter );

		$this->assertSame( $hidden, $fields['billing_postcode']['hidden'], 'The locale hidden flag should survive the merge with the default fields.' );
	}

	/**
	 * @testdox 'get_address_fields' does not add a 'hidden' key when the locale does not define one.
	 *
	 * @return void
	 */
	public function test_get_address_fields_has_no_hidden_key_when_locale_does_not_define_one() {
		$fields = ( new WC_Countries() )->get_address_fields( 'ES', 'billing_' );

		$this->assertArrayNotHasKey( 'hidden', $fields['billing_postcode'] );
	}

	/**
	 * @testdox 'get_address_fields' hides the postcode and makes the state optional for Qatar, matching the UAE.
	 *
	 * @return void
	 */
	public function test_get_address_fields_relaxes_postcode_and_state_for_qatar() {
		$fields = ( new WC_Countries() )->get_address_fields( 'QA', 'billing_' );

		$this->assertTrue( $fields['billing_postcode']['hidden'] ?? false, 'Qatar addresses have no postcode, so the field should be hidden.' );
		$this->assertFalse( $fields['billing_postcode']['required'], 'Qatar addresses have no postcode, so the field should not be required.' );
		$this->assertFalse( $fields['billing_state']['required'], 'WooCommerce lists no subdivisions for Qatar, so the state should not be required.' );
	}

	/**
	 * Provider for `test_get_country_from_alpha_3_code`.
	 *
	 * @return array
	 */
	public function provide_test_get_country_from_alpha_3_code() {
		return array(
			'empty'   => array(
				'country code'    => '',
				'expected result' => null,
			),
			'integer' => array(
				'country code'    => 123,
				'expected result' => null,
			),
			'invalid' => array(
				'country code'    => 'invalid',
				'expected result' => null,
			),
			'valid'   => array(
				'country code'    => 'USA',
				'expected result' => 'US',
			),
		);
	}
}
