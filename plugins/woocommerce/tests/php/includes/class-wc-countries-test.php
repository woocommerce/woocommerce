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
	 * @testdox Should emit the canonical boolean `selected` attribute for the selected country option.
	 */
	public function test_country_dropdown_options_emits_boolean_selected_attribute(): void {
		ob_start();
		wc()->countries->country_dropdown_options( 'GB', '*' );
		$output = ob_get_clean();

		$this->assertStringNotContainsString(
			'selected="selected"',
			$output,
			'Country dropdown options must not emit the non-boolean `selected="selected"` form.'
		);
		$this->assertMatchesRegularExpression(
			'/<option selected value="GB">/',
			$output,
			'The selected country option should use the boolean `selected` attribute form.'
		);
	}

	/**
	 * @testdox Should emit the canonical boolean `selected` attribute for the selected state option.
	 */
	public function test_country_dropdown_options_emits_boolean_selected_for_state_option(): void {
		// Use a country that has states defined; US is guaranteed by core defaults.
		ob_start();
		wc()->countries->country_dropdown_options( 'US', 'CA' );
		$output = ob_get_clean();

		$this->assertStringNotContainsString(
			'selected="selected"',
			$output,
			'Country dropdown state options must not emit the non-boolean `selected="selected"` form.'
		);
		$this->assertMatchesRegularExpression(
			'/<option value="US:CA" selected>/',
			$output,
			'The selected state option should use the boolean `selected` attribute form.'
		);
	}
}
