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
			// 1Z format (unambiguous, 100)
			array( '1Z12345E0205271688', 'US', 'US', true, 100 ),
			array( '1z12345e0205271688', 'CA', 'US', true, 100 ),

			// T/H format (domestic US only, 75).
			array( 'T1234567890', 'US', 'US', true, 75 ),
			array( 't1234567890', 'US', 'US', true, 75 ),
			array( 'H1234567890', 'US', 'US', true, 75 ),

			// 9x format (ambiguous: CA origin = 60, other = 40)
			array( '9123456789012345678901234567890123', 'CA', 'US', true, 60 ),
			array( '9123456789012345678901234567890123', 'US', 'CA', true, 40 ),

			// Invalid formats (should not match).
			array( '1234567890', 'US', 'US', false, null ),
			array( 'INVALID123', 'CA', 'CA', false, null ),

			// T/H used in invalid lanes.
			array( 'T1234567890', 'US', 'CA', false, null ),
			array( 'H1234567890', 'CA', 'US', false, null ),
		);
	}

	/**
	 * Test tracking number parsing.
	 *
	 * @param string   $tracking_number The tracking number to test.
	 * @param string   $shipping_from   The shipping origin country code.
	 * @param string   $shipping_to     The shipping destination country code.
	 * @param bool     $has_match       Whether the tracking number should match.
	 * @param int|null $expected_score Expected ambiguity score, or null if no match.
	 *
	 * @return void
	 *
	 * @dataProvider trackingNumberDataProvider
	 */
	public function test_tracking_number_parsing( string $tracking_number, string $shipping_from, string $shipping_to, bool $has_match, ?int $expected_score ): void {
		$provider = new UPSShippingProvider();
		$result   = $provider->try_parse_tracking_number( $tracking_number, $shipping_from, $shipping_to );

		if ( $has_match ) {
			$this->assertNotNull( $result );
			$this->assertEquals( 'https://www.ups.com/track?tracknum=' . rawurlencode( $tracking_number ), $result['url'] );
			$this->assertEquals( $expected_score, $result['ambiguity_score'] );
		} else {
			$this->assertNull( $result );
		}
	}
}
