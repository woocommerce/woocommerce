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
	 * @testdox Should expose Chhattisgarh under the GST state code `CG` and not the legacy `CT`.
	 */
	public function test_indian_state_chhattisgarh_uses_cg_code() {
		$in_states = wc()->countries->get_states( 'IN' );

		$this->assertIsArray( $in_states, 'Indian states list should be available.' );
		$this->assertArrayHasKey( 'CG', $in_states, 'Chhattisgarh should be keyed by the GST state code `CG`.' );
		$this->assertSame( 'Chhattisgarh', $in_states['CG'], '`CG` should map to Chhattisgarh.' );
		$this->assertArrayNotHasKey( 'CT', $in_states, 'The legacy `CT` code for Chhattisgarh should no longer be present in the Indian states list.' );
	}
}
