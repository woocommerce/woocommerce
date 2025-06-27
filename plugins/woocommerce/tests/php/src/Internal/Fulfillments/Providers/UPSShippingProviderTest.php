<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\UPSShippingProvider;

/**
 * Tests for UPSShippingProvider class.
 */
class UPSShippingProviderTest extends \WP_UnitTestCase {
	/**
	 * Test the get_tracking_url method.
	 */
	public function test_get_tracking_url(): void {
		$provider        = new UPSShippingProvider();
		$tracking_number = '1Z12345E0205271688';
		$expected_url    = 'https://www.ups.com/track?tracknum=' . rawurlencode( $tracking_number );

		$this->assertEquals( $expected_url, $provider->get_tracking_url( $tracking_number ) );
	}

	/**
	 * Data provider for tracking number parsing tests.
	 *
	 * @return array
	 */
	public function trackingNumberDataProvider(): array {
		return array(
			// Valid tracking numbers.
			array( '1Z12345E0205271688', 'US', 'US', true, false ),
			array( '1Z12345E0205271688', 'US', 'CA', true, false ),
			array( '1Z12345E0205271688', 'CA', 'US', true, false ),
			array( '1Z12345E0205271688', 'CA', 'CA', true, false ),
			array( '1Z12345E0205271688', 'US', 'MX', true, false ),
			array( '1Z12345E0205271688', 'MX', 'US', true, false ),
			array( 'T1234567890', 'US', 'US', true, true ),
			array( 'T1234567890', 'US', 'CA', false, false ),
			array( 'T1234567890', 'CA', 'US', false, false ),
			array( 'T1234567890', 'CA', 'CA', false, false ),
			array( 'H1234567890', 'US', 'US', true, true ),
			array( 'H1234567890', 'US', 'CA', false, false ),
			array( 'H1234567890', 'CA', 'US', false, false ),
			array( 'H1234567890', 'CA', 'CA', false, false ),
			// Invalid tracking numbers.
			array( '1234567890', 'US', 'US', false, false ),
			array( '1234567890', 'US', 'CA', false, false ),
			array( '1234567890', 'CA', 'US', false, false ),
			array( '1234567890', 'CA', 'CA', false, false ),
			array( 'INVALID123', 'US', 'US', false, false ),
			array( 'INVALID123', 'US', 'CA', false, false ),
			array( 'INVALID123', 'CA', 'US', false, false ),
			array( 'INVALID123', 'CA', 'CA', false, false ),
			// Ambiguous tracking numbers.
			array( 'T1234567890', 'US', 'MX', false, false ),
			array( 'T1234567890', 'MX', 'US', false, false ),
			array( 'H1234567890', 'US', 'MX', false, false ),
			array( 'H1234567890', 'MX', 'US', false, false ),
			// Mixed cases.
			array( '1z12345e0205271688', 'US', 'US', true, false ),
			array( '1z12345e0205271688', 'US', 'CA', true, false ),
			array( '1z12345e0205271688', 'CA', 'US', true, false ),
			array( '1z12345e0205271688', 'CA', 'CA', true, false ),
			array( 't1234567890', 'US', 'US', true, true ),
			array( 't1234567890', 'CA', 'CA', false, false ),
			array( 'h1234567890', 'US', 'US', true, true ),
			array( 'h1234567890', 'CA', 'CA', false, false ),
		);
	}

	/**
	 * Test tracking number parsing.
	 *
	 * @param string $tracking_number The tracking number to test.
	 * @param string $shipping_from   The shipping origin country code.
	 * @param string $shipping_to     The shipping destination country code.
	 * @param bool   $has_match       Whether the tracking number should match.
	 * @param bool   $is_ambiguous    Whether the tracking number is ambiguous.
	 *
	 * @return void
	 *
	 * @dataProvider trackingNumberDataProvider
	 */
	public function test_tracking_number_parsing( string $tracking_number, string $shipping_from, string $shipping_to, bool $has_match, bool $is_ambiguous ): void {
		$provider = new UPSShippingProvider();
		$result   = $provider->try_parse_tracking_number( $tracking_number, $shipping_from, $shipping_to );

		if ( $has_match ) {
			$this->assertNotNull( $result );
			$this->assertEquals( 'https://www.ups.com/track?tracknum=' . rawurlencode( $tracking_number ), $result['url'] );
			$this->assertEquals( $is_ambiguous, $result['ambiguous'] ?? false );
		} else {
			$this->assertNull( $result );
		}
	}
}
