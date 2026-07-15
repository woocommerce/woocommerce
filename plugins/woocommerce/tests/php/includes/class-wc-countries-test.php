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

	/**
	 * @testdox Nepal uses current provinces instead of legacy zones.
	 */
	public function test_nepal_uses_current_provinces_instead_of_legacy_zones(): void {
		$expected = array(
			'P1' => 'Koshi',
			'P2' => 'Madhesh',
			'P3' => 'Bagmati',
			'P4' => 'Gandaki',
			'P5' => 'Lumbini',
			'P6' => 'Karnali',
			'P7' => 'Sudurpashchim',
		);

		$this->assertSame(
			$expected,
			wc()->countries->get_states( 'NP' ),
			'Nepal should use current province codes and official names.'
		);
	}

	/**
	 * @testdox Legacy Nepal zone codes remain readable in historical addresses.
	 */
	public function test_legacy_nepal_zone_codes_remain_readable_in_historical_addresses(): void {
		$formatted_address = wc()->countries->get_formatted_address(
			array(
				'address_1' => '123 Example Street',
				'city'      => 'Kathmandu',
				'state'     => 'BAG',
				'country'   => 'NP',
			),
			', '
		);

		$this->assertStringContainsString( 'Bagmati', $formatted_address, 'Legacy Nepal state codes should retain their historical labels.' );
		$this->assertStringNotContainsString( 'BAG', $formatted_address, 'Historical addresses should not fall back to an unexplained raw state code.' );
	}
}
