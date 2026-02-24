<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\CanadaPostShippingProvider;

/**
 * Unit tests for CanadaPostShippingProvider class.
 *
 * @package WooCommerce\Tests\Internal\Fulfillments\Providers
 */
class CanadaPostShippingProviderTest extends \WP_UnitTestCase {
	/**
	 * Instance of CanadaPostShippingProvider used in tests.
	 *
	 * @var CanadaPostShippingProvider
	 */
	private CanadaPostShippingProvider $provider;

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->provider = new CanadaPostShippingProvider();
	}

	/**
	 * Test the get_key method.
	 */
	public function test_get_key(): void {
		$this->assertSame( 'canada-post', $this->provider->get_key() );
	}

	/**
	 * Test the get_name method.
	 */
	public function test_get_name(): void {
		$this->assertSame( 'Canada Post', $this->provider->get_name() );
	}

	/**
	 * Test the get_tracking_url method.
	 */
	public function test_get_tracking_url(): void {
		$tracking_number = 'AB123456789CA';
		$expected_url    = 'https://www.canadapost-postescanada.ca/track-reperage/en#/search?searchFor=' . $tracking_number;
		$this->assertSame( $expected_url, $this->provider->get_tracking_url( $tracking_number ) );
	}

	/**
	 * Test get_shipping_from_countries returns expected countries.
	 */
	public function test_get_shipping_from_countries(): void {
		$countries = $this->provider->get_shipping_from_countries();

		// Test that Canada is included.
		$expected_countries = array( 'CA' );
		foreach ( $expected_countries as $country ) {
			$this->assertContains( $country, $countries, "Country {$country} should be in shipping from countries" );
		}

		// Test that we have the expected number of countries (only Canada).
		$this->assertSame( 1, count( $countries ), 'Should have exactly 1 country (Canada)' );
	}

	/**
	 * Test get_shipping_to_countries includes international destinations.
	 */
	public function test_get_shipping_to_countries(): void {
		$to_countries = $this->provider->get_shipping_to_countries();

		// Test that common destinations are included.
		$expected_destinations = array( 'CA', 'US', 'GB', 'FR', 'DE', 'AU', 'JP' );
		foreach ( $expected_destinations as $country ) {
			$this->assertContains( $country, $to_countries, "Country {$country} should be in shipping to countries" );
		}

		// Test that we have many international destinations.
		$this->assertGreaterThan( 190, count( $to_countries ), 'Should have many international destinations' );
	}

	/**
	 * Data provider for valid tracking number parsing tests.
	 *
	 * @return array[]
	 */
	public function validTrackingNumberProvider(): array {
		return array(
			// Standard format: XX#########CA.
			array( 'AB123456789CA', 'CA', 'US', 94 ),   // International shipment (92+2 for US).
			array( 'CD987654321CA', 'CA', 'CA', 95 ),   // Domestic shipment with boost (92+3).
			array( 'EF555666777CA', 'CA', 'GB', 92 ),   // International shipment (base 92).

			// 16-digit domestic tracking.
			array( '1234567890123456', 'CA', 'CA', 95 ), // Domestic with boost (92+3).
			array( '9876543210987654', 'CA', 'US', 94 ), // US destination (92+2).

			// 12-digit domestic tracking.
			array( '123456789012', 'CA', 'CA', 95 ),     // Domestic with boost (92+3).
			array( '987654321098', 'CA', 'AU', 96 ),     // International (92+4 check digit bonus).

			// International format: XX#######XX.
			array( 'AB1234567CD', 'CA', 'FR', 92 ),      // International (base 92).
			array( 'EF9876543GH', 'CA', 'CA', 95 ),      // Domestic with boost (92+3).

			// Some domestic formats: X#########X.
			array( 'A123456789B', 'CA', 'CA', 95 ),      // Domestic with boost (92+3).
			array( 'C987654321D', 'CA', 'DE', 92 ),      // International (base 92).
		);
	}

	/**
	 * Data provider for invalid tracking number parsing tests.
	 *
	 * @return array[]
	 */
	public function invalidTrackingNumberProvider(): array {
		return array(
			// Wrong origin country (not Canada).
			array( 'AB123456789CA', 'US', 'CA' ),        // From US instead of CA.
			array( '1234567890123456', 'GB', 'CA' ),     // From GB instead of CA.
			array( '123456789012', 'DE', 'US' ),         // From DE instead of CA.

			// Too short.
			array( '12345', 'CA', 'US' ),                // Too short.
			array( 'AB123CA', 'CA', 'GB' ),              // Too short for standard format.

			// Too long.
			array( '12345678901234567890', 'CA', 'US' ), // Too long.
			array( 'AB123456789012345CA', 'CA', 'GB' ),  // Too long for standard format.

			// Invalid format.
			array( '123456789AB', 'CA', 'US' ),          // Mixed format invalid.
			array( 'ABCDEFGHIJK', 'CA', 'GB' ),          // All letters invalid length.

			// Empty or whitespace only.
			array( '', 'CA', 'US' ),                     // Empty string.
			array( '   ', 'CA', 'GB' ),                  // Whitespace only.

			// Invalid characters.
			array( '12-34-56-78-90-12', 'CA', 'US' ),    // Dashes (invalid format).
			array( '123.456.789.012', 'CA', 'GB' ),      // Dots (invalid format).
		);
	}

	/**
	 * Test try_parse_tracking_number method with valid tracking numbers.
	 *
	 * @dataProvider validTrackingNumberProvider
	 *
	 * @param string $tracking_number The tracking number to test.
	 * @param string $from            Origin country.
	 * @param string $to              Destination country.
	 * @param int    $expected_score  Expected ambiguity score.
	 */
	public function test_try_parse_tracking_number_valid( string $tracking_number, string $from, string $to, int $expected_score ): void {
		$result = $this->provider->try_parse_tracking_number( $tracking_number, $from, $to );

		$this->assertIsArray( $result, "Should return array for valid tracking number: {$tracking_number}" );
		$this->assertArrayHasKey( 'url', $result );
		$this->assertArrayHasKey( 'ambiguity_score', $result );

		// Check score matches expected.
		$this->assertSame(
			$expected_score,
			$result['ambiguity_score'],
			"Score should be {$expected_score} for {$tracking_number} from {$from} to {$to}"
		);

		// Check score is within valid range.
		$this->assertGreaterThanOrEqual( 92, $result['ambiguity_score'], 'Score should be at least 92' );
		$this->assertLessThanOrEqual( 98, $result['ambiguity_score'], 'Score should not exceed 98' );

		// Check URL format.
		$normalized_tracking = strtoupper( preg_replace( '/\s+/', '', $tracking_number ) );
		$expected_url        = $this->provider->get_tracking_url( $normalized_tracking );
		$this->assertSame( $expected_url, $result['url'] );
	}

	/**
	 * Test try_parse_tracking_number method with invalid tracking numbers.
	 *
	 * @dataProvider invalidTrackingNumberProvider
	 *
	 * @param string $tracking_number The tracking number to test.
	 * @param string $from            Origin country.
	 * @param string $to              Destination country.
	 */
	public function test_try_parse_tracking_number_invalid( string $tracking_number, string $from, string $to ): void {
		$result = $this->provider->try_parse_tracking_number( $tracking_number, $from, $to );
		$this->assertNull( $result, "Should return null for invalid tracking number: '{$tracking_number}'" );
	}

	/**
	 * Test tracking number normalization (spaces, case sensitivity).
	 */
	public function test_tracking_number_normalization(): void {
		$test_cases = array(
			// With spaces.
			array( 'AB 123 456 789 CA', 'CA', 'US' ),
			array( '  AB123456789CA  ', 'CA', 'GB' ),
			array( '1234 5678 9012 3456', 'CA', 'AU' ),

			// Mixed case.
			array( 'ab123456789ca', 'CA', 'US' ),
			array( 'Ab123456789Ca', 'CA', 'GB' ),
			array( 'AB123456789CA', 'CA', 'FR' ),
		);

		foreach ( $test_cases as $test_case ) {
			list( $tracking_number, $from, $to ) = $test_case;
			$result                              = $this->provider->try_parse_tracking_number( $tracking_number, $from, $to );

			$this->assertIsArray( $result, "Should parse tracking number with normalization: {$tracking_number}" );
			$this->assertArrayHasKey( 'url', $result );

			// URL should contain normalized version (no spaces, uppercase).
			$normalized = strtoupper( preg_replace( '/\s+/', '', $tracking_number ) );
			$this->assertStringContainsString( $normalized, $result['url'] );
		}
	}

	/**
	 * Test empty parameter handling.
	 */
	public function test_empty_parameters(): void {
		// Empty tracking number.
		$result = $this->provider->try_parse_tracking_number( '', 'CA', 'US' );
		$this->assertNull( $result );

		// Empty origin country.
		$result = $this->provider->try_parse_tracking_number( 'AB123456789CA', '', 'US' );
		$this->assertNull( $result );

		// Empty destination country.
		$result = $this->provider->try_parse_tracking_number( 'AB123456789CA', 'CA', '' );
		$this->assertNull( $result );

		// All empty.
		$result = $this->provider->try_parse_tracking_number( '', '', '' );
		$this->assertNull( $result );
	}

	/**
	 * Test domestic vs international scoring.
	 */
	public function test_domestic_vs_international_scoring(): void {
		// Domestic shipment should get confidence boost.
		$result_domestic      = $this->provider->try_parse_tracking_number( 'AB123456789CA', 'CA', 'CA' );
		$result_international = $this->provider->try_parse_tracking_number( 'AB123456789CA', 'CA', 'US' );

		$this->assertIsArray( $result_domestic );
		$this->assertIsArray( $result_international );

		// Domestic should have higher score (95 vs 94).
		$this->assertSame( 95, $result_domestic['ambiguity_score'] );
		$this->assertSame( 94, $result_international['ambiguity_score'] ); // US gets +2 boost.
		$this->assertGreaterThan( $result_international['ambiguity_score'], $result_domestic['ambiguity_score'] );
	}

	/**
	 * Test specific pattern formats.
	 */
	public function test_pattern_formats(): void {
		// Test standard format XX#########CA.
		$standard_result = $this->provider->try_parse_tracking_number( 'AB123456789CA', 'CA', 'US' );
		$this->assertIsArray( $standard_result );
		$this->assertSame( 94, $standard_result['ambiguity_score'] ); // US destination gets +2.

		// Test 16-digit format.
		$digit16_result = $this->provider->try_parse_tracking_number( '1234567890123456', 'CA', 'FR' );
		$this->assertIsArray( $digit16_result );
		$this->assertSame( 92, $digit16_result['ambiguity_score'] ); // International base.

		// Test 12-digit format.
		$digit12_result = $this->provider->try_parse_tracking_number( '123456789012', 'CA', 'MX' );
		$this->assertIsArray( $digit12_result );
		$this->assertSame( 94, $digit12_result['ambiguity_score'] ); // MX destination gets +2.

		// Test international format XX#######XX.
		$intl_result = $this->provider->try_parse_tracking_number( 'AB1234567CD', 'CA', 'GB' );
		$this->assertIsArray( $intl_result );
		$this->assertSame( 92, $intl_result['ambiguity_score'] ); // International base.

		// Test domestic format X########X.
		$domestic_result = $this->provider->try_parse_tracking_number( 'A123456789B', 'CA', 'AU' );
		$this->assertIsArray( $domestic_result );
		$this->assertSame( 92, $domestic_result['ambiguity_score'] ); // International base.
	}

	/**
	 * Test non-Canada origin rejection.
	 */
	public function test_non_canada_origin_rejection(): void {
		$non_canada_origins = array( 'US', 'GB', 'FR', 'DE', 'AU', 'JP' );

		foreach ( $non_canada_origins as $origin ) {
			$result = $this->provider->try_parse_tracking_number( 'AB123456789CA', $origin, 'CA' );
			$this->assertNull( $result, "Should reject tracking number from non-Canada origin: {$origin}" );
		}
	}
}
