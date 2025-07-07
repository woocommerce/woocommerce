<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\AmazonLogisticsShippingProvider;

/**
 * Unit tests for AmazonLogisticsShippingProvider class.
 */
class AmazonLogisticsShippingProviderTest extends \WP_UnitTestCase {
	/**
	 * The provider instance being tested.
	 *
	 * @var AmazonLogisticsShippingProvider
	 */
	private AmazonLogisticsShippingProvider $provider;

	/**
	 * Sets up the test fixture.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->provider = new AmazonLogisticsShippingProvider();
	}

	/**
	 * Tests the tracking URL generation.
	 */
	public function test_get_tracking_url(): void {
		$this->assertEquals(
			'https://www.amazon.com/progress-tracker/package/ref=ppx_yo_dt_b_track_package_o0?_=TBA123456789012',
			$this->provider->get_tracking_url( 'TBA123456789012' )
		);

		// Test case insensitivity.
		$this->assertEquals(
			'https://www.amazon.com/progress-tracker/package/ref=ppx_yo_dt_b_track_package_o0?_=TBC123456789012',
			$this->provider->get_tracking_url( 'tbc123456789012' )
		);

		// Test special characters.
		$this->assertStringContainsString(
			rawurlencode( 'TBA-123_456/789' ),
			$this->provider->get_tracking_url( 'TBA-123_456/789' )
		);
	}

	/**
	 * Data provider for tracking number validation tests.
	 *
	 * @return array<array{string, string, string, bool, int|null}> Test cases.
	 */
	public function trackingNumberProvider(): array {
		return array(
			// TBA format - US standard (12 digits).
			array( 'TBA123456789012', 'US', 'US', true, 100 ),
			array( 'TBA123456789012', 'CA', 'US', true, 95 ),
			array( 'TBA123456789012', 'DE', 'FR', true, 95 ),

			// TBC format - Canada standard.
			array( 'TBC123456789012', 'CA', 'US', true, 100 ),
			array( 'TBC123456789012', 'US', 'CA', true, 90 ),
			array( 'TBC123456789012', 'DE', 'FR', true, 90 ),

			// TBM format - Mexico standard.
			array( 'TBM987654321098', 'MX', 'US', true, 100 ),
			array( 'TBM987654321098', 'US', 'MX', true, 85 ),
			array( 'TBM987654321098', 'GB', 'FR', true, 85 ),

			// CC format - Europe.
			array( 'CC123456789012', 'FR', 'DE', true, 95 ),
			array( 'CC123456789012', 'BE', 'NL', true, 95 ),
			array( 'CC123456789012', 'US', 'CA', true, 80 ),

			// GBA format - United Kingdom.
			array( 'GBA123456789012', 'GB', 'US', true, 100 ),
			array( 'GBA123456789012', 'US', 'GB', true, 85 ),

			// RB format - China/Hong Kong.
			array( 'RB123456789012', 'CN', 'US', true, 95 ),
			array( 'RB123456789012', 'HK', 'US', true, 95 ),
			array( 'RB123456789012', 'US', 'CN', true, 75 ),

			// ZZ format - Australia.
			array( 'ZZ123456789012', 'AU', 'US', true, 100 ),
			array( 'ZZ123456789012', 'US', 'AU', true, 80 ),

			// ZX format - India.
			array( 'ZX123456789012', 'IN', 'US', true, 100 ),
			array( 'ZX123456789012', 'US', 'IN', true, 85 ),

			// Fallback format - matches 15-20 character codes.
			array( 'ABC123456789012', 'US', 'US', true, 60 ),  // 15 char fallback.
			array( 'ABCD123456789012', 'US', 'US', true, 60 ), // 16 char fallback.
			array( 'AMZN123456789012', 'US', 'US', true, 60 ), // 16 char fallback.

			// Invalid formats.
			array( 'TB123456789012', 'US', 'US', false, null ),  // Incomplete prefix.
			array( '123456789012', 'US', 'US', false, null ),    // No prefix.
			array( 'L123456789012', 'US', 'US', false, null ),   // Invalid L format (China Post).

			// Invalid lengths.
			array( 'TBA123', 'US', 'US', false, null ),        // Too short.
			array( 'TBA1234567890123456789012', 'US', 'US', false, null ), // Too long (24 chars).
			array( 'TBA12345678901', 'US', 'US', true, 90 ), // 14 chars - matches TB[A-Z] pattern.

			// Invalid country routes.
			array( 'TBA123456789012', 'ZZ', 'US', false, null ), // Invalid origin.
			array( 'TBA123456789012', 'US', 'ZZ', false, null ), // Invalid destination.
		);
	}

