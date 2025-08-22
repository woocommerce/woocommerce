<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\ValidationUtils;

/**
 * A collection of tests for the ValidationUtils class.
 */
class ValidationUtilsTest extends \WC_Unit_Test_Case {

	/**
	 * ValidationUtils instance.
	 *
	 * @var ValidationUtils
	 */
	private $validation_utils;

	/**
	 * Original WC countries instance for restoration.
	 *
	 * @var \WC_Countries
	 */
	private $original_wc_countries;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->validation_utils = new ValidationUtils();

		// Store original countries instance.
		$this->original_wc_countries = WC()->countries;
	}

	/**
	 * Teardown test.
	 */
	public function tearDown(): void {
		// Restore original countries instance.
		WC()->countries = $this->original_wc_countries;
		parent::tearDown();
	}

	/**
	 * @testdox `validate_and_correct_country` should return the same country and state when they are valid for billing addresses.
	 */
	public function test_validate_and_correct_country_valid_billing_address() {
		// Mock WC()->countries to return specific allowed countries.
		$this->mock_wc_countries(
			[
				'US' => 'United States',
				'CA' => 'Canada',
			],
			[ 'US' => 'United States' ]
		);

		$result = $this->validation_utils->validate_and_correct_country( 'US', 'CA', 'billing' );

		$this->assertEquals( 'US', $result['country'] );
		$this->assertEquals( 'CA', $result['state'] );
	}

	/**
	 * @testdox `validate_and_correct_country` should return the same country and state when they are valid for shipping addresses.
	 */
	public function test_validate_and_correct_country_valid_shipping_address() {
		// Mock WC()->countries to return specific allowed countries.
		$this->mock_wc_countries(
			[ 'US' => 'United States' ],
			[
				'US' => 'United States',
				'CA' => 'Canada',
			]
		);

		$result = $this->validation_utils->validate_and_correct_country( 'CA', 'BC', 'shipping' );

		$this->assertEquals( 'CA', $result['country'] );
		$this->assertEquals( 'BC', $result['state'] );
	}

	/**
	 * @testdox `validate_and_correct_country` should correct invalid billing country to first available.
	 */
	public function test_validate_and_correct_country_invalid_billing_country() {
		// Mock WC()->countries to return specific allowed countries.
		$this->mock_wc_countries(
			[
				'US' => 'United States',
				'CA' => 'Canada',
			],
			[ 'US' => 'United States' ]
		);

		// Test with invalid billing country 'GB'.
		$result = $this->validation_utils->validate_and_correct_country( 'GB', 'London', 'billing' );

		$this->assertEquals( 'US', $result['country'] ); // Should be corrected to first available.
		$this->assertEquals( '', $result['state'] ); // State should be reset.
	}

	/**
	 * @testdox `validate_and_correct_country` should correct invalid shipping country to first available.
	 */
	public function test_validate_and_correct_country_invalid_shipping_country() {
		// Mock WC()->countries to return specific allowed countries.
		$this->mock_wc_countries(
			[ 'US' => 'United States' ],
			[
				'CA' => 'Canada',
				'AU' => 'Australia',
			]
		);

		// Test with invalid shipping country 'GB'.
		$result = $this->validation_utils->validate_and_correct_country( 'GB', 'London', 'shipping' );

		$this->assertEquals( 'CA', $result['country'] ); // Should be corrected to first available shipping country.
		$this->assertEquals( '', $result['state'] ); // State should be reset.
	}

	/**
	 * @testdox `validate_and_correct_country` should handle empty country gracefully.
	 */
	public function test_validate_and_correct_country_empty_country() {
		// Mock WC()->countries to return specific allowed countries.
		$this->mock_wc_countries(
			[
				'US' => 'United States',
				'CA' => 'Canada',
			],
			[ 'US' => 'United States' ]
		);

		$result = $this->validation_utils->validate_and_correct_country( '', 'CA', 'billing' );

		$this->assertEquals( '', $result['country'] );
		// State validation for empty country usually passes, so state may be preserved.
		// This is actually correct behavior - we don't want to modify valid state if country is just empty.
		$this->assertEquals( 'CA', $result['state'] );
	}

	/**
	 * @testdox `validate_and_correct_country` should reset invalid state for valid country.
	 */
	public function test_validate_and_correct_country_invalid_state() {
		// Set up a country with no states to simulate invalid state validation.
		$this->mock_wc_countries( [ 'US' => 'United States' ], [ 'US' => 'United States' ] );

		// Mock WC_Countries to return empty states for US (so any state will be invalid).
		$countries_mock = $this->getMockBuilder( \WC_Countries::class )
			->onlyMethods( [ 'get_allowed_countries', 'get_shipping_countries', 'get_states' ] )
			->getMock();

		$countries_mock->method( 'get_allowed_countries' )
			->willReturn( [ 'US' => 'United States' ] );
		$countries_mock->method( 'get_shipping_countries' )
			->willReturn( [ 'US' => 'United States' ] );
		$countries_mock->method( 'get_states' )
			->with( 'US' )
			->willReturn( [ 'NY' => 'New York' ] ); // Only NY is valid.

		WC()->countries = $countries_mock;

		$result = $this->validation_utils->validate_and_correct_country( 'US', 'INVALID_STATE', 'billing' );

		$this->assertEquals( 'US', $result['country'] );
		$this->assertEquals( '', $result['state'] ); // Invalid state should be reset.
	}

	/**
	 * @testdox `validate_and_correct_country` should default to billing when no address type specified.
	 */
	public function test_validate_and_correct_country_defaults_to_billing() {
		// Mock WC()->countries to return specific allowed countries.
		$this->mock_wc_countries( [ 'US' => 'United States' ], [ 'CA' => 'Canada' ] );

		// Test with country that's only valid for shipping, should fail for billing (default).
		$result = $this->validation_utils->validate_and_correct_country( 'CA', 'BC' ); // No address_type specified.

		$this->assertEquals( 'US', $result['country'] ); // Should be corrected using billing countries.
		$this->assertEquals( '', $result['state'] ); // State should be reset.
	}

	/**
	 * @testdox `validate_and_correct_country` should handle null state gracefully.
	 */
	public function test_validate_and_correct_country_null_state() {
		// Mock WC()->countries to return specific allowed countries.
		$this->mock_wc_countries(
			[
				'US' => 'United States',
				'CA' => 'Canada',
			],
			[ 'US' => 'United States' ]
		);

		$result = $this->validation_utils->validate_and_correct_country( 'US', null, 'billing' );

		$this->assertEquals( 'US', $result['country'] );
		$this->assertEquals( '', $result['state'] ); // Null state should be converted to empty string.
	}

	/**
	 * @testdox `validate_and_correct_country` should preserve valid states.
	 */
	public function test_validate_and_correct_country_preserves_valid_state() {
		// Use standardized mocking approach.
		$this->mock_wc_countries_with_states(
			[ 'US' => 'United States' ],
			[ 'US' => 'United States' ],
			[
				'US' => [
					'NY' => 'New York',
					'CA' => 'California',
				],
			]
		);

		$result = $this->validation_utils->validate_and_correct_country( 'US', 'NY', 'billing' );

		$this->assertEquals( 'US', $result['country'] );
		$this->assertEquals( 'NY', $result['state'] ); // Valid state should be preserved.
	}

	/**
	 * @testdox `validate_and_correct_country` should handle empty allowed countries gracefully.
	 */
	public function test_validate_and_correct_country_no_allowed_countries() {
		// Test with no allowed countries - should handle gracefully.
		$this->mock_wc_countries( [], [] );

		$result = $this->validation_utils->validate_and_correct_country( 'US', 'CA', 'billing' );

		// When no countries are available, should preserve original country since no correction is possible.
		$this->assertEquals( 'US', $result['country'] );
		$this->assertEquals( 'CA', $result['state'] ); // State should be preserved when no validation is possible.
	}

	/**
	 * @testdox `validate_and_correct_country` should handle both country and state being empty.
	 */
	public function test_validate_and_correct_country_both_empty() {
		$this->mock_wc_countries( [ 'US' => 'United States' ], [ 'US' => 'United States' ] );

		$result = $this->validation_utils->validate_and_correct_country( '', '', 'billing' );

		$this->assertEquals( '', $result['country'] );
		$this->assertEquals( '', $result['state'] );
	}

	/**
	 * @testdox `validate_and_correct_country` should validate states properly for countries with states.
	 */
	public function test_validate_and_correct_country_realistic_state_validation() {
		// Use realistic US state validation.
		$this->mock_wc_countries_with_states(
			[ 'US' => 'United States' ],
			[ 'US' => 'United States' ],
			[
				'US' => [
					'CA' => 'California',
					'NY' => 'New York',
					'TX' => 'Texas',
				],
			]
		);

		// Test valid state code.
		$result = $this->validation_utils->validate_and_correct_country( 'US', 'CA', 'billing' );
		$this->assertEquals( 'US', $result['country'] );
		$this->assertEquals( 'CA', $result['state'] );

		// Test invalid state.
		$result = $this->validation_utils->validate_and_correct_country( 'US', 'INVALID', 'billing' );
		$this->assertEquals( 'US', $result['country'] );
		$this->assertEquals( '', $result['state'] );
	}

	/**
	 * @testdox `validate_and_correct_country` should handle countries without states.
	 */
	public function test_validate_and_correct_country_no_states() {
		// Test with a country that has no states (like many European countries).
		$this->mock_wc_countries_with_states(
			[ 'DE' => 'Germany' ],
			[ 'DE' => 'Germany' ],
			[ 'DE' => [] ] // No states for Germany.
		);

		$result = $this->validation_utils->validate_and_correct_country( 'DE', 'Berlin', 'billing' );

		$this->assertEquals( 'DE', $result['country'] );
		$this->assertEquals( 'Berlin', $result['state'] ); // Should preserve any state value for countries without states.
	}

	/**
	 * @testdox `validate_and_correct_country` should handle edge cases with special characters.
	 */
	public function test_validate_and_correct_country_special_characters() {
		$this->mock_wc_countries( [ 'US' => 'United States' ], [ 'US' => 'United States' ] );

		// Test with special characters in state.
		$result = $this->validation_utils->validate_and_correct_country( 'US', 'St. John\'s', 'billing' );

		$this->assertEquals( 'US', $result['country'] );
		// State validation may clear special characters, but method should not crash.
		$this->assertIsString( $result['state'] );
	}

	/**
	 * Mock WC()->countries methods for testing.
	 *
	 * @param array $allowed_countries Billing countries.
	 * @param array $shipping_countries Shipping countries.
	 */
	private function mock_wc_countries( $allowed_countries, $shipping_countries ) {
		// Create a mock for WC_Countries.
		$countries_mock = $this->getMockBuilder( \WC_Countries::class )
			->onlyMethods( [ 'get_allowed_countries', 'get_shipping_countries' ] )
			->getMock();

		$countries_mock->method( 'get_allowed_countries' )
			->willReturn( $allowed_countries );

		$countries_mock->method( 'get_shipping_countries' )
			->willReturn( $shipping_countries );

		// Replace WC()->countries with our mock.
		WC()->countries = $countries_mock;
	}

	/**
	 * Mock WC()->countries methods including states for more comprehensive testing.
	 *
	 * @param array $allowed_countries Billing countries.
	 * @param array $shipping_countries Shipping countries.
	 * @param array $states States per country.
	 */
	private function mock_wc_countries_with_states( $allowed_countries, $shipping_countries, $states ) {
		// Create a mock for WC_Countries.
		$countries_mock = $this->getMockBuilder( \WC_Countries::class )
			->onlyMethods( [ 'get_allowed_countries', 'get_shipping_countries', 'get_states' ] )
			->getMock();

		$countries_mock->method( 'get_allowed_countries' )
			->willReturn( $allowed_countries );

		$countries_mock->method( 'get_shipping_countries' )
			->willReturn( $shipping_countries );

		// Set up get_states to return different states per country.
		foreach ( $states as $country => $country_states ) {
			$countries_mock->method( 'get_states' )
				->with( $country )
				->willReturn( $country_states );
		}

		// Replace WC()->countries with our mock.
		WC()->countries = $countries_mock;
	}
}
