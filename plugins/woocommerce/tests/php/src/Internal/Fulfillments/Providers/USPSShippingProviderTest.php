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
			// Domestic services test cases.
			array( '9400111899223859301234', 'US', 'US', true, 95 ),  // Standard USPS Tracking.
			array( '9205111899223859301234', 'US', 'US', true, 95 ),  // Priority Mail.
			array( '9407111899223859301234', 'US', 'US', true, 100 ), // Certified Mail.
			array( '9208111899223859301234', 'US', 'US', true, 100 ), // Registered Mail.
			array( '70001200000012345678', 'US', 'US', true, 100 ),   // Certified legacy format.

			// International formats test cases.
			array( 'LZ123456789US', 'US', 'DE', true, 100 ), // UPU S10 format.
			array( 'EL123456789US', 'US', 'FR', true, 100 ),  // Express Mail International.
			array( 'EC123456789US', 'US', 'CA', true, 100 ),  // Global Express.
			array( 'R123456789US', 'US', 'MX', true, 85 ),   // Registered International.

			// US territories test cases.
			array( '9400111899223859301234', 'US', 'PR', true, 95 ), // Puerto Rico.
			array( '9205111899223859301234', 'US', 'GU', true, 95 ), // Guam.

			// Other formats test cases.
			array( '911234567890123456789', 'US', 'US', true, 85 ),  // GS1-128 format.
			array( '12345678901234567890', 'US', 'US', true, 70 ),   // 20-digit format.
			array( '9999111899223859301234', 'US', 'US', true, 90 ), // Fallback 9x format. Origin or destination is US.

			// Invalid cases test cases.
			array( 'INVALID123', 'US', 'US', false, null ), // Invalid format.
			array( '940011189922385930', 'US', 'US', false, null ), // Too short.
			array( '9400111899223859301234', 'CA', 'US', false, null ), // Invalid origin.
			array( 'LZ123456789DE', 'US', 'DE', false, null ), // Invalid UPU suffix.
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
		$certified = $this->provider->try_parse_tracking_number( '9407111899223859301234', 'US', 'US' );
		$priority  = $this->provider->try_parse_tracking_number( '9205111899223859301234', 'US', 'US' );
		$fallback  = $this->provider->try_parse_tracking_number( '9999111899223859301234', 'US', 'US' );

		$this->assertGreaterThan( $priority['ambiguity_score'], $certified['ambiguity_score'] );
		$this->assertGreaterThan( $fallback['ambiguity_score'], $priority['ambiguity_score'] );
	}

	/**
	 * Tests international shipment scoring.
	 */
	public function test_international_scoring(): void {
		$upu_intl = $this->provider->try_parse_tracking_number( 'LZ123456789US', 'US', 'DE' );
		$express  = $this->provider->try_parse_tracking_number( 'EL123456789US', 'US', 'FR' );
		$global   = $this->provider->try_parse_tracking_number( 'EC123456789US', 'US', 'CA' );

		$this->assertEquals( 100, $upu_intl['ambiguity_score'] );
		$this->assertEquals( 100, $express['ambiguity_score'] );
		$this->assertEquals( 100, $global['ambiguity_score'] );
	}
}
