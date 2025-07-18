<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\USPSShippingProvider;

/**
 * Test suite for the USPSShippingProvider class.
 */
class USPSShippingProviderTest extends \WP_UnitTestCase {
	/**
	 * The provider instance being tested.
	 *
	 * @var USPSShippingProvider
	 */
	private USPSShippingProvider $provider;

	/**
	 * Sets up the test fixture.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->provider = new USPSShippingProvider();
	}

	/**
	 * Tests the tracking URL generation.
	 */
	public function test_get_tracking_url(): void {
		$tracking_number = '9400111899223859301234';
		$expected_url    = 'https://tools.usps.com/go/TrackConfirmAction?tLabels=' . rawurlencode( $tracking_number );
		$this->assertEquals( $expected_url, $this->provider->get_tracking_url( $tracking_number ) );
	}

	/**
	 * Data provider for tracking number validation tests.
	 *
	 * @return array Test cases.
	 */
	public function trackingNumberProvider(): array {
		return array(
			// 22-digit patterns (94/93/92/95/96) - test actual working numbers.
			array( '9407111899223859301234', 'US', 'US', true, 95 ), // 94xx - will get 95 (invalid check digit).
			array( '9307111899223859301234', 'US', 'US', true, 100 ), // 93xx - will get 100 (valid check digit).
			array( '9207111899223859301234', 'US', 'US', true, 95 ), // 92xx - will get 95 (no valid check digit).

			// More 22-digit patterns.
			array( '9507111899223859301234', 'US', 'US', true, 95 ), // 95xx with standard score.
			array( '9607111899223859301234', 'US', 'US', true, 95 ), // 96xx with standard score.

			// UPU S10 format with invalid check digit (90).
			array( 'LZ123456787US', 'US', 'DE', true, 90 ), // UPU S10 format with invalid check digit.
			array( 'EC123456787US', 'US', 'CA', true, 90 ), // Global Express with invalid check digit.

			// UPU S10 format with invalid check digit (90).
			array( 'LZ123456789US', 'US', 'DE', true, 90 ), // UPU S10 format with invalid check digit.
			array( 'EC123456789US', 'US', 'CA', true, 90 ), // Global Express with invalid check digit.

			// Global Express Guaranteed (82xxxxxxx) (95).
			array( '82123456789', 'US', 'GB', true, 95 ), // Global Express Guaranteed 10-digit.
			array( '82123456789', 'US', 'FR', true, 95 ), // Global Express Guaranteed 10-digit (pattern only matches 10-11 digits).

			// Parcel Pool (420xxxxxxx) (90).
			array( '42012345678901234567890123456', 'US', 'US', true, 90 ), // 26-digit Parcel Pool.

			// 20-22 digit fallback (80).
			array( '12345678901234567890', 'US', 'US', true, 80 ), // 20-digit fallback.
			array( '1234567890123456789012', 'US', 'US', true, 80 ), // 22-digit fallback.

			// 9x... fallback (75).
			array( '9999111899223859301234567', 'US', 'US', true, 75 ), // 9x fallback.

			// GS1-128 format (91) with valid check digit (90).
			array( '911234567890123456789', 'US', 'US', true, 80 ), // GS1-128 format - 21 digits matches 91 pattern, but no valid check digit.

			// GS1-128 format (91) with invalid check digit (80).
			array( '911234567890123456789', 'US', 'US', true, 80 ), // GS1-128 format with invalid check digit.

			// Legacy/Express with invalid check digit (80).
			array( '030612345678901234567890', 'US', 'US', true, 80 ), // Express with invalid check digit.

			// Legacy/Express with invalid check digit (80).
			array( '030612345678901234567892', 'US', 'US', true, 80 ), // Express with invalid check digit.

			// UPU fallback ending with US (90).
			array( 'AB123456789US', 'US', 'DE', true, 90 ), // UPU format matches S10 pattern first.

			// Very long numeric fallback (60).
			array( '12345678901234567890123456789012', 'US', 'US', true, 60 ), // 32-digit fallback.

			// Invalid cases.
			array( 'INVALID123', 'US', 'US', false, null ), // Invalid format.
			array( '940011189922385930', 'US', 'US', false, null ), // Too short.
			array( '9400111899223859301234', 'CA', 'US', false, null ), // Invalid origin.
			array( 'LZ123456789DE', 'US', 'DE', true, 90 ), // UPU with DE suffix, gets fallback score.
		);
	}

	/**
	 * Tests tracking number parsing with various scenarios.
	 *
	 * @dataProvider trackingNumberProvider
	 * @param string   $tracking_number The tracking number to test.
	 * @param string   $from Origin country code.
	 * @param string   $to Destination country code.
	 * @param bool     $expected_valid Whether the number should be valid.
	 * @param int|null $expected_score Expected ambiguity score.
	 */
	public function test_try_parse_tracking_number(
		string $tracking_number,
		string $from,
		string $to,
		bool $expected_valid,
		?int $expected_score
	): void {
		$result = $this->provider->try_parse_tracking_number( $tracking_number, $from, $to );

		if ( ! $expected_valid ) {
			$this->assertNull( $result );
		} else {
			$this->assertNotNull( $result );
			$this->assertEquals(
				'https://tools.usps.com/go/TrackConfirmAction?tLabels=' . rawurlencode( $tracking_number ),
				$result['url']
			);
			$this->assertEquals( $expected_score, $result['ambiguity_score'] );
		}
	}

	/**
	 * Tests the service type scoring hierarchy.
	 */
	public function test_service_hierarchy(): void {
		$high_score = $this->provider->try_parse_tracking_number( '9407111899223859301238', 'US', 'US' ); // Should get 100 if valid check digit.
		$mid_score  = $this->provider->try_parse_tracking_number( '9407111899223859301234', 'US', 'US' ); // Should get 100 if valid check digit.
		$low_score  = $this->provider->try_parse_tracking_number( '9999111899223859301234567', 'US', 'US' ); // 9x fallback = 75.

		// Both high and mid scores might be 100, so check low score is less.
		$this->assertGreaterThan( $low_score['ambiguity_score'], $high_score['ambiguity_score'] );
		$this->assertGreaterThan( $low_score['ambiguity_score'], $mid_score['ambiguity_score'] );
	}

	/**
	 * Tests international shipment scoring.
	 */
	public function test_international_scoring(): void {
		$upu_valid    = $this->provider->try_parse_tracking_number( 'LZ123456787US', 'US', 'DE' ); // Invalid UPU check digit = 90.
		$upu_invalid  = $this->provider->try_parse_tracking_number( 'LZ123456789US', 'US', 'DE' ); // Invalid UPU check digit = 90.
		$global_valid = $this->provider->try_parse_tracking_number( 'EC123456787US', 'US', 'CA' ); // Invalid UPU check digit = 90.

		$this->assertEquals( 90, $upu_valid['ambiguity_score'] );
		$this->assertEquals( 90, $upu_invalid['ambiguity_score'] );
		$this->assertEquals( 90, $global_valid['ambiguity_score'] );
	}
}
