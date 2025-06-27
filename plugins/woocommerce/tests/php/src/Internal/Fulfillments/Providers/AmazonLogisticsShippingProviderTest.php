<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\AmazonLogisticsShippingProvider;

/**
 * AmazonLogisticsShippingProvider Test
 */
class AmazonLogisticsShippingProviderTest extends \WP_UnitTestCase {
	/**
	 * The Amazon Logistics shipping provider instance.
	 *
	 * @var AmazonLogisticsShippingProvider
	 */
	private AmazonLogisticsShippingProvider $provider;

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		$this->provider = new AmazonLogisticsShippingProvider();
	}

	/**
	 * Test the get_key method.
	 */
	public function test_get_tracking_url(): void {
		$tracking_number = 'TBA1234567890';
		$expected_url    = 'https://www.amazon.com/progress-tracker/package/ref=ppx_yo_dt_b_track_package_o0?_=' . rawurlencode( $tracking_number );
		$this->assertEquals( $expected_url, $this->provider->get_tracking_url( $tracking_number ) );
	}

	/**
	 * Test the try_parse_tracking_number method with various tracking numbers.
	 *
	 * @param string $tracking_number The tracking number to test.
	 * @param string $from            The shipping origin country code.
	 * @param string $to              The shipping destination country code.
	 * @param bool   $is_valid        Whether the tracking number should be valid.
	 * @param int    $expected_score  Expected ambiguity score.
	 * @dataProvider trackingNumberProvider
	 */
	public function test_try_parse_tracking_number( string $tracking_number, string $from, string $to, bool $is_valid, int $expected_score = 0 ): void {
		$result = $this->provider->try_parse_tracking_number( $tracking_number, $from, $to );

		if ( ! $is_valid ) {
			$this->assertNull( $result );
		} else {
			$this->assertNotNull( $result );
			$this->assertEquals( $expected_score, $result['ambiguity_score'] );
			$this->assertStringContainsString( rawurlencode( strtoupper( $tracking_number ) ), $result['url'] );
		}
	}

	/**
	 * Data provider for tracking number tests.
	 *
	 * @return array
	 */
	public function trackingNumberProvider(): array {
		return array(
			// TBA (always accepted, high confidence).
			array( 'TBA1234567890', 'US', 'US', true, 100 ),
			array( 'TBAabcdef1234', 'DE', 'DE', true, 100 ),

			// TBC (contextual confidence).
			array( 'TBC1234567890', 'CA', 'CA', true, 90 ),
			array( 'TBC1234567890', 'GB', 'GB', true, 90 ),
			array( 'TBC1234567890', 'US', 'US', true, 60 ),

			// TBM (lower confidence outside India/MX).
			array( 'TBM9876543210', 'IN', 'IN', true, 85 ),
			array( 'TBM9876543210', 'MX', 'MX', true, 85 ),
			array( 'TBM9876543210', 'GB', 'GB', true, 50 ),

			// Invalids.
			array( 'TBC12345', 'CA', 'CA', false, 0 ),
			array( 'XYZ1234567890', 'US', 'US', false, 0 ),
			array( '1234567890', 'US', 'US', false, 0 ),
		);
	}
}
