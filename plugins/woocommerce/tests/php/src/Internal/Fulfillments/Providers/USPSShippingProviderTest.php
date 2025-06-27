<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\USPSShippingProvider;

/**
 * Tests for USPSShippingProvider class.
 */
class USPSShippingProviderTest extends \WP_UnitTestCase {
	/**
	 * Instance of USPSShippingProvider to be used in tests.
	 *
	 * @var USPSShippingProvider
	 */
	private USPSShippingProvider $provider;

	/**
	 * Set up the USPSShippingProvider instance before each test.
	 */
	protected function setUp(): void {
		$this->provider = new USPSShippingProvider();
	}

	/**
	 * Test the get_tracking_url method.
	 */
	public function test_get_tracking_url(): void {
		$tracking_number = '9400111899223859301234';
		$expected_url    = 'https://tools.usps.com/go/TrackConfirmAction?qtc_tLabels1=' . rawurlencode( $tracking_number );
		$this->assertEquals( $expected_url, $this->provider->get_tracking_url( $tracking_number ) );
	}

	/**
	 * Test the try_parse_tracking_number method with various tracking numbers.
	 *
	 * @param string $tracking_number The tracking number to test.
	 * @param string $from The country code from which the shipment is sent.
	 * @param string $to The country code to which the shipment is sent.
	 * @param bool   $expected_valid Whether the tracking number is expected to be valid.
	 * @param bool   $expected_ambiguous Whether the tracking number is expected to be ambiguous.
	 *
	 * @dataProvider trackingNumberProvider
	 */
	public function test_try_parse_tracking_number( string $tracking_number, string $from, string $to, bool $expected_valid, bool $expected_ambiguous ): void {
		$result = $this->provider->try_parse_tracking_number( $tracking_number, $from, $to );

		if ( ! $expected_valid ) {
			$this->assertNull( $result, 'Tracking number should not be valid.' );
		} else {
			$this->assertNotNull( $result, 'Tracking number should be valid.' );
			$this->assertIsArray( $result );
			$this->assertArrayHasKey( 'url', $result );
			$this->assertArrayHasKey( 'ambiguous', $result );
			$this->assertSame( $expected_ambiguous, $result['ambiguous'] );
		}
	}

	/**
	 * Data provider for tracking number parsing tests.
	 *
	 * @return array
	 */
	public function trackingNumberProvider(): array {
		return array(
			// Valid and unambiguous.
			array( '9400111899223859301234', 'US', 'US', true, true ),
			array( 'LZ123456789US', 'US', 'DE', true, false ),
			array( '70001200000012345678', 'US', 'US', true, false ),
			array( '23012345678901234567', 'US', 'US', true, false ),

			// Invalid: wrong origin.
			array( '9400111899223859301234', 'CA', 'US', false, false ),
			array( 'LZ123456789US', 'DE', 'US', false, false ),

			// Invalid: bad format.
			array( 'INVALID123', 'US', 'US', false, false ),
			array( '1234567890', 'US', 'US', false, false ),

			// Lowercase international.
			array( 'lz123456789us', 'US', 'GB', true, false ),
		);
	}
}
