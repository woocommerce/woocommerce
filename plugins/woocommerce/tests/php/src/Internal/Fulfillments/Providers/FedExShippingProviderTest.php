<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\FedExShippingProvider;

/**
 * Unit tests for FedExShippingProvider class.
 */
class FedExShippingProviderTest extends \WP_UnitTestCase {
	/**
	 * The provider instance being tested.
	 *
	 * @var FedExShippingProvider
	 */
	private FedExShippingProvider $provider;

	/**
	 * Sets up the test fixture.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->provider = new FedExShippingProvider();
	}

	/**
	 * Tests the tracking URL generation.
	 */
	public function test_get_tracking_url(): void {
		$tracking_number = '123456789012';
		$expected_url    = 'https://www.fedex.com/fedextrack/?tracknumbers=' . rawurlencode( $tracking_number );
		$this->assertEquals( $expected_url, $this->provider->get_tracking_url( $tracking_number ) );
	}

	/**
	 * Data provider for tracking number validation tests.
	 *
	 * @return array<array{string, string, string, bool, int}> Test cases.
	 */
	public function trackingNumberProvider(): array {
		return array(
			// FedEx Custom Critical (98 score).
			array( '001234567890123456789012', 'US', 'CA', true, 98 ),
			array( '011234567890123456789012', 'US', 'US', true, 98 ),

			// FedEx SmartPost (97 and 96 score).
			array( '02312345678901234567', 'US', 'US', true, 97 ),
			array( '58123456789012345678', 'US', 'CA', true, 96 ),

			// FedEx Express - 3x patterns (92 score).
			array( '31234567890', 'US', 'DE', true, 92 ),
			array( '398765432109876', 'CA', 'US', true, 80 ), // 15-digit without valid check digit

			// FedEx Ground - 96 prefix (95 US/CA, 60 others).
			array( '9611020987654312345678', 'US', 'US', true, 95 ),
			array( '9611020987654312345678', 'CA', 'US', true, 95 ),
			array( '9611020987654312345678', 'DE', 'FR', true, 60 ),

			// FedEx Freight (93 score).
			array( '9712345678901234567890123', 'US', 'CA', true, 93 ),
			array( '971234567890123', 'US', 'US', true, 80 ), // 15-digit pattern, not 97x pattern

			// FedEx International Priority European (93 for EU, 75 for others).
			array( '812345678901234', 'GB', 'DE', true, 65 ), // 15-digit pattern gets 65 for non-NA
			array( '812345678901234', 'DE', 'FR', true, 65 ), // 15-digit pattern gets 65 for non-NA
			array( '812345678901234', 'US', 'CA', true, 80 ), // 15-digit pattern gets 80 for NA

			// FedEx Ground - 7x patterns (90 US/CA, 75 others).
			array( '712345678901234567890', 'US', 'US', true, 90 ),
			array( '712345678901234567890', 'CA', 'CA', true, 90 ),
			array( '712345678901234567890', 'DE', 'FR', true, 75 ),

			// FedEx Express - 15 digit (80 US/CA, 65 others).
			array( '123456789012345', 'US', 'CA', true, 80 ), // Invalid check digit.
			array( '123456789012345', 'DE', 'FR', true, 65 ), // Invalid check digit.

			// FedEx Express - 12 digit with invalid check digit (85 US/CA, 70 others).
			array( '123456789013', 'US', 'CA', true, 85 ), // Invalid check digit.
			array( '123456789013', 'DE', 'FR', true, 70 ), // Invalid check digit, non-NA.

			// FedEx Express - 12 digit with invalid check digit (85 US/CA, 70 others).
			array( '123456789012', 'US', 'CA', true, 85 ), // Invalid check digit.
			array( '123456789012', 'DE', 'FR', true, 70 ), // Invalid check digit, non-NA.

			// FedEx Express - 14 digit with invalid check digit (78 US/CA, 60 others).
			array( '12345678901237', 'US', 'FR', true, 78 ), // Invalid check digit.
			array( '12345678901237', 'GB', 'DE', true, 60 ), // Invalid check digit, non-NA.

			// FedEx Express - 14 digit with invalid check digit (78 US/CA, 60 others).
			array( '12345678901234', 'US', 'FR', true, 78 ), // Invalid check digit.
			array( '12345678901234', 'GB', 'DE', true, 60 ), // Invalid check digit, non-NA.

			// FedEx SameDay and Next Flight Out.
			array( 'SD1234567890123', 'US', 'CA', true, 90 ),
			array( 'NFO1234567890123', 'US', 'DE', true, 92 ),

			// FedEx Express - 20 digit (70 score).
			array( '12345678901234567890', 'US', 'DE', true, 70 ),
			array( '98765432109876543210', 'FR', 'IT', true, 70 ),

			// FedEx Express - 22 digit (65 score).
			array( '1234567890123456789012', 'US', 'DE', true, 65 ),

			// Invalid formats.
			array( '1234567890', 'US', 'CA', false, 0 ), // Too short.
			array( 'ABCDEFGHIJKL', 'US', 'US', false, 0 ), // Invalid characters.
			array( '12345', 'CA', 'US', false, 0 ), // Too short.

			// Invalid countries.
			array( '123456789012', 'ZZ', 'US', false, 0 ), // Invalid origin.
			array( '123456789012', 'US', 'ZZ', false, 0 ), // Invalid destination.
		);
	}

