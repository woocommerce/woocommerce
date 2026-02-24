<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\UPSShippingProvider;

/**
 * Unit tests for UPSShippingProvider class.
 */
class UPSShippingProviderTest extends \WP_UnitTestCase {
	/**
	 * Test the get_tracking_url method.
	 */
	public function test_get_tracking_url(): void {
		$tracking_number = '1Z12345E0205271688';
		$expected_url    = 'https://www.ups.com/track?tracknum=' . rawurlencode( $tracking_number );
		$provider        = new UPSShippingProvider();
		$this->assertEquals( $expected_url, $provider->get_tracking_url( $tracking_number ) );
	}

	/**
	 * Data provider for tracking number parsing tests.
	 *
	 * @return array
	 */
	public function trackingNumberProvider(): array {
		return array(
			// 1Z format with valid check digit (100).
			array( '1Z12345E0205271688', 'US', 'DE', true, 100 ),
			array( '1Z12345E0205271688', 'CA', 'US', true, 100 ),
			array( '1Z12345E0205271688', 'GB', 'FR', true, 100 ),
			array( '1z12345e0205271688', 'DE', 'IT', true, 100 ),

			// 1Z format with invalid check digit (95).
			array( '1Z12345E0205271687', 'US', 'DE', true, 95 ),

			// SurePost format (85 for US/CA).
			array( '9274345678901234567890', 'US', 'US', true, 85 ),
			array( '9274345678901234567890', 'CA', 'CA', true, 85 ),

			// T/H/V format (85).
			array( 'T1234567890', 'US', 'US', true, 85 ),
			array( 'H1234567890', 'US', 'US', true, 85 ),
			array( 't1234567890', 'US', 'US', true, 85 ),
			array( 'h1234567890', 'US', 'US', true, 85 ),
			array( 'T1234567890', 'CA', 'CA', true, 85 ),
			array( 'T1234567890', 'GB', 'GB', true, 85 ),
			array( 'T1234567890', 'US', 'CA', true, 85 ),

			// InfoNotice format (80).
			array( 'J1234567890', 'US', 'US', true, 80 ),
			array( 'J1234567890', 'CA', 'CA', true, 80 ),
			array( 'j1234567890', 'DE', 'DE', true, 80 ),
			array( 'J1234567890', 'US', 'CA', true, 80 ),

			// Mail Innovations formats - US/CA get higher scores.
			array( '9123456789012345678901234567890123', 'US', 'CA', true, 85 ),
			array( '9123456789012345678901234567890123', 'CA', 'US', true, 85 ),
			array( '9123456789012345678901234567890123', 'DE', 'FR', true, 70 ),

			// 12-digit with valid check digit (80).
			array( '476618356000', 'US', 'US', true, 80 ),
			// 12-digit with invalid check digit (80).
			array( '476618356001', 'US', 'US', true, 80 ),

			// Other numeric formats.
			array( '1234567890', 'US', 'US', true, 75 ),
			array( '123456789', 'US', 'US', true, 70 ),
			array( '1234567890123456789012', 'US', 'CA', true, 60 ),

			// Domestic-but-international-tracking countries (boost +5).
			array( '1Z12345E0205271688', 'IN', 'IN', true, 105 ),
			array( 'T1234567890', 'HK', 'HK', true, 90 ), // 85+5

			// Invalid formats.
			array( 'INVALID123', 'CA', 'US', false, null ),
			array( '1Y12345E0205271688', 'US', 'DE', false, null ),
			array( '1Z12345E020527', 'US', 'DE', false, null ),

			// Invalid countries.
			array( '1Z12345E0205271688', 'ZZ', 'US', false, null ),
			array( '1Z12345E0205271688', 'US', 'ZZ', false, null ),
		);
	}

	/**
	 * Test tracking number parsing with various scenarios.
	 *
	 * @param string   $tracking_number The tracking number to test.
	 * @param string   $shipping_from The country code from which the shipment is sent.
	 * @param string   $shipping_to The country code to which the shipment is sent.
	 * @param bool     $has_match Whether the tracking number should match a known format.
	 * @param int|null $expected_score The expected ambiguity score if a match is found.
	 *
	 * @dataProvider trackingNumberProvider
	 */
	public function test_tracking_number_parsing(
		string $tracking_number,
		string $shipping_from,
		string $shipping_to,
		bool $has_match,
		?int $expected_score
	): void {
		$provider = new UPSShippingProvider();
		$result   = $provider->try_parse_tracking_number( $tracking_number, $shipping_from, $shipping_to );

		if ( $has_match ) {
			$this->assertNotNull( $result );
			$this->assertEquals(
				'https://www.ups.com/track?tracknum=' . rawurlencode( strtoupper( $tracking_number ) ),
				$result['url']
			);
			$this->assertEquals( $expected_score, $result['ambiguity_score'] );
		} else {
			$this->assertNull( $result );
		}
	}

	/**
	 * Test T/H format global validity.
	 */
	public function test_th_format_global_validity(): void {
		$provider = new UPSShippingProvider();

		// Should work globally.
		$us_domestic   = $provider->try_parse_tracking_number( 'T1234567890', 'US', 'US' );
		$ca_domestic   = $provider->try_parse_tracking_number( 'T1234567890', 'CA', 'CA' );
		$international = $provider->try_parse_tracking_number( 'T1234567890', 'US', 'CA' );

		$this->assertNotNull( $us_domestic );
		$this->assertNotNull( $ca_domestic );
		$this->assertNotNull( $international );
		$this->assertEquals( 85, $us_domestic['ambiguity_score'] );
	}

	/**
	 * Test SurePost format recognition.
	 */
	public function test_surepost_format(): void {
		$provider = new UPSShippingProvider();
		$result   = $provider->try_parse_tracking_number( '9274345678901234567890', 'US', 'US' );

		$this->assertNotNull( $result );
		$this->assertEquals( 85, $result['ambiguity_score'] );
	}

	/**
	 * Test domestic-but-international-tracking score boost.
	 */
	public function test_domestic_international_tracking_boost(): void {
		$provider = new UPSShippingProvider();

		// India (IN) is in domestic_but_international_tracking.
		$result = $provider->try_parse_tracking_number( '1Z12345E0205271688', 'IN', 'IN' );
		$this->assertNotNull( $result );
		$this->assertEquals( 105, $result['ambiguity_score'] ); // 100 + 5 boost.
	}

	/**
	 * Test case insensitivity.
	 */
	public function test_case_insensitivity(): void {
		$provider = new UPSShippingProvider();

		$results = array(
			$provider->try_parse_tracking_number( '1Z12345E0205271688', 'US', 'DE' ),
			$provider->try_parse_tracking_number( '1z12345e0205271688', 'US', 'DE' ),
			$provider->try_parse_tracking_number( '1z12345E0205271688', 'US', 'DE' ),
		);

		foreach ( $results as $result ) {
			$this->assertNotNull( $result );
			$this->assertEquals( 100, $result['ambiguity_score'] );
		}
	}
}
