<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\EvriHermesShippingProvider;

/**
 * Unit tests for EvriHermesShippingProvider class.
 *
 * @package WooCommerce\Tests\Internal\Fulfillments\Providers
 */
class EvriHermesShippingProviderTest extends \WP_UnitTestCase {
	/**
	 * Instance of EvriHermesShippingProvider used in tests.
	 *
	 * @var EvriHermesShippingProvider
	 */
	private EvriHermesShippingProvider $provider;

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->provider = new EvriHermesShippingProvider();
	}

	/**
	 * Test the get_key method.
	 */
	public function test_get_key(): void {
		$this->assertSame( 'evri-hermes', $this->provider->get_key() );
	}

	/**
	 * Test the get_name method.
	 */
	public function test_get_name(): void {
		$this->assertSame( 'Evri (Hermes)', $this->provider->get_name() );
	}

	/**
	 * Test the get_tracking_url method.
	 */
	public function test_get_tracking_url(): void {
		$tracking_number = 'H12345678';
		$expected_url    = 'https://www.evri.com/track/' . $tracking_number;
		$this->assertSame( $expected_url, $this->provider->get_tracking_url( $tracking_number ) );
	}

	/**
	 * Test get_shipping_from_countries returns expected countries.
	 */
	public function test_get_shipping_from_countries(): void {
		$countries = $this->provider->get_shipping_from_countries();

		// Test that core Evri countries are included.
		$expected_core_countries = array( 'GB', 'IE', 'FR', 'DE', 'IT', 'ES', 'NL', 'BE', 'AT', 'PL', 'GR', 'PT', 'CH' );
		foreach ( $expected_core_countries as $country ) {
			$this->assertContains( $country, $countries, "Country {$country} should be in shipping from countries" );
		}

		// Test that we have the expected number of countries.
		$this->assertGreaterThanOrEqual( 22, count( $countries ), 'Should have at least 22 countries' );
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
			// UK tracking numbers (highest confidence - primary market).
			array( 'HH12345678GB', 'GB', 'IE', 98 ),    // Full format with boost (90+5+3).
			array( 'H12345678', 'GB', 'FR', 98 ),       // H prefix format with boost (90+5+3).
			array( 'E98765432', 'GB', 'DE', 98 ),       // E prefix format with boost (90+5+3).
			array( '1234567890123456', 'GB', 'NL', 98 ), // 16-digit numeric with boost (90+5+3).
			array( '123456789012345', 'GB', 'BE', 98 ),  // 15-digit numeric with boost (90+5+3).

			// UK tracking numbers to non-Evri countries (no destination boost but UK boost).
			array( 'H12345678', 'GB', 'US', 93 ),       // H prefix format with UK boost only (90+3).
			array( '1234567890123456', 'GB', 'CA', 93 ), // 16-digit with UK boost only (90+3).

			// Ireland tracking numbers (high confidence).
			array( 'AB12345678IE', 'IE', 'FR', 90 ),    // IE suffix format with boost (85+5).
			array( '1234567890123456', 'IE', 'FR', 90 ), // 16-digit with boost (85+5).
			array( '123456789012345', 'IE', 'DE', 90 ),  // 15-digit with boost (85+5).

			// Ireland tracking numbers to non-Evri countries.
			array( 'AB12345678IE', 'IE', 'US', 85 ),    // IE format without destination boost.
			array( '1234567890123456', 'IE', 'JP', 85 ), // 16-digit without destination boost.

			// Standard pattern countries - major markets (80% confidence).
			array( '1234567890123456', 'FR', 'DE', 85 ), // France 16-digit with boost (80+5).
			array( '123456789012345', 'DE', 'FR', 85 ),  // Germany 15-digit with boost (80+5).
			array( 'AB12345678IT', 'IT', 'CH', 85 ),     // Italy country format with boost (80+5).
			array( 'AB12345678ES', 'ES', 'PT', 85 ),     // Spain country format with boost (80+5).
			array( '1234567890123456', 'NL', 'BE', 85 ), // Netherlands 16-digit with boost (80+5).
			array( '123456789012345', 'BE', 'NL', 85 ),  // Belgium 15-digit with boost (80+5).
			array( 'AB12345678AT', 'AT', 'DE', 85 ),     // Austria country format with boost (80+5).
			array( '1234567890123456', 'PL', 'CZ', 85 ), // Poland 16-digit with boost (80+5).
			array( 'AB12345678GR', 'GR', 'IT', 85 ),     // Greece country format with boost (80+5).
			array( '123456789012345', 'PT', 'ES', 85 ),  // Portugal 15-digit with boost (80+5).
			array( 'AB12345678CH', 'CH', 'AT', 85 ),     // Switzerland country format with boost (80+5).

			// Standard pattern countries to non-Evri destinations.
			array( '1234567890123456', 'FR', 'US', 80 ), // France without destination boost.
			array( 'AB12345678DE', 'DE', 'JP', 80 ),     // Germany without destination boost.

			// Central/Eastern Europe (75% confidence).
			array( '1234567890123456', 'CZ', 'DE', 80 ), // Czech Republic with boost (75+5).
			array( 'AB12345678HU', 'HU', 'AT', 80 ),     // Hungary country format with boost (75+5).
			array( '123456789012345', 'RO', 'IT', 80 ),  // Romania with boost (75+5).
			array( '1234567890123456', 'NO', 'SE', 80 ), // Norway with boost (75+5).
			array( 'AB12345678SE', 'SE', 'DK', 80 ),     // Sweden country format with boost (75+5).
			array( '123456789012345', 'DK', 'FI', 80 ),  // Denmark with boost (75+5).
			array( 'AB12345678FI', 'FI', 'EE', 80 ),     // Finland country format with boost (75+5).

			// Lower confidence countries (70% confidence).
			array( '1234567890123456', 'EE', 'FI', 75 ), // Estonia with boost (70+5).
			array( 'AB12345678CY', 'CY', 'GR', 75 ),     // Cyprus country format with boost (70+5).

			// Cross-border within Evri network (destination boost).
			array( '1234567890123456', 'FR', 'DE', 85 ), // France to Germany with boost (80+5).
			array( '123456789012345', 'DE', 'AT', 85 ),  // Germany to Austria with boost (80+5).
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
			array( '12345678', 'GB', 'IE' ),       // 8 digits (too short).
			array( 'H1234', 'GB', 'FR' ),          // H prefix too short.
			array( 'E123', 'GB', 'DE' ),           // E prefix too short.

			// Too long for standard patterns.
			array( '12345678901234567', 'FR', 'DE' ), // 17 digits (too long).
			array( '1234567890123456789', 'DE', 'IT' ), // 19 digits (too long).

			// Invalid formats for UK.
			array( 'A12345678', 'GB', 'IE' ),      // Single letter prefix (invalid).
			array( 'HH123456789', 'GB', 'FR' ),    // H prefix with 9 digits (invalid).
			array( 'E1234567', 'GB', 'DE' ),       // E prefix with 7 digits (invalid).

			// Invalid country suffix formats.
			array( 'AB12345678XY', 'FR', 'DE' ),   // Invalid country code XY.
			array( 'AB123456789GB', 'IE', 'GB' ),  // Too many digits with GB suffix.
			array( 'ABC12345678GB', 'GB', 'IE' ),  // Too many prefix letters.

			// Empty or whitespace only.
			array( '', 'GB', 'IE' ),               // Empty string.
			array( '   ', 'FR', 'DE' ),            // Whitespace only.

			// Invalid characters.
			array( '12345-67890-12345', 'GB', 'IE' ), // Dashes (invalid).
			array( '1234567890123@45', 'FR', 'DE' ),  // Special character.
			array( 'H12345678!', 'GB', 'FR' ),       // Exclamation mark.

			// Wrong length for specific patterns.
			array( 'H123456789', 'GB', 'IE' ),     // H prefix with 9 digits (should be 8).
			array( 'E1234567', 'GB', 'FR' ),       // E prefix with 7 digits (should be 8).
			array( 'HH12345678G', 'GB', 'DE' ),    // Incomplete country suffix.

			// Invalid mixed formats.
			array( '12AB345678901234', 'FR', 'DE' ), // Letters in middle of numeric.
			array( 'ABCD123456', 'GB', 'IE' ),       // Too many letters for standard.
		);
	}

	/**
	 * Data provider for tracking numbers from unsupported countries.
	 *
	 * @return array[]
	 */
	public function unsupportedCountryProvider(): array {
		return array(
			// Countries not in Evri network.
			array( '1234567890123456', 'US', 'GB' ), // US not supported.
			array( '123456789012345', 'CA', 'IE' ),  // Canada not supported.
			array( '1234567890123456', 'JP', 'FR' ), // Japan not supported.
			array( '123456789012345', 'AU', 'DE' ),  // Australia not supported.
			array( 'AB12345678US', 'BR', 'GB' ),     // Brazil not supported.
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
			"Score should be at least {$expected_min_score} for {$tracking_number} from {$from} to {$to}, got {$result['ambiguity_score']}"
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
	 * Test try_parse_tracking_number method with unsupported countries.
	 *
	 * @dataProvider unsupportedCountryProvider
	 *
	 * @param string $tracking_number The tracking number to test.
	 * @param string $from            Origin country.
	 * @param string $to              Destination country.
	 */
	public function test_try_parse_tracking_number_unsupported_country( string $tracking_number, string $from, string $to ): void {
		$result = $this->provider->try_parse_tracking_number( $tracking_number, $from, $to );
		$this->assertNull( $result, "Should return null for unsupported country: {$from}" );
	}

	/**
	 * Test tracking number normalization (spaces, case sensitivity).
	 */
	public function test_tracking_number_normalization(): void {
		$test_cases = array(
			// With spaces.
			array( '1234 5678 9012 3456', 'GB', 'IE' ),
			array( '  H123 456 78  ', 'GB', 'FR' ),
			array( 'AB123 456 78GB', 'GB', 'DE' ),

			// Mixed case.
			array( 'h12345678', 'GB', 'IE' ),
			array( 'e87654321', 'GB', 'FR' ),
			array( 'ab12345678gb', 'GB', 'DE' ),
			array( 'Hh12345678Gb', 'GB', 'IE' ),
		);

		foreach ( $test_cases as $test_case ) {
			list( $tracking_number, $from, $to ) = $test_case;
			$result                              = $this->provider->try_parse_tracking_number( $tracking_number, $from, $to );

			$this->assertIsArray( $result, "Should handle normalization for: {$tracking_number}" );
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
		$result = $this->provider->try_parse_tracking_number( '', 'GB', 'IE' );
		$this->assertNull( $result );

		// Empty origin country.
		$result = $this->provider->try_parse_tracking_number( 'H12345678', '', 'IE' );
		$this->assertNull( $result );

		// Empty destination country.
		$result = $this->provider->try_parse_tracking_number( 'H12345678', 'GB', '' );
		$this->assertNull( $result );

		// All empty.
		$result = $this->provider->try_parse_tracking_number( '', '', '' );
		$this->assertNull( $result );
	}

	/**
	 * Test confidence scoring consistency.
	 */
	public function test_confidence_scoring_consistency(): void {
		// UK (primary market) should have higher score than other countries.
		$result_uk     = $this->provider->try_parse_tracking_number( '1234567890123456', 'GB', 'US' );
		$result_france = $this->provider->try_parse_tracking_number( '1234567890123456', 'FR', 'US' );

		$this->assertIsArray( $result_uk );
		$this->assertIsArray( $result_france );

		$this->assertGreaterThan(
			$result_france['ambiguity_score'],
			$result_uk['ambiguity_score'],
			'UK should have higher confidence than France'
		);

		// Ireland should have higher confidence than standard pattern countries.
		$result_ireland = $this->provider->try_parse_tracking_number( '1234567890123456', 'IE', 'US' );
		$result_germany = $this->provider->try_parse_tracking_number( '1234567890123456', 'DE', 'US' );

		$this->assertIsArray( $result_ireland );
		$this->assertIsArray( $result_germany );

		$this->assertGreaterThan(
			$result_germany['ambiguity_score'],
			$result_ireland['ambiguity_score'],
			'Ireland should have higher confidence than Germany'
		);
	}

	/**
	 * Test destination boost scoring.
	 */
	public function test_destination_boost_scoring(): void {
		// Cross-border Evri shipping should get confidence boost.
		$result_boost    = $this->provider->try_parse_tracking_number( '1234567890123456', 'FR', 'DE' );
		$result_no_boost = $this->provider->try_parse_tracking_number( '1234567890123456', 'FR', 'US' );

		$this->assertIsArray( $result_boost );
		$this->assertIsArray( $result_no_boost );

		$this->assertGreaterThan( $result_no_boost['ambiguity_score'], $result_boost['ambiguity_score'] );

		// Destination boost should give score of 85 for FR origin (80+5).
		$this->assertSame( 85, $result_boost['ambiguity_score'] );
		$this->assertSame( 80, $result_no_boost['ambiguity_score'] );
	}

	/**
	 * Test UK boost scoring.
	 */
	public function test_uk_boost_scoring(): void {
		// UK shipments should get extra boost.
		$result_uk_boost = $this->provider->try_parse_tracking_number( 'H12345678', 'GB', 'DE' );
		$result_ie       = $this->provider->try_parse_tracking_number( 'AB12345678IE', 'IE', 'DE' );

		$this->assertIsArray( $result_uk_boost );
		$this->assertIsArray( $result_ie );

		// UK should get both destination boost (+5) and UK boost (+3) = 98.
		// IE should get destination boost (+5) only = 90.
		$this->assertSame( 98, $result_uk_boost['ambiguity_score'] );
		$this->assertSame( 90, $result_ie['ambiguity_score'] );
	}

	/**
	 * Test specific pattern formats for different countries.
	 */
	public function test_country_specific_patterns(): void {
		// Test UK-specific patterns.
		$uk_h_format = $this->provider->try_parse_tracking_number( 'H12345678', 'GB', 'IE' );
		$uk_e_format = $this->provider->try_parse_tracking_number( 'E87654321', 'GB', 'FR' );
		$uk_full     = $this->provider->try_parse_tracking_number( 'HH12345678GB', 'GB', 'DE' );

		$this->assertIsArray( $uk_h_format );
		$this->assertIsArray( $uk_e_format );
		$this->assertIsArray( $uk_full );

		// All should have high confidence with boosts.
		$this->assertSame( 98, $uk_h_format['ambiguity_score'] ); // 90+5+3
		$this->assertSame( 98, $uk_e_format['ambiguity_score'] );  // 90+5+3
		$this->assertSame( 98, $uk_full['ambiguity_score'] );      // 90+5+3

		// Test Ireland-specific pattern.
		$ie_format = $this->provider->try_parse_tracking_number( 'AB12345678IE', 'IE', 'GB' );
		$this->assertIsArray( $ie_format );
		$this->assertSame( 90, $ie_format['ambiguity_score'] ); // 85+5

		// Test standard countries with country suffix.
		$fr_format = $this->provider->try_parse_tracking_number( 'AB12345678FR', 'FR', 'DE' );
		$this->assertIsArray( $fr_format );
		$this->assertSame( 85, $fr_format['ambiguity_score'] ); // 80+5
	}

	/**
	 * Test URL encoding in tracking URLs.
	 */
	public function test_tracking_url_encoding(): void {
		// Test that special characters are properly encoded.
		$tracking_with_spaces = 'H123 456 78';
		$result               = $this->provider->try_parse_tracking_number( $tracking_with_spaces, 'GB', 'IE' );

		$this->assertIsArray( $result );

		// Should normalize spaces away before creating URL.
		$expected_url = 'https://www.evri.com/track/H12345678';
		$this->assertSame( $expected_url, $result['url'] );
	}

	/**
	 * Test that common patterns work for standard pattern countries.
	 */
	public function test_common_patterns_for_standard_countries(): void {
		$standard_countries = array( 'FR', 'DE', 'IT', 'ES', 'NL', 'BE', 'AT', 'PL', 'GR', 'PT', 'CH' );

		foreach ( $standard_countries as $country ) {
			// Test 16-digit pattern.
			$result_16 = $this->provider->try_parse_tracking_number( '1234567890123456', $country, 'GB' );
			$this->assertIsArray( $result_16, "16-digit pattern should work for {$country}" );

			// Test 15-digit pattern.
			$result_15 = $this->provider->try_parse_tracking_number( '123456789012345', $country, 'GB' );
			$this->assertIsArray( $result_15, "15-digit pattern should work for {$country}" );

			// Test country-specific suffix pattern.
			$result_suffix = $this->provider->try_parse_tracking_number( "AB12345678{$country}", $country, 'GB' );
			$this->assertIsArray( $result_suffix, "Country suffix pattern should work for {$country}" );
		}
	}
}
