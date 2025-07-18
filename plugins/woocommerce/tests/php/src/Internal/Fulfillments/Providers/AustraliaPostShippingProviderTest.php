<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\AustraliaPostShippingProvider;

/**
 * Unit tests for AustraliaPostShippingProvider class.
 *
 * @package WooCommerce\Tests\Internal\Fulfillments\Providers
 */
class AustraliaPostShippingProviderTest extends \WP_UnitTestCase {
	/**
	 * Instance of AustraliaPostShippingProvider used in tests.
	 *
	 * @var AustraliaPostShippingProvider
	 */
	private AustraliaPostShippingProvider $provider;

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->provider = new AustraliaPostShippingProvider();
	}

	/**
	 * Test the get_key method.
	 */
	public function test_get_key(): void {
		$this->assertSame( 'australia-post', $this->provider->get_key() );
	}

	/**
	 * Test the get_name method.
	 */
	public function test_get_name(): void {
		$this->assertSame( 'Australia Post', $this->provider->get_name() );
	}

	/**
	 * Test the get_tracking_url method.
	 */
	public function test_get_tracking_url(): void {
		$tracking_number = 'AB123456789AU';
		$expected_url    = 'https://auspost.com.au/mypost/track/details/' . $tracking_number;
		$this->assertSame( $expected_url, $this->provider->get_tracking_url( $tracking_number ) );
	}

	/**
	 * Test get_shipping_from_countries returns expected countries.
	 */
	public function test_get_shipping_from_countries(): void {
		$countries = $this->provider->get_shipping_from_countries();

		// Test that Australia is included.
		$expected_countries = array( 'AU' );
		foreach ( $expected_countries as $country ) {
			$this->assertContains( $country, $countries, "Country {$country} should be in shipping from countries" );
		}

		// Test that we have the expected number of countries (only Australia).
		$this->assertSame( 1, count( $countries ), 'Should have exactly 1 country (Australia)' );
	}

	/**
	 * Test get_shipping_to_countries includes international destinations.
	 */
	public function test_get_shipping_to_countries(): void {
		$to_countries = $this->provider->get_shipping_to_countries();

		// Test that common destinations are included.
		$expected_destinations = array( 'AU', 'US', 'NZ', 'GB', 'SG', 'JP', 'CA', 'DE' );
		foreach ( $expected_destinations as $country ) {
			$this->assertContains( $country, $to_countries, "Country {$country} should be in shipping to countries" );
		}

		// Test that we have many international destinations.
		$this->assertGreaterThan( 180, count( $to_countries ), 'Should have many international destinations' );
	}

	/**
	 * Data provider for valid tracking number parsing tests.
	 *
	 * @return array[]
	 */
	public function validTrackingNumberProvider(): array {
		return array(
			// International UPU S10 format: XX#########AU.
			array( 'AB123456789AU', 'AU', 'US', 92 ),   // Common destination with boost (90+2).
			array( 'CD987654321AU', 'AU', 'AU', 95 ),   // Domestic shipment with boost (90+5).
			array( 'EF555666777AU', 'AU', 'NZ', 93 ),   // APAC destination with boost (90+3).

			// Alternative international format: XX#######AU.
			array( 'AB1234567AU', 'AU', 'SG', 93 ),     // APAC destination with boost (90+3).
			array( 'CD9876543AU', 'AU', 'AU', 95 ),     // Domestic with boost (90+5).
			array( 'EF5556667AU', 'AU', 'GB', 92 ),     // Common destination with boost (90+2).

			// 13-digit domestic tracking.
			array( '1234567890123', 'AU', 'AU', 95 ),   // Domestic with boost (90+5).
			array( '9876543210987', 'AU', 'HK', 95 ),   // APAC destination with boost, has valid check digit (90+3+8->95).
			array( '5556667778889', 'AU', 'BR', 90 ),   // International base score.

			// 12-digit domestic tracking.
			array( '123456789012', 'AU', 'AU', 95 ),    // Domestic with boost (90+5).
			array( '987654321098', 'AU', 'JP', 95 ),    // APAC destination with boost, has valid check digit (90+3+8->95).
			array( '555666777888', 'AU', 'CA', 92 ),    // Common destination with boost (90+2).

			// 11-digit domestic tracking.
			array( '12345678901', 'AU', 'AU', 95 ),     // Domestic with boost (90+5).
			array( '98765432109', 'AU', 'KR', 93 ),     // APAC destination with boost (90+3).
			array( '55566677788', 'AU', 'DE', 92 ),     // Common destination with boost (90+2).

			// Standard format: XX########XX.
			array( 'AB12345678CD', 'AU', 'AU', 95 ),    // Domestic with boost (90+5).
			array( 'EF98765432GH', 'AU', 'TH', 93 ),    // APAC destination with boost (90+3).
			array( 'IJ55566677KL', 'AU', 'US', 92 ),    // Common destination with boost (90+2).

			// Domestic format: X##########X.
			array( 'A1234567890B', 'AU', 'AU', 95 ),    // Domestic with boost (90+5).
			array( 'C9876543210D', 'AU', 'MY', 93 ),    // APAC destination with boost (90+3).
			array( 'E5556667778F', 'AU', 'GB', 92 ),    // Common destination with boost (90+2).

			// Express Post format: XXXX########.
			array( 'ABCD12345678', 'AU', 'AU', 95 ),    // Domestic with boost (90+5).
			array( 'EFGH98765432', 'AU', 'ID', 93 ),    // APAC destination with boost (90+3).
			array( 'IJKL55566677', 'AU', 'CA', 92 ),    // Common destination with boost (90+2).

			// 16-digit format starting with 7.
			array( '7123456789012345', 'AU', 'AU', 95 ), // Domestic with boost (90+5).
			array( '7987654321098765', 'AU', 'PH', 93 ), // APAC destination with boost (90+3).
			array( '7555666777888999', 'AU', 'BR', 90 ), // International base score.

			// 16-digit format starting with 3.
			array( '3123456789012345', 'AU', 'AU', 95 ), // Domestic with boost (90+5).
			array( '3987654321098765', 'AU', 'VN', 93 ), // APAC destination with boost (90+3).
			array( '3555666777888999', 'AU', 'US', 92 ), // Common destination with boost (90+2).
		);
	}

	/**
	 * Data provider for invalid tracking number parsing tests.
	 *
	 * @return array[]
	 */
	public function invalidTrackingNumberProvider(): array {
		return array(
			// Wrong origin country (not Australia).
			array( 'AB123456789AU', 'US', 'AU' ),       // From US instead of AU.
			array( '1234567890123', 'NZ', 'AU' ),       // From NZ instead of AU.
			array( '123456789012', 'GB', 'US' ),        // From GB instead of AU.

			// Too short.
			array( '12345', 'AU', 'US' ),               // Too short.
			array( 'AB123AU', 'AU', 'NZ' ),             // Too short for international format.
			array( 'A12345B', 'AU', 'SG' ),             // Too short for domestic format.

			// Too long.
			array( '12345678901234567890', 'AU', 'US' ), // Too long.
			array( 'AB123456789012345AU', 'AU', 'NZ' ), // Too long for international format.
			array( 'ABCDE123456789012345', 'AU', 'SG' ), // Too long for express post.

			// Invalid format.
			array( '123456789AB', 'AU', 'US' ),         // Mixed format invalid.
			array( 'ABCDEFGHIJK', 'AU', 'NZ' ),         // All letters invalid length.
			array( 'AB12345AU67', 'AU', 'SG' ),         // Invalid pattern.

			// Empty or whitespace only.
			array( '', 'AU', 'US' ),                    // Empty string.
			array( '   ', 'AU', 'NZ' ),                 // Whitespace only.

			// Invalid characters.
			array( '12-34-56-78-90-12', 'AU', 'US' ),   // Dashes (invalid format).
			array( '123.456.789.012', 'AU', 'NZ' ),     // Dots (invalid format).
			array( 'AB123456@89AU', 'AU', 'SG' ),       // Special characters.

			// Invalid starting digits for 16-digit format.
			array( '1123456789012345', 'AU', 'US' ),    // Starts with 1 (not 3 or 7).
			array( '5987654321098765', 'AU', 'NZ' ),    // Starts with 5 (not 3 or 7).
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
		$this->assertGreaterThanOrEqual( 90, $result['ambiguity_score'], 'Score should be at least 90' );
		$this->assertLessThanOrEqual( 95, $result['ambiguity_score'], 'Score should not exceed 95' );

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
			array( 'AB 123 456 789 AU', 'AU', 'US' ),
			array( '  AB123456789AU  ', 'AU', 'NZ' ),
			array( '1234 5678 9012 3', 'AU', 'SG' ),

			// Mixed case.
			array( 'ab123456789au', 'AU', 'US' ),
			array( 'Ab123456789Au', 'AU', 'NZ' ),
			array( 'AB123456789AU', 'AU', 'SG' ),
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
		$result = $this->provider->try_parse_tracking_number( '', 'AU', 'US' );
		$this->assertNull( $result );

		// Empty origin country.
		$result = $this->provider->try_parse_tracking_number( 'AB123456789AU', '', 'US' );
		$this->assertNull( $result );

		// Empty destination country.
		$result = $this->provider->try_parse_tracking_number( 'AB123456789AU', 'AU', '' );
		$this->assertNull( $result );

		// All empty.
		$result = $this->provider->try_parse_tracking_number( '', '', '' );
		$this->assertNull( $result );
	}

	/**
	 * Test confidence scoring hierarchy.
	 */
	public function test_confidence_scoring(): void {
		// Domestic shipment should get highest confidence boost.
		$result_domestic = $this->provider->try_parse_tracking_number( 'AB123456789AU', 'AU', 'AU' );
		$this->assertIsArray( $result_domestic );
		$this->assertSame( 95, $result_domestic['ambiguity_score'] );

		// APAC destination should get medium-high boost.
		$result_apac = $this->provider->try_parse_tracking_number( 'AB123456789AU', 'AU', 'NZ' );
		$this->assertIsArray( $result_apac );
		$this->assertSame( 93, $result_apac['ambiguity_score'] );

		// Common destination should get medium boost.
		$result_common = $this->provider->try_parse_tracking_number( 'AB123456789AU', 'AU', 'US' );
		$this->assertIsArray( $result_common );
		$this->assertSame( 92, $result_common['ambiguity_score'] );

		// International should get base confidence.
		$result_international = $this->provider->try_parse_tracking_number( 'AB123456789AU', 'AU', 'BR' );
		$this->assertIsArray( $result_international );
		$this->assertSame( 90, $result_international['ambiguity_score'] );

		// Verify scoring hierarchy.
		$this->assertGreaterThan( $result_apac['ambiguity_score'], $result_domestic['ambiguity_score'] );
		$this->assertGreaterThan( $result_common['ambiguity_score'], $result_apac['ambiguity_score'] );
		$this->assertGreaterThan( $result_international['ambiguity_score'], $result_common['ambiguity_score'] );
	}

	/**
	 * Test specific pattern formats.
	 */
	public function test_pattern_formats(): void {
		// Test international UPU S10 format XX#########AU.
		$upu_result = $this->provider->try_parse_tracking_number( 'AB123456789AU', 'AU', 'US' );
		$this->assertIsArray( $upu_result );
		$this->assertSame( 92, $upu_result['ambiguity_score'] );

		// Test alternative international format XX#######AU.
		$alt_intl_result = $this->provider->try_parse_tracking_number( 'AB1234567AU', 'AU', 'US' );
		$this->assertIsArray( $alt_intl_result );
		$this->assertSame( 92, $alt_intl_result['ambiguity_score'] );

		// Test 13-digit tracking.
		$digit13_result = $this->provider->try_parse_tracking_number( '1234567890123', 'AU', 'US' );
		$this->assertIsArray( $digit13_result );
		$this->assertSame( 92, $digit13_result['ambiguity_score'] );

		// Test 12-digit tracking.
		$digit12_result = $this->provider->try_parse_tracking_number( '123456789012', 'AU', 'US' );
		$this->assertIsArray( $digit12_result );
		$this->assertSame( 92, $digit12_result['ambiguity_score'] );

		// Test 11-digit tracking.
		$digit11_result = $this->provider->try_parse_tracking_number( '12345678901', 'AU', 'US' );
		$this->assertIsArray( $digit11_result );
		$this->assertSame( 92, $digit11_result['ambiguity_score'] );

		// Test standard format XX########XX.
		$standard_result = $this->provider->try_parse_tracking_number( 'AB12345678CD', 'AU', 'US' );
		$this->assertIsArray( $standard_result );
		$this->assertSame( 92, $standard_result['ambiguity_score'] );

		// Test domestic format X##########X.
		$domestic_result = $this->provider->try_parse_tracking_number( 'A1234567890B', 'AU', 'US' );
		$this->assertIsArray( $domestic_result );
		$this->assertSame( 92, $domestic_result['ambiguity_score'] );

		// Test Express Post format XXXX########.
		$express_result = $this->provider->try_parse_tracking_number( 'ABCD12345678', 'AU', 'US' );
		$this->assertIsArray( $express_result );
		$this->assertSame( 92, $express_result['ambiguity_score'] );

		// Test 16-digit format starting with 7.
		$digit16_7_result = $this->provider->try_parse_tracking_number( '7123456789012345', 'AU', 'US' );
		$this->assertIsArray( $digit16_7_result );
		$this->assertSame( 92, $digit16_7_result['ambiguity_score'] );

		// Test 16-digit format starting with 3.
		$digit16_3_result = $this->provider->try_parse_tracking_number( '3123456789012345', 'AU', 'US' );
		$this->assertIsArray( $digit16_3_result );
		$this->assertSame( 92, $digit16_3_result['ambiguity_score'] );
	}

	/**
	 * Test non-Australia origin rejection.
	 */
	public function test_non_australia_origin_rejection(): void {
		$non_au_origins = array( 'US', 'NZ', 'GB', 'SG', 'CA', 'JP' );

		foreach ( $non_au_origins as $origin ) {
			$result = $this->provider->try_parse_tracking_number( 'AB123456789AU', $origin, 'AU' );
			$this->assertNull( $result, "Should reject tracking number from non-Australia origin: {$origin}" );
		}
	}

	/**
	 * Test APAC destination boost.
	 */
	public function test_apac_destination_boost(): void {
		$apac_destinations = array( 'NZ', 'SG', 'HK', 'JP', 'KR', 'TH', 'MY', 'ID', 'PH', 'VN', 'IN' );

		foreach ( $apac_destinations as $destination ) {
			$result = $this->provider->try_parse_tracking_number( 'AB123456789AU', 'AU', $destination );
			$this->assertIsArray( $result );
			$this->assertSame( 93, $result['ambiguity_score'], "APAC destination {$destination} should get confidence boost" );
		}
	}

	/**
	 * Test common destination boost.
	 */
	public function test_common_destination_boost(): void {
		$common_destinations = array( 'US', 'GB', 'CA', 'DE', 'FR' );

		foreach ( $common_destinations as $destination ) {
			$result = $this->provider->try_parse_tracking_number( 'AB123456789AU', 'AU', $destination );
			$this->assertIsArray( $result );
			$this->assertSame( 92, $result['ambiguity_score'], "Common destination {$destination} should get confidence boost" );
		}

		// Test non-common, non-APAC destination doesn't get boost.
		$result_other = $this->provider->try_parse_tracking_number( 'AB123456789AU', 'AU', 'BR' );
		$this->assertIsArray( $result_other );
		$this->assertSame( 90, $result_other['ambiguity_score'] );
	}
}