	/**
	 * Tests tracking number parsing with various scenarios.
	 *
	 * @dataProvider trackingNumberProvider
	 * @param string   $tracking_number The tracking number to test.
	 * @param string   $from Origin country code.
	 * @param string   $to Destination country code.
	 * @param bool     $expected_valid Whether the number should be valid.
	 * @param int|null $expected_score Expected ambiguity score.
	 */
	public function test_try_parse_tracking_number(
		string $tracking_number,
		string $from,
		string $to,
		bool $expected_valid,
		?int $expected_score
	): void {
		$result = $this->provider->try_parse_tracking_number( $tracking_number, $from, $to );

		if ( $expected_valid ) {
			$this->assertNotNull( $result );
			$this->assertEquals( $expected_score, $result['ambiguity_score'] );
			$this->assertStringContainsString(
				rawurlencode( strtoupper( $tracking_number ) ),
				$result['url']
			);
		} else {
			$this->assertNull( $result );
		}
	}

	/**
	 * Tests regional scoring differences.
	 */
	public function test_regional_scoring_differences(): void {
		// TBA format scores higher from US.
		$us_result = $this->provider->try_parse_tracking_number( 'TBA123456789012', 'US', 'DE' );
		$de_result = $this->provider->try_parse_tracking_number( 'TBA123456789012', 'DE', 'US' );

		$this->assertEquals( 100, $us_result['ambiguity_score'] );
		$this->assertEquals( 95, $de_result['ambiguity_score'] );

		// TBC format scores higher from CA.
		$ca_result = $this->provider->try_parse_tracking_number( 'TBC123456789012', 'CA', 'US' );
		$us_result = $this->provider->try_parse_tracking_number( 'TBC123456789012', 'US', 'CA' );

		$this->assertEquals( 100, $ca_result['ambiguity_score'] );
		$this->assertEquals( 90, $us_result['ambiguity_score'] );

		// TBM format scores higher from MX.
		$mx_result = $this->provider->try_parse_tracking_number( 'TBM123456789012', 'MX', 'US' );
		$us_result = $this->provider->try_parse_tracking_number( 'TBM123456789012', 'US', 'MX' );

		$this->assertEquals( 100, $mx_result['ambiguity_score'] );
		$this->assertEquals( 85, $us_result['ambiguity_score'] );

		// GBA format scores higher from GB.
		$gb_result = $this->provider->try_parse_tracking_number( 'GBA123456789012', 'GB', 'US' );
		$us_result = $this->provider->try_parse_tracking_number( 'GBA123456789012', 'US', 'GB' );

		$this->assertEquals( 100, $gb_result['ambiguity_score'] );
		$this->assertEquals( 85, $us_result['ambiguity_score'] );
	}

	/**
	 * Tests case insensitivity in tracking number parsing.
	 */
	public function test_case_insensitivity(): void {
		$lowercase = $this->provider->try_parse_tracking_number( 'tba123456789012', 'US', 'US' );
		$mixedcase = $this->provider->try_parse_tracking_number( 'TbA123456789012', 'US', 'US' );
		$uppercase = $this->provider->try_parse_tracking_number( 'TBA123456789012', 'US', 'US' );

		$this->assertEquals( 100, $lowercase['ambiguity_score'] );
		$this->assertEquals( 100, $mixedcase['ambiguity_score'] );
		$this->assertEquals( 100, $uppercase['ambiguity_score'] );
	}

	/**
	 * Tests whitespace handling in tracking numbers.
	 */
	public function test_whitespace_handling(): void {
		$result = $this->provider->try_parse_tracking_number( ' TBA 123 456 789 012 ', 'US', 'US' );
		$this->assertNotNull( $result );
		$this->assertStringContainsString( 'TBA123456789012', $result['url'] );
	}

	/**
	 * Tests provider metadata.
	 */
	public function test_provider_metadata(): void {
		$this->assertEquals( 'amazon-logistics', $this->provider->get_key() );
		$this->assertEquals( 'Amazon Logistics', $this->provider->get_name() );
		$this->assertStringEndsWith(
			'/assets/images/shipping_providers/amazon-logistics.png',
			$this->provider->get_icon()
		);
	}
}
