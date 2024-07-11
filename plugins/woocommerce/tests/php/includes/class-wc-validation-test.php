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
