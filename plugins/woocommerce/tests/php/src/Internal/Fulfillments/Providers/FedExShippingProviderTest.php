<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\FedExShippingProvider;

/**
 * Unit tests for FedExShippingProvider class.
 */
class FedExShippingProviderTest extends \WP_UnitTestCase {
	/**
	 * The provider instance being tested.
	 *
	 * @var FedExShippingProvider
	 */
	private FedExShippingProvider $provider;

	/**
	 * Sets up the test fixture.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->provider = new FedExShippingProvider();
	}

	/**
	 * Tests the tracking URL generation.
	 */
	public function test_get_tracking_url(): void {
		$tracking_number = '123456789012';
		$expected_url    = 'https://www.fedex.com/fedextrack/?tracknumbers=' . rawurlencode( $tracking_number );
		$this->assertEquals( $expected_url, $this->provider->get_tracking_url( $tracking_number ) );
	}

	/**
	 * Data provider for tracking number validation tests.
	 *
	 * @return array<array{string, string, string, bool, int}> Test cases.
	 */
	public function trackingNumberProvider(): array {
		return array(
			// FedEx Express - 12 digit (95 score).
			array( '123456789012', 'US', 'CA', true, 95 ),
			array( '999888777666', 'CA', 'US', true, 95 ),

			// FedEx Custom Critical (98 score).
			array( '001234567890123456789012', 'US', 'CA', true, 98 ),
			array( '011234567890123456789012', 'US', 'US', true, 98 ),

			// FedEx SmartPost (96 score).
			array( '02312345678901234567', 'US', 'US', true, 96 ),
			array( '58123456789012345678', 'US', 'CA', true, 96 ),

			// FedEx Ground - 96 prefix (95 US/CA, 60 others).
			array( '9611020987654312345678', 'US', 'US', true, 95 ),
			array( '9611020987654312345678', 'CA', 'US', true, 95 ),
			array( '9611020987654312345678', 'DE', 'FR', true, 60 ),

			// FedEx Freight (93 score).
			array( '9712345678901234567890123', 'US', 'CA', true, 93 ),
			array( '971234567890123', 'US', 'US', true, 93 ),

			// FedEx Express - 3x patterns (96 score).
			array( '31234567890', 'US', 'DE', true, 96 ),
			array( '398765432109876', 'CA', 'US', true, 96 ),

			// FedEx Ground - 7x patterns (90 US/CA, 75 others).
			array( '712345678901234567890', 'US', 'US', true, 90 ),
			array( '712345678901234567890', 'CA', 'CA', true, 90 ),
			array( '712345678901234567890', 'DE', 'FR', true, 75 ),

			// FedEx Express - 14 digit (95 score).
			array( '12345678901234', 'US', 'FR', true, 95 ),
			array( '98765432109876', 'GB', 'DE', true, 80 ), // Reduced for EU.

			// FedEx Express - 15 digit (90 score).
			array( '123456789012345', 'CA', 'US', true, 90 ),
			array( '987654321098765', 'US', 'GB', true, 90 ),

			// FedEx Express - 20 digit (70 score).
			array( '12345678901234567890', 'US', 'DE', true, 70 ),
			array( '98765432109876543210', 'FR', 'IT', true, 70 ),

			// Invalid formats.
			array( '1234567890', 'US', 'CA', false, 0 ), // Too short.
			array( 'ABCDEFGHIJKL', 'US', 'US', false, 0 ), // Invalid characters.
			array( '12345', 'CA', 'US', false, 0 ), // Too short.

			// Invalid countries.
			array( '123456789012', 'ZZ', 'US', false, 0 ), // Invalid origin.
			array( '123456789012', 'US', 'ZZ', false, 0 ), // Invalid destination.

			// International validations.
			array( '123456789012', 'DE', 'FR', true, 95 ),
			array( '123456789012', 'JP', 'AU', true, 95 ),
		);
	}

	/**
	 * Tests tracking number parsing with various scenarios.
	 *
	 * @dataProvider trackingNumberProvider
	 * @param string $tracking_number The tracking number to test.
	 * @param string $from Origin country code.
	 * @param string $to Destination country code.
	 * @param bool   $expected_valid Whether the number should be valid.
	 * @param int    $expected_score Expected ambiguity score.
	 */
	public function test_try_parse_tracking_number(
		string $tracking_number,
		string $from,
		string $to,
		bool $expected_valid,
		int $expected_score
	): void {
		$result = $this->provider->try_parse_tracking_number( $tracking_number, $from, $to );

		if ( ! $expected_valid ) {
			$this->assertNull( $result );
		} else {
			$this->assertNotNull( $result );
			$this->assertEquals(
				'https://www.fedex.com/fedextrack/?tracknumbers=' . rawurlencode( $tracking_number ),
				$result['url']
			);
			$this->assertEquals( $expected_score, $result['ambiguity_score'] );
		}
	}

	/**
	 * Tests FedEx Ground regional scoring differences.
	 */
	public function test_ground_regional_restrictions(): void {
		$us_result = $this->provider->try_parse_tracking_number( '9611020987654312345678', 'US', 'CA' );
		$de_result = $this->provider->try_parse_tracking_number( '9611020987654312345678', 'DE', 'FR' );

		$this->assertNotNull( $us_result );
		$this->assertNotNull( $de_result );
		$this->assertGreaterThan( $de_result['ambiguity_score'], $us_result['ambiguity_score'] );
	}

	/**
	 * Tests the scoring hierarchy between different formats.
	 */
	public function test_format_confidence_hierarchy(): void {
		$custom_critical = $this->provider->try_parse_tracking_number( '001234567890123456789012', 'US', 'CA' );
		$express_12      = $this->provider->try_parse_tracking_number( '123456789012', 'US', 'CA' );
		$express_15      = $this->provider->try_parse_tracking_number( '123456789012345', 'US', 'CA' );
		$generic_20      = $this->provider->try_parse_tracking_number( '12345678901234567890', 'US', 'CA' );

		$this->assertGreaterThan( $express_12['ambiguity_score'], $custom_critical['ambiguity_score'] );
		$this->assertGreaterThan( $express_15['ambiguity_score'], $express_12['ambiguity_score'] );
		$this->assertGreaterThan( $generic_20['ambiguity_score'], $express_15['ambiguity_score'] );
	}
}
