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
			// German tracking numbers (medium confidence).
			array( '12345678901234', 'DE', 'FR', 80 ), // 14 digits from Germany with boost (75+5).
			array( '123456789012', 'DE', 'NL', 80 ),   // 12 digits from Germany with boost (75+5).

			// UK tracking numbers (medium-high confidence).
			array( '12345678901234', 'GB', 'DE', 90 ),   // 14 digits UK format with boost (85+5).
			array( 'AB123456789GB', 'GB', 'FR', 90 ),    // Country suffix format UK with boost (85+5).

			// French tracking numbers (medium confidence).
			array( '12345678901234', 'FR', 'DE', 80 ), // 14 digits from France with boost (75+5).
			array( '123456789012', 'FR', 'GB', 80 ),   // 12 digits from France with boost (75+5).

			// Netherlands tracking numbers (medium confidence).
			array( '12345678901234', 'NL', 'DE', 80 ), // 14 digits from Netherlands with boost (75+5).
			array( '123456789012', 'NL', 'BE', 80 ),   // 12 digits from Netherlands with boost (75+5).

			// Belgian tracking numbers (medium confidence).
			array( '12345678901234', 'BE', 'NL', 80 ), // 14 digits from Belgium with boost (75+5).
			array( '123456789012', 'BE', 'FR', 80 ),   // 12 digits from Belgium with boost (75+5).

			// Polish tracking numbers (medium-high confidence).
			array( '12345678901234', 'PL', 'DE', 95 ), // 14 digits from Poland with boost (90+5).
			array( 'PL1234567890', 'PL', 'DE', 95 ),   // Country code format from Poland with boost.

			// New countries - Baltic states (medium confidence).
			array( '12345678901234', 'LT', 'DE', 75 ), // 14 digits from Lithuania with boost (70+5).
			array( '123456789012', 'LV', 'PL', 75 ),   // 12 digits from Latvia with boost (70+5).
			array( '12345678901234', 'EE', 'FI', 75 ), // 14 digits from Estonia with boost (70+5).

			// Nordic countries (medium-low confidence).
			array( '12345678901234', 'FI', 'SE', 70 ), // 14 digits from Finland with boost (65+5).
			array( '123456789012', 'DK', 'NO', 70 ),   // 12 digits from Denmark with boost (65+5).
			array( '12345678901234', 'SE', 'DK', 70 ), // 14 digits from Sweden with boost (65+5).
			array( '123456789012', 'NO', 'SE', 65 ),   // 12 digits from Norway with boost (60+5).

			// Southern Europe (medium confidence).
			array( '12345678901234', 'GR', 'IT', 85 ), // 14 digits from Greece.
			array( 'GR1234567890', 'GR', 'BG', 85 ),   // Country code format from Greece.
			array( '12345678901234', 'PT', 'ES', 85 ), // 14 digits from Portugal.
			array( 'PT1234567890', 'PT', 'FR', 85 ),   // Country code format from Portugal.

			// Irish tracking numbers (medium confidence).
			array( '12345678901234', 'IE', 'GB', 85 ), // 14 digits from Ireland.
			array( 'IE123456789IE', 'IE', 'GB', 85 ),   // Country code format from Ireland.

			// Austrian tracking numbers (medium confidence).
			array( '12345678901234', 'AT', 'DE', 80 ), // 14 digits from Austria with boost (75+5).
			array( '123456789012', 'AT', 'CH', 80 ),   // 12 digits from Austria with boost (75+5).

			// Swiss tracking numbers (medium confidence).
			array( '12345678901234', 'CH', 'DE', 85 ), // 14 digits from Switzerland.
			array( 'CH123456789CH', 'CH', 'AT', 85 ),   // Country code format from Switzerland.

			// Spanish tracking numbers (medium confidence).
			array( '12345678901234', 'ES', 'FR', 85 ),   // 14 digits from Spain.
			array( 'ES1234567890', 'ES', 'PT', 85 ),   // Country code format from Spain.

			// Italian tracking numbers (medium confidence).
			array( '12345678901234', 'IT', 'FR', 85 ),   // 14 digits from Italy.
			array( 'IT1234567890', 'IT', 'CH', 85 ),   // Country code format from Italy.

			// Cross-border with destination boost (both DPD countries).
			array( '12345678901234', 'DE', 'FR', 80 ), // DE to FR, confidence boosted (75+5).
			array( '12345678901234', 'GB', 'DE', 90 ),   // GB to DE, confidence boosted (85+5).
		);
	}

	/**
	 * Data provider for invalid tracking number parsing tests.
	 *
	 * @return array[]
	 */
	public function invalidTrackingNumberProvider(): array {
		return array(
			// Too short.
			array( '123456789', 'DE', 'FR' ),      // 9 digits (too short).
			array( '12345', 'GB', 'DE' ),          // 5 digits (too short).

			// Too long (non-extended format).
			array( '123456789012345', 'DE', 'FR' ), // 15 digits (invalid length).
			array( '12345678901234567890', 'GB', 'DE' ), // 20 digits (not extended format).

			// Invalid characters in wrong positions.
			array( 'ABCD123456789012', 'DE', 'FR' ), // Too many letters for German format.
			array( '12345A67890123', 'FR', 'DE' ),   // Letter in middle (invalid for French).

			// Empty or whitespace only.
			array( '', 'DE', 'FR' ),               // Empty string.
			array( '   ', 'GB', 'DE' ),            // Whitespace only.

			// Invalid format combinations.
			array( 'ABC123', 'DE', 'FR' ),         // Mixed format too short.
			array( '12-34-56-78-90-12', 'GB', 'DE' ), // Dashes (invalid format).

			// Invalid extended formats.
			array( '12345678901234567890123456', 'DE', 'FR' ), // 26 digits (not 28).
			array( 'ABCD1234567890123456789012', 'GB', 'DE' ), // 24 alphanumeric (not valid DPD format).
		);
	}

	/**
	 * Data provider for extended format tracking numbers.
	 *
	 * @return array[]
	 */
	public function extendedTrackingNumberProvider(): array {
		return array(
			// Extended format without spaces (28 digits).
			array( '1234567890123456789012345678', 'GB', 'DE', 95 ),
		);
	}

	/**
	 * Data provider for ambiguous tracking numbers (multiple country matches).
	 *
	 * @return array[]
	 */
	public function ambiguousTrackingNumberProvider(): array {
		return array(
			// Numbers that could match multiple countries but origin not in supported list.
			array( '12345678901234', 'US', 'DE' ), // US not supported, should reduce confidence.
			array( '123456789012', 'CA', 'FR' ),   // CA not supported, should reduce confidence.

			// Valid format but from unsupported origin.
			array( '12345678901234', 'JP', 'GB' ), // JP not supported.
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
	 * @param int    $expected_min_score Minimum expected ambiguity score.
	 */
	public function test_try_parse_tracking_number_valid( string $tracking_number, string $from, string $to, int $expected_min_score ): void {
		$result = $this->provider->try_parse_tracking_number( $tracking_number, $from, $to );

		$this->assertIsArray( $result, "Should return array for valid tracking number: {$tracking_number}" );
		$this->assertArrayHasKey( 'url', $result );
		$this->assertArrayHasKey( 'ambiguity_score', $result );

		// Check score is at least the expected minimum.
		$this->assertGreaterThanOrEqual(
			$expected_min_score,
			$result['ambiguity_score'],
			"Score should be at least {$expected_min_score} for {$tracking_number} from {$from} to {$to}"
		);

		// Check score is within valid range.
		$this->assertGreaterThanOrEqual( 40, $result['ambiguity_score'], 'Score should be at least 40' );
		$this->assertLessThanOrEqual( 105, $result['ambiguity_score'], 'Score should not exceed 105' );

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

		if ( null !== $result ) {
			$this->assertIsArray( $result );
			$this->assertArrayHasKey( 'ambiguity_score', $result );
			// Should have reduced confidence due to unsupported origin country.
			$this->assertLessThan( 85, $result['ambiguity_score'], 'Should have reduced confidence for unsupported origin' );
			$this->assertGreaterThanOrEqual( 40, $result['ambiguity_score'], 'Should still be above minimum threshold' );
		} else {
			$this->assertNull( $result, "Should return null for ambiguous tracking number: {$tracking_number}" );
		}
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
		$result_medium = $this->provider->try_parse_tracking_number( '12345678901234', 'BG', 'US' );

		$this->assertIsArray( $result_high );
		$this->assertIsArray( $result_medium );

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
		$this->assertSame( 75, $result_no_boost['ambiguity_score'] );
		$this->assertGreaterThan( $result_no_boost['ambiguity_score'], $result_boost['ambiguity_score'] );

		// Destination boost should give score of 80 for DE origin (75+5).
		$this->assertSame( 80, $result_boost['ambiguity_score'] );
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
		$this->assertSame( 90, $uk_digits['ambiguity_score'] ); // 85+5=90
		$this->assertSame( 90, $uk_prefix['ambiguity_score'] ); // 85+5=90

		// Test German patterns.
		$de_14_digits = $this->provider->try_parse_tracking_number( '12345678901234', 'DE', 'FR' );
		$this->assertIsArray( $de_14_digits );
		$this->assertSame( 80, $de_14_digits['ambiguity_score'] ); // DE with FR destination gets boost (75+5).
	}
}
