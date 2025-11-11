<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\DPDShippingProvider;

/**
 * Unit tests for DPDShippingProvider class.
 *
 * @package WooCommerce\Tests\Internal\Fulfillments\Providers
 */
class DPDShippingProviderTest extends \WP_UnitTestCase {
	/**
	 * Instance of DPDShippingProvider used in tests.
	 *
	 * @var DPDShippingProvider
	 */
	private DPDShippingProvider $provider;

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->provider = new DPDShippingProvider();
	}

	/**
	 * Test the get_key method.
	 */
	public function test_get_key(): void {
		$this->assertSame( 'dpd', $this->provider->get_key() );
	}

	/**
	 * Test the get_name method.
	 */
	public function test_get_name(): void {
		$this->assertSame( 'DPD', $this->provider->get_name() );
	}

	/**
	 * Test the get_tracking_url method.
	 */
	public function test_get_tracking_url(): void {
		$tracking_number = '12345678901234';
		$expected_url    = 'https://www.dpd.com/tracking/' . $tracking_number;
		$this->assertSame( $expected_url, $this->provider->get_tracking_url( $tracking_number ) );
	}

	/**
	 * Test get_shipping_from_countries returns expected countries.
	 */
	public function test_get_shipping_from_countries(): void {
		$countries = $this->provider->get_shipping_from_countries();

		// Test that core DPD countries are included.
		$expected_core_countries = array( 'DE', 'GB', 'FR', 'NL', 'BE', 'PL', 'IE', 'LT', 'LV', 'EE', 'FI', 'DK', 'SE', 'NO', 'GR', 'PT' );
		foreach ( $expected_core_countries as $country ) {
			$this->assertContains( $country, $countries, "Country {$country} should be in shipping from countries" );
		}

		// Test that we have the expected number of countries.
		$this->assertGreaterThanOrEqual( 28, count( $countries ), 'Should have at least 28 countries' );
	}

	/**
	 * Test get_shipping_to_countries matches from countries.
	 */
	public function test_get_shipping_to_countries(): void {
		$from_countries = $this->provider->get_shipping_from_countries();
		$to_countries   = $this->provider->get_shipping_to_countries();

		$this->assertSame( $from_countries, $to_countries );
	}

	/**
	 * Data provider for valid tracking number parsing tests.
	 *
	 * @return array[]
	 */
	public function validTrackingNumberProvider(): array {
		return array(
			// German tracking numbers (base confidence 80).
			array( '12345678901234', 'DE', 'FR', 83 ), // 14 digits DE->FR, base 80 + intra-DPD boost 3.
			array( '123456789012', 'DE', 'NL', 83 ),   // 12 digits DE->NL, base 80 + intra-DPD boost 3.
			array( '02123456789012', 'DE', 'US', 80 ),  // Classic service, base confidence 80.

			// UK tracking numbers (base confidence 90).
			array( '12345678901234', 'GB', 'DE', 93 ),   // 14 digits GB->DE, base 90 + intra-DPD boost 3.
			array( 'AB123456789GB', 'GB', 'FR', 90 ),    // S10 service, confidence 90.
			array( '03123456789012', 'GB', 'US', 90 ),   // Next day service (88) + express boost (2) = 90.

			// French tracking numbers (base confidence 78).
			array( '12345678901234', 'FR', 'DE', 81 ), // 14 digits FR->DE, base 78 + intra-DPD boost 3.
			array( '123456789012', 'FR', 'GB', 81 ),   // 12 digits FR->GB, base 78 + intra-DPD boost 3.
			array( '02123456789012', 'FR', 'US', 78 ),  // Base confidence 78 (relais pattern doesn't match).

			// Netherlands tracking numbers (base confidence 78).
			array( '12345678901234', 'NL', 'DE', 81 ), // 14 digits NL->DE, base 78 + intra-DPD boost 3.
			array( '123456789012', 'NL', 'BE', 81 ),   // 12 digits NL->BE, base 78 + intra-DPD boost 3.
			array( '03123456789012', 'NL', 'US', 87 ),  // Classic service (82) + express boost (2) + express boost (2) + intra-DPD boost (1) = 87.

			// Belgian tracking numbers (base confidence 78).
			array( '12345678901234', 'BE', 'NL', 81 ), // 14 digits BE->NL, base 78 + intra-DPD boost 3.
			array( '123456789012', 'BE', 'FR', 81 ),   // 12 digits BE->FR, base 78 + intra-DPD boost 3.
			array( '03123456789012', 'BE', 'US', 87 ),  // Classic service (82) + express boost (2) + express boost (2) + intra-DPD boost (1) = 87.

			// Polish tracking numbers (base confidence 90).
			array( '12345678901234', 'PL', 'DE', 93 ), // 14 digits PL->DE, base 90 + intra-DPD boost 3.
			array( 'PL1234567890', 'PL', 'DE', 93 ),   // Country code format PL->DE, base 90 + intra-DPD boost 3.

			// International 28-digit format (base confidence 95).
			array( '1234567890123456789012345678', 'DE', 'FR', 95 ),
			array( '1234567890123456789012345678', 'GB', 'NL', 95 ),

			// S10/UPU format (confidence 90).
			array( 'AB123456789DE', 'DE', 'FR', 90 ),
			array( 'CD987654321GB', 'GB', 'NL', 90 ),

			// Service-specific patterns with confidence boosts.
			array( '05123456789012', 'DE', 'US', 87 ),  // Express service (85) + express boost (2) = 87.
			array( '09123456789012', 'DE', 'US', 85 ),  // Predict service, confidence 85.
			array( '06123456789012', 'GB', 'US', 90 ),  // Express service, base confidence 90.
			array( '15123456789012', 'GB', 'US', 90 ),  // Predict/Return service, base confidence 90.

			// Fallback patterns (12-24 digits get 60 confidence).
			array( '123456789012', 'US', 'DE', 60 ),     // 12 digits fallback.
			array( '123456789012345', 'US', 'GB', 60 ),  // 15 digits fallback.
			array( '123456789012345678', 'US', 'FR', 60 ), // 18 digits fallback.
		);
	}

	/**
	 * Data provider for invalid tracking number parsing tests.
	 *
	 * @return array[]
	 */
	public function invalidTrackingNumberProvider(): array {
		return array(
			// Too short for any pattern.
			array( '123456789', 'DE', 'FR' ),      // 9 digits (too short).
			array( '12345', 'GB', 'DE' ),          // 5 digits (too short).

			// Invalid lengths (not matching any pattern).
			array( '12345678901234567890123456', 'DE', 'FR' ), // 26 digits (not 28).
			array( '12345678901234567890123456789', 'GB', 'DE' ), // 29 digits (too long).

			// Invalid S10/UPU format.
			array( 'ABC123456789DE', 'DE', 'FR' ), // Too many letters at start.
			array( 'AB12345678DEF', 'FR', 'DE' ),   // Too many letters at end.

			// Empty or whitespace only.
			array( '', 'DE', 'FR' ),               // Empty string.
			array( '   ', 'GB', 'DE' ),            // Whitespace only.

			// Invalid format combinations.
			array( 'ABC123', 'DE', 'FR' ),         // Mixed format too short.
			array( '12-34-56-78-90-12', 'GB', 'DE' ), // Dashes (invalid format).

			// Numbers below 12 digits (too short for fallback).
			array( '12345678901', 'DE', 'FR' ),    // 11 digits (too short).
			array( '1234567890', 'GB', 'DE' ),     // 10 digits (too short).
		);
	}

	/**
	 * Data provider for extended format tracking numbers.
	 *
	 * @return array[]
	 */
	public function extendedTrackingNumberProvider(): array {
		return array(
			// International 28-digit format (confidence 95).
			array( '1234567890123456789012345678', 'GB', 'DE', 95 ),
			array( '9876543210987654321098765432', 'DE', 'FR', 95 ),
		);
	}

	/**
	 * Data provider for ambiguous tracking numbers (multiple country matches).
	 *
	 * @return array[]
	 */
	public function ambiguousTrackingNumberProvider(): array {
		return array(
			// Numbers that could match fallback pattern from non-DPD countries.
			array( '12345678901234', 'US', 'DE' ), // US not in DPD countries, gets fallback 60.
			array( '123456789012', 'CA', 'FR' ),   // CA not in DPD countries, gets fallback 60.

			// Valid format but from unsupported origin.
			array( '12345678901234', 'JP', 'GB' ), // JP not in DPD countries, gets fallback 60.
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
	 * @param int    $expected_score Expected ambiguity score.
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
		$this->assertGreaterThanOrEqual( 60, $result['ambiguity_score'], 'Score should be at least 60' );
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
	 * Test try_parse_tracking_number method with extended format tracking numbers.
	 *
	 * @dataProvider extendedTrackingNumberProvider
	 *
	 * @param string $tracking_number The tracking number to test.
	 * @param string $from            Origin country.
	 * @param string $to              Destination country.
	 * @param int    $expected_score  Expected ambiguity score.
	 */
	public function test_try_parse_tracking_number_extended( string $tracking_number, string $from, string $to, int $expected_score ): void {
		$result = $this->provider->try_parse_tracking_number( $tracking_number, $from, $to );

		$this->assertIsArray( $result, "Should return array for extended tracking number: {$tracking_number}" );
		$this->assertArrayHasKey( 'url', $result );
		$this->assertArrayHasKey( 'ambiguity_score', $result );
		$this->assertSame( $expected_score, $result['ambiguity_score'] );
	}

	/**
	 * Test try_parse_tracking_number method with ambiguous cases.
	 *
	 * @dataProvider ambiguousTrackingNumberProvider
	 *
	 * @param string $tracking_number The tracking number to test.
	 * @param string $from            Origin country.
	 * @param string $to              Destination country.
	 */
	public function test_try_parse_tracking_number_ambiguous( string $tracking_number, string $from, string $to ): void {
		$result = $this->provider->try_parse_tracking_number( $tracking_number, $from, $to );

		$this->assertIsArray( $result, "Should return array for fallback pattern: {$tracking_number}" );
		$this->assertArrayHasKey( 'ambiguity_score', $result );
		// Should get fallback confidence of 60.
		$this->assertSame( 60, $result['ambiguity_score'], 'Should get fallback confidence of 60' );
	}

	/**
	 * Test tracking number normalization (spaces, case sensitivity).
	 */
	public function test_tracking_number_normalization(): void {
		$test_cases = array(
			// With spaces.
			array( '1234 5678 9012 34', 'DE', 'FR' ),
			array( '  1234 5678 9012 34  ', 'DE', 'FR' ),

			// Mixed case (for alphanumeric formats).
			array( 'abcdefghijkl', 'GB', 'DE' ),
			array( 'Abcdefghijkl', 'GB', 'DE' ),
			array( 'ABCDEFGHIJKL', 'GB', 'DE' ),
		);

		foreach ( $test_cases as $test_case ) {
			list( $tracking_number, $from, $to ) = $test_case;
			$result                              = $this->provider->try_parse_tracking_number( $tracking_number, $from, $to );

			if ( null !== $result ) {
				$this->assertIsArray( $result );
				$this->assertArrayHasKey( 'url', $result );

				// URL should contain normalized version (no spaces, uppercase).
				$normalized = strtoupper( preg_replace( '/\s+/', '', $tracking_number ) );
				$this->assertStringContainsString( $normalized, $result['url'] );
			}
		}
	}

	/**
	 * Test empty parameter handling.
	 */
	public function test_empty_parameters(): void {
		// Empty tracking number.
		$result = $this->provider->try_parse_tracking_number( '', 'DE', 'FR' );
		$this->assertNull( $result );

		// Empty origin country.
		$result = $this->provider->try_parse_tracking_number( '12345678901234', '', 'FR' );
		$this->assertNull( $result );

		// Empty destination country.
		$result = $this->provider->try_parse_tracking_number( '12345678901234', 'DE', '' );
		$this->assertNull( $result );

		// All empty.
		$result = $this->provider->try_parse_tracking_number( '', '', '' );
		$this->assertNull( $result );
	}

	/**
	 * Test confidence scoring consistency.
	 */
	public function test_confidence_scoring_consistency(): void {
		// Same tracking number from higher-confidence country should have higher score.
		$result_high   = $this->provider->try_parse_tracking_number( '12345678901234', 'GB', 'US' );
		$result_medium = $this->provider->try_parse_tracking_number( '12345678901234', 'US', 'GB' );

		$this->assertIsArray( $result_high );
		$this->assertIsArray( $result_medium );

		// GB has base confidence 90, US gets fallback 60.
		$this->assertSame( 90, $result_high['ambiguity_score'] );
		$this->assertSame( 60, $result_medium['ambiguity_score'] );
		$this->assertGreaterThan(
			$result_medium['ambiguity_score'],
			$result_high['ambiguity_score'],
			'Higher-confidence country should have higher score than lower-confidence country'
		);
	}

	/**
	 * Test destination boost scoring.
	 */
	public function test_destination_boost_scoring(): void {
		// Cross-border DPD shipping should get confidence boost.
		$result_boost    = $this->provider->try_parse_tracking_number( '12345678901234', 'DE', 'FR' );
		$result_no_boost = $this->provider->try_parse_tracking_number( '12345678901234', 'DE', 'US' );

		$this->assertIsArray( $result_boost );
		$this->assertIsArray( $result_no_boost );
		$this->assertSame( 80, $result_no_boost['ambiguity_score'] ); // DE base confidence.
		$this->assertGreaterThan( $result_no_boost['ambiguity_score'], $result_boost['ambiguity_score'] );

		// Intra-DPD boost should give score of 83 for DE origin (80+3).
		$this->assertSame( 83, $result_boost['ambiguity_score'] );
	}

	/**
	 * Test extended pattern validation.
	 */
	public function test_extended_pattern_validation(): void {
		// Extended patterns should get high confidence regardless of origin/destination.
		$digits_result = $this->provider->try_parse_tracking_number( '1234567890123456789012345678', 'GB', 'DE' );

		$this->assertIsArray( $digits_result );

		// Should have score of 95.
		$this->assertSame( 95, $digits_result['ambiguity_score'] );
	}

	/**
	 * Test specific pattern formats for different countries.
	 */
	public function test_country_specific_patterns(): void {
		// Test UK-specific patterns.
		$uk_digits = $this->provider->try_parse_tracking_number( '12345678901234', 'GB', 'DE' );
		$uk_prefix = $this->provider->try_parse_tracking_number( 'AB123456789GB', 'GB', 'FR' );

		$this->assertIsArray( $uk_digits );
		$this->assertIsArray( $uk_prefix );
		$this->assertSame( 93, $uk_digits['ambiguity_score'] ); // 90+3=93 (intra-DPD boost)
		$this->assertSame( 90, $uk_prefix['ambiguity_score'] ); // S10 service, confidence 90.

		// Test German patterns.
		$de_14_digits = $this->provider->try_parse_tracking_number( '12345678901234', 'DE', 'FR' );
		$this->assertIsArray( $de_14_digits );
		$this->assertSame( 83, $de_14_digits['ambiguity_score'] ); // DE with FR destination gets boost (80+3).
	}
}