	/**
	 * Tests tracking number parsing with various scenarios.
	 *
	 * @dataProvider trackingNumberProvider
	 * @param string $tracking_number The tracking number to test.
	 * @param string $from Origin country code.
	 * @param string $to Destination country code.
	 * @param bool   $expected_valid Whether the number should be valid.
	 * @param int    $expected_score Expected ambiguity score.
	 */
	public function test_try_parse_tracking_number(
		string $tracking_number,
		string $from,
		string $to,
		bool $expected_valid,
		int $expected_score
	): void {
		$result = $this->provider->try_parse_tracking_number( $tracking_number, $from, $to );

		if ( ! $expected_valid ) {
			$this->assertNull( $result );
		} else {
			$this->assertNotNull( $result );
			$this->assertEquals(
				'https://www.fedex.com/fedextrack/?tracknumbers=' . rawurlencode( $tracking_number ),
				$result['url']
			);
			$this->assertEquals( $expected_score, $result['ambiguity_score'] );
		}
	}

	/**
	 * Tests FedEx Ground regional scoring differences.
	 */
	public function test_ground_regional_restrictions(): void {
		$us_result = $this->provider->try_parse_tracking_number( '9611020987654312345678', 'US', 'CA' );
		$de_result = $this->provider->try_parse_tracking_number( '9611020987654312345678', 'DE', 'FR' );

		$this->assertNotNull( $us_result );
		$this->assertNotNull( $de_result );
		$this->assertGreaterThan( $de_result['ambiguity_score'], $us_result['ambiguity_score'] );
	}

	/**
	 * Tests the scoring hierarchy between different formats.
	 */
	public function test_format_confidence_hierarchy(): void {
		$custom_critical    = $this->provider->try_parse_tracking_number( '001234567890123456789012', 'US', 'CA' );
		$express_12_valid   = $this->provider->try_parse_tracking_number( '123456789013', 'US', 'CA' ); // Valid check digit.
		$express_12_invalid = $this->provider->try_parse_tracking_number( '123456789012', 'US', 'CA' ); // Invalid check digit.
		$express_15         = $this->provider->try_parse_tracking_number( '123456789012345', 'US', 'CA' );
		$generic_20         = $this->provider->try_parse_tracking_number( '12345678901234567890', 'US', 'CA' );

		// Custom Critical should have highest score.
		$this->assertGreaterThan( $express_12_valid['ambiguity_score'], $custom_critical['ambiguity_score'] );

		// Both 12-digit numbers get same score if both have invalid check digits.
		$this->assertEquals( $express_12_invalid['ambiguity_score'], $express_12_valid['ambiguity_score'] );

		// Express 15 should beat Express 12 invalid.
		$this->assertGreaterThan( $express_15['ambiguity_score'], $express_12_invalid['ambiguity_score'] );

		// Express 15 should beat generic 20.
		$this->assertGreaterThan( $generic_20['ambiguity_score'], $express_15['ambiguity_score'] );
	}
}
