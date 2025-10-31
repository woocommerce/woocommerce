<?php
declare( strict_types=1 );

/**
 * Tests for the WC_Countries class.
 */
class WC_Countries_Test extends \WC_Unit_Test_Case {
	/**
	 * Tests for `get_alpha_2`.
	 *
	 * @param mixed $country_code Country code to test.
	 * @param mixed $expected     Expected result.
	 * @return void
	 *
	 * @dataProvider provide_test_get_alpha_2
	 */
	public function test_get_alpha_2( $country_code, $expected ) {
		$this->assertEquals( $expected, wc()->countries->get_alpha_2( $country_code ) );
	}

	/**
	 * Provider for `test_get_alpha_2`.
	 *
	 * @return array
	 */
	public function provide_test_get_alpha_2() {
		return array(
			'empty'   => array(
				'country code'    => '',
				'expected result' => null
			),
			'integer' => array(
				'country code'    => 123,
				'expected result' => null
			),
			'invalid' => array(
				'country code'    => 'invalid',
				'expected result' => null
			),
			'valid'   => array(
				'country code'    => 'USA',
				'expected result' => 'US'
			),
		);
	}
}
