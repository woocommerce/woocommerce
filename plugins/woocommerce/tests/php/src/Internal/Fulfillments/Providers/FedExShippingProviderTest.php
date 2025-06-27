<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\FedExShippingProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for FedExShippingProvider class.
 */
class FedExShippingProviderTest extends TestCase {
	/**
	 * The FedEx shipping provider instance.
	 *
	 * @var FedExShippingProvider
	 */
	private FedExShippingProvider $provider;

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		$this->provider = new FedExShippingProvider();
	}

	/**
	 * Test the get_tracking_url method.
	 */
	public function test_get_tracking_url(): void {
		$tracking_number = '123456789012';
		$expected_url    = 'https://www.fedex.com/fedextrack/?tracknumbers=' . rawurlencode( $tracking_number );
		$this->assertEquals( $expected_url, $this->provider->get_tracking_url( $tracking_number ) );
	}

	/**
	 * Test the try_parse_tracking_number method with various tracking numbers.
	 *
	 * @param string $tracking_number The tracking number to test.
	 * @param string $from            The shipping origin country code.
	 * @param string $to              The shipping destination country code.
	 * @param bool   $expected_valid  Whether the tracking number should be valid.
	 * @param int    $expected_score  Expected ambiguity score.
	 *
	 * @dataProvider tracking_number_provider
	 */
	public function test_try_parse_tracking_number( string $tracking_number, string $from, string $to, bool $expected_valid, int $expected_score ): void {
		$result = $this->provider->try_parse_tracking_number( $tracking_number, $from, $to );

		if ( ! $expected_valid ) {
			$this->assertNull( $result );
		} else {
			$this->assertNotNull( $result );
			$this->assertIsArray( $result );
			$this->assertEquals( $expected_score, $result['ambiguity_score'] );
		}
	}

	/**
	 * Data provider for tracking number tests.
	 *
	 * @return array
	 */
	public function tracking_number_provider(): array {
		return array(
			// Valid tracking numbers.
			array( '123456789012', 'US', 'CA', true, 100 ), // 12 digit
			array( '123456789012345', 'CA', 'US', true, 85 ), // 15 digit
			array( '12345678901234567890', 'US', 'US', true, 60 ), // 20 digit

			// Invalid numbers.
			array( '1234567890', 'US', 'CA', false, 0 ),
			array( 'ABCDEFGHIJKL', 'US', 'US', false, 0 ),
			array( '123456789012', 'FR', 'FR', true, 100 ), // Valid origin.
			array( '123456789012', 'US', 'ZZ', false, 0 ), // Invalid destination.
			array( '123456789012', 'ZZ', 'US', false, 0 ), // Invalid origin.
		);
	}
}
