<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\RoyalMailShippingProvider;

/**
 * Unit tests for RoyalMailShippingProvider class.
 *
 * @package WooCommerce\Tests\Internal\Fulfillments\Providers
 */
class RoyalMailShippingProviderTest extends \WP_UnitTestCase {
	/**
	 * Instance of RoyalMailShippingProvider used in tests.
	 *
	 * @var RoyalMailShippingProvider
	 */
	private RoyalMailShippingProvider $provider;

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->provider = new RoyalMailShippingProvider();
	}

	/**
	 * Test the get_key method.
	 */
	public function test_get_key(): void {
		$this->assertSame( 'royal-mail', $this->provider->get_key() );
	}

	/**
	 * Test the get_name method.
	 */
	public function test_get_name(): void {
		$this->assertSame( 'Royal Mail', $this->provider->get_name() );
	}

	/**
	 * Test the get_tracking_url method.
	 */
	public function test_get_tracking_url(): void {
		$tracking_number = 'AB123456789GB';
		$expected_url    = 'https://www.royalmail.com/track-your-item#/tracking-results/' . $tracking_number;
		$this->assertSame( $expected_url, $this->provider->get_tracking_url( $tracking_number ) );
	}

	/**
	 * Test get_shipping_from_countries returns expected countries.
	 */
	public function test_get_shipping_from_countries(): void {
		$countries = $this->provider->get_shipping_from_countries();

		// Test that UK is included.
		$expected_countries = array( 'GB' );
		foreach ( $expected_countries as $country ) {
			$this->assertContains( $country, $countries, "Country {$country} should be in shipping from countries" );
		}

		// Test that we have the expected number of countries (only UK).
		$this->assertSame( 1, count( $countries ), 'Should have exactly 1 country (UK)' );
	}

	/**
	 * Test get_shipping_to_countries includes international destinations.
	 */
	public function test_get_shipping_to_countries(): void {
		$to_countries = $this->provider->get_shipping_to_countries();

		// Test that common destinations are included.
		$expected_destinations = array( 'GB', 'US', 'FR', 'DE', 'ES', 'IT', 'AU', 'CA' );
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
			// International format: XX#########GB.
			array( 'AB123456789GB', 'GB', 'US', 85 ),   // International shipment.
			array( 'CD987654321GB', 'GB', 'GB', 92 ),   // Domestic shipment with boost.
			array( 'EF555666777GB', 'GB', 'FR', 90 ),   // European destination with boost.

			// Alternative international format: XX#######GB.
			array( 'AB1234567GB', 'GB', 'DE', 90 ),     // European destination with boost.
			array( 'CD9876543GB', 'GB', 'GB', 92 ),     // Domestic with boost.
			array( 'EF5556667GB', 'GB', 'AU', 85 ),     // International.

			// Domestic format: X#########X.
			array( 'A123456789B', 'GB', 'GB', 92 ),     // Domestic with boost.
			array( 'C987654321D', 'GB', 'IT', 90 ),     // European destination with boost.
			array( 'E555666777F', 'GB', 'US', 85 ),     // International.

			// Standard format: XX########XX.
			array( 'AB12345678CD', 'GB', 'GB', 92 ),    // Domestic with boost.
			array( 'EF98765432GH', 'GB', 'ES', 90 ),    // European destination with boost.
			array( 'IJ55566677KL', 'GB', 'JP', 85 ),    // International.

			// 13-digit domestic tracking.
			array( '1234567890123', 'GB', 'GB', 92 ),   // Domestic with boost.
			array( '9876543210987', 'GB', 'NL', 90 ),   // European destination with boost.
			array( '5556667778889', 'GB', 'CA', 85 ),   // International.

			// 12-digit domestic tracking.
			array( '123456789012', 'GB', 'GB', 92 ),    // Domestic with boost.
			array( '987654321098', 'GB', 'BE', 90 ),    // European destination with boost.
			array( '555666777888', 'GB', 'AU', 85 ),    // International.

			// Compact format: XX######XX.
			array( 'AB123456CD', 'GB', 'GB', 92 ),      // Domestic with boost.
			array( 'EF987654GH', 'GB', 'IE', 90 ),      // European destination with boost.
			array( 'IJ555666KL', 'GB', 'US', 85 ),      // International.

			// Special delivery format: XXXX##########.
			array( 'ABCD1234567890', 'GB', 'GB', 92 ),  // Domestic with boost.
			array( 'EFGH9876543210', 'GB', 'FR', 90 ),  // European destination with boost.
			array( 'IJKL5556667778', 'GB', 'US', 85 ),  // International.
		);
	}

	/**
	 * Data provider for invalid tracking number parsing tests.
	 *
	 * @return array[]
	 */
	public function invalidTrackingNumberProvider(): array {
		return array(
			// Wrong origin country (not UK).
			array( 'AB123456789GB', 'US', 'GB' ),       // From US instead of GB.
			array( '1234567890123', 'FR', 'GB' ),       // From FR instead of GB.
			array( '123456789012', 'DE', 'US' ),        // From DE instead of GB.

			// Too short.
			array( '12345', 'GB', 'US' ),               // Too short.
			array( 'AB123GB', 'GB', 'FR' ),             // Too short for international format.
			array( 'A12345B', 'GB', 'DE' ),             // Too short for domestic format.

			// Too long.
			array( '12345678901234567890', 'GB', 'US' ), // Too long.
			array( 'AB123456789012345GB', 'GB', 'FR' ), // Too long for international format.
			array( 'ABCDE123456789012345', 'GB', 'DE' ), // Too long for special delivery.

			// Invalid format.
			array( '123456789AB', 'GB', 'US' ),         // Mixed format invalid.
			array( 'ABCDEFGHIJK', 'GB', 'FR' ),         // All letters invalid length.
			array( 'AB12345GB67', 'GB', 'DE' ),         // Invalid pattern.

			// Empty or whitespace only.
			array( '', 'GB', 'US' ),                    // Empty string.
			array( '   ', 'GB', 'FR' ),                 // Whitespace only.

			// Invalid characters.
			array( '12-34-56-78-90-12', 'GB', 'US' ),   // Dashes (invalid format).
			array( '123.456.789.012', 'GB', 'FR' ),     // Dots (invalid format).
			array( 'AB123456@89GB', 'GB', 'DE' ),       // Special characters.
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
		$this->assertGreaterThanOrEqual( 85, $result['ambiguity_score'], 'Score should be at least 85' );
		$this->assertLessThanOrEqual( 92, $result['ambiguity_score'], 'Score should not exceed 92' );

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
			array( 'AB 123 456 789 GB', 'GB', 'US' ),
			array( '  AB123456789GB  ', 'GB', 'FR' ),
			array( '1234 5678 9012 3', 'GB', 'DE' ),

			// Mixed case.
			array( 'ab123456789gb', 'GB', 'US' ),
			array( 'Ab123456789Gb', 'GB', 'FR' ),
			array( 'AB123456789GB', 'GB', 'DE' ),
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
		$result = $this->provider->try_parse_tracking_number( '', 'GB', 'US' );
		$this->assertNull( $result );

		// Empty origin country.
		$result = $this->provider->try_parse_tracking_number( 'AB123456789GB', '', 'US' );
		$this->assertNull( $result );

		// Empty destination country.
		$result = $this->provider->try_parse_tracking_number( 'AB123456789GB', 'GB', '' );
		$this->assertNull( $result );

		// All empty.
		$result = $this->provider->try_parse_tracking_number( '', '', '' );
		$this->assertNull( $result );
	}

	/**
	 * Test domestic vs international vs European scoring.
	 */
	public function test_confidence_scoring(): void {
		// Domestic shipment should get highest confidence boost.
		$result_domestic = $this->provider->try_parse_tracking_number( 'AB123456789GB', 'GB', 'GB' );
		$this->assertIsArray( $result_domestic );
		$this->assertSame( 92, $result_domestic['ambiguity_score'] );

		// European destination should get medium boost.
		$result_european = $this->provider->try_parse_tracking_number( 'AB123456789GB', 'GB', 'FR' );
		$this->assertIsArray( $result_european );
		$this->assertSame( 90, $result_european['ambiguity_score'] );

		// International should get base confidence.
		$result_international = $this->provider->try_parse_tracking_number( 'AB123456789GB', 'GB', 'US' );
		$this->assertIsArray( $result_international );
		$this->assertSame( 85, $result_international['ambiguity_score'] );

		// Verify scoring hierarchy.
		$this->assertGreaterThan( $result_european['ambiguity_score'], $result_domestic['ambiguity_score'] );
		$this->assertGreaterThan( $result_international['ambiguity_score'], $result_european['ambiguity_score'] );
	}

	/**
	 * Test specific pattern formats.
	 */
	public function test_pattern_formats(): void {
		// Test international format XX#########GB.
		$intl_result = $this->provider->try_parse_tracking_number( 'AB123456789GB', 'GB', 'US' );
		$this->assertIsArray( $intl_result );
		$this->assertSame( 85, $intl_result['ambiguity_score'] );

		// Test alternative international format XX#######GB.
		$alt_intl_result = $this->provider->try_parse_tracking_number( 'AB1234567GB', 'GB', 'US' );
		$this->assertIsArray( $alt_intl_result );
		$this->assertSame( 85, $alt_intl_result['ambiguity_score'] );

		// Test domestic format X#########X.
		$domestic_result = $this->provider->try_parse_tracking_number( 'A123456789B', 'GB', 'US' );
		$this->assertIsArray( $domestic_result );
		$this->assertSame( 85, $domestic_result['ambiguity_score'] );

		// Test standard format XX########XX.
		$standard_result = $this->provider->try_parse_tracking_number( 'AB12345678CD', 'GB', 'US' );
		$this->assertIsArray( $standard_result );
		$this->assertSame( 85, $standard_result['ambiguity_score'] );

		// Test 13-digit tracking.
		$digit13_result = $this->provider->try_parse_tracking_number( '1234567890123', 'GB', 'US' );
		$this->assertIsArray( $digit13_result );
		$this->assertSame( 85, $digit13_result['ambiguity_score'] );

		// Test 12-digit tracking.
		$digit12_result = $this->provider->try_parse_tracking_number( '123456789012', 'GB', 'US' );
		$this->assertIsArray( $digit12_result );
		$this->assertSame( 85, $digit12_result['ambiguity_score'] );

		// Test compact format XX######XX.
		$compact_result = $this->provider->try_parse_tracking_number( 'AB123456CD', 'GB', 'US' );
		$this->assertIsArray( $compact_result );
		$this->assertSame( 85, $compact_result['ambiguity_score'] );

		// Test special delivery format XXXX##########.
		$special_result = $this->provider->try_parse_tracking_number( 'ABCD1234567890', 'GB', 'US' );
		$this->assertIsArray( $special_result );
		$this->assertSame( 85, $special_result['ambiguity_score'] );
	}

	/**
	 * Test non-UK origin rejection.
	 */
	public function test_non_uk_origin_rejection(): void {
		$non_uk_origins = array( 'US', 'CA', 'FR', 'DE', 'AU', 'JP' );

		foreach ( $non_uk_origins as $origin ) {
			$result = $this->provider->try_parse_tracking_number( 'AB123456789GB', $origin, 'GB' );
			$this->assertNull( $result, "Should reject tracking number from non-UK origin: {$origin}" );
		}
	}

	/**
	 * Test European destination boost.
	 */
	public function test_european_destination_boost(): void {
		$european_destinations = array( 'FR', 'DE', 'ES', 'IT', 'NL', 'BE', 'IE' );

		foreach ( $european_destinations as $destination ) {
			$result = $this->provider->try_parse_tracking_number( 'AB123456789GB', 'GB', $destination );
			$this->assertIsArray( $result );
			$this->assertSame( 90, $result['ambiguity_score'], "European destination {$destination} should get confidence boost" );
		}

		// Test non-European destination doesn't get boost.
		$result_non_eu = $this->provider->try_parse_tracking_number( 'AB123456789GB', 'GB', 'US' );
		$this->assertIsArray( $result_non_eu );
		$this->assertSame( 85, $result_non_eu['ambiguity_score'] );
	}
}