<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\DHLShippingProvider;

/**
 * DHLShippingProvider Test
 */
class DHLShippingProviderTest extends \WP_UnitTestCase {
	/**
	 * The DHL shipping provider instance.
	 *
	 * @var DHLShippingProvider
	 */
	private DHLShippingProvider $provider;

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		$this->provider = new DHLShippingProvider();
	}

	/**
	 * Test the get_key method.
	 */
	public function test_get_tracking_url(): void {
		$tracking_number = '1234567890';
		$expected        = 'https://www.dhl.com/global-en/home/tracking.html?tracking-id=' . rawurlencode( $tracking_number );
		$this->assertEquals( $expected, $this->provider->get_tracking_url( $tracking_number ) );
	}

	/**
	 * Test the try_parse_tracking_number method with various tracking numbers.
	 *
	 * @param string   $tracking_number The tracking number to test.
	 * @param string   $from            The shipping origin country code.
	 * @param string   $to              The shipping destination country code.
	 * @param bool     $is_valid        Whether the tracking number should be valid.
	 * @param int|null $score         Expected ambiguity score, or null if no match.
	 *
	 * @dataProvider tracking_number_provider
	 */
	public function test_try_parse_tracking_number( string $tracking_number, string $from, string $to, bool $is_valid, ?int $score ): void {
		$result = $this->provider->try_parse_tracking_number( $tracking_number, $from, $to );

		if ( $is_valid ) {
			$this->assertNotNull( $result );
			$this->assertEquals( $score, $result['ambiguity_score'] );
		} else {
			$this->assertNull( $result );
		}
	}

	/**
	 * Data provider for tracking number tests.
	 *
	 * @return array
	 */
	public function tracking_number_provider(): array {
		return array(
			// ✅ Valid DHL formats
			array( '1234567890', 'DE', 'US', true, 80 ),
			array( 'JJD1234567890', 'US', 'FR', true, 95 ),
			array( 'JJD00123456789012', 'GB', 'IT', true, 95 ),

			// ❌ Invalid formats
			array( 'INVALID1234', 'DE', 'US', false, null ),
			array( '12345', 'US', 'US', false, null ),
			array( 'JJD123', 'JP', 'GB', false, null ),

			// ❌ Invalid countries
			array( '1234567890', 'ZZ', 'US', false, null ),
			array( '1234567890', 'DE', 'ZZ', false, null ),
		);
	}
}
