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
			$this->assertStringContainsString( rawurlencode( $tracking_number ), $result['url'] );
		}
	}

	/**
	 * Data provider for tracking number tests.
	 *
	 * @return array
	 */
	public function trackingNumberProvider(): array {
		return array(
			array( 'TBA1234567890', 'US', 'US', true, 95 ),
			array( 'TBA987654321X', 'GB', 'GB', true, 95 ),
			array( 'TBA00000000', 'DE', 'FR', true, 95 ),
			array( 'tba111222333', 'US', 'CA', true, 95 ),
			array( 'TBA1234567890', 'US', 'BR', false ),
			array( 'INVALID123456', 'US', 'US', false ),
			array( 'TBX1234567890', 'US', 'US', false ),
		);
	}
}
