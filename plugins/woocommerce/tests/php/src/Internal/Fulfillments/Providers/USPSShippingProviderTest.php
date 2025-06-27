<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\USPSShippingProvider;

/**
 * Tests for USPSShippingProvider class.
 */
class USPSShippingProviderTest extends \WP_UnitTestCase {
	/**
	 * The USPS shipping provider instance.
	 *
	 * @var USPSShippingProvider
	 */
	private USPSShippingProvider $provider;

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		$this->provider = new USPSShippingProvider();
	}

	/**
	 * Test the get_key method.
	 */
	public function test_get_tracking_url(): void {
		$tracking_number = '9400111899223859301234';
		$expected_url    = 'https://tools.usps.com/go/TrackConfirmAction?qtc_tLabels1=' . rawurlencode( $tracking_number );
		$this->assertEquals( $expected_url, $this->provider->get_tracking_url( $tracking_number ) );
	}

	/**
	 * Test the try_parse_tracking_number method with various tracking numbers.
	 *
	 * @param string   $tracking_number The tracking number to test.
	 * @param string   $from            The shipping origin country code.
	 * @param string   $to              The shipping destination country code.
	 * @param bool     $expected_valid  Whether the tracking number should be valid.
	 * @param int|null $expected_score Expected ambiguity score, or null if no match.
	 *
	 * @dataProvider trackingNumberProvider
	 */
	public function test_try_parse_tracking_number( string $tracking_number, string $from, string $to, bool $expected_valid, ?int $expected_score ): void {
		$result = $this->provider->try_parse_tracking_number( $tracking_number, $from, $to );

		if ( ! $expected_valid ) {
			$this->assertNull( $result, 'Tracking number should not be valid.' );
		} else {
			$this->assertNotNull( $result, 'Tracking number should be valid.' );
			$this->assertIsArray( $result );
			$this->assertArrayHasKey( 'url', $result );
			$this->assertArrayHasKey( 'ambiguity_score', $result );
			$this->assertSame( $expected_score, $result['ambiguity_score'] );
		}
	}

	/**
	 * Data provider for tracking number tests.
	 *
	 * @return array
	 */
	public function trackingNumberProvider(): array {
		return array(
			// International UPU - unambiguous (100).
			array( 'LZ123456789US', 'US', 'DE', true, 100 ),
			array( 'lz123456789us', 'US', 'GB', true, 100 ),
			// Domestic UPU - ambiguous (60).
			array( 'LZ123456789US', 'US', 'US', true, 60 ),
			// Certified mail - unambiguous (90).
			array( '70001200000012345678', 'US', 'US', true, 90 ),
			// Confirm format - unambiguous (85).
			array( '23012345678901234567', 'US', 'US', true, 85 ),
			// 9x ambiguous format - common but shared (50)
			array( '9400111899223859301234', 'US', 'US', true, 50 ),
			// Invalid formats.
			array( 'INVALID123', 'US', 'US', false, null ),
			array( '1234567890', 'US', 'US', false, null ),
			// Invalid origins.
			array( '9400111899223859301234', 'CA', 'US', false, null ),
			array( 'LZ123456789US', 'DE', 'US', false, null ),
		);
	}
}
