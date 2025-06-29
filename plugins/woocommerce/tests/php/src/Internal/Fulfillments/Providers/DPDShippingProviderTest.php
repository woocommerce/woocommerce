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
		$expected_core_countries = array( 'DE', 'GB', 'FR', 'NL', 'BE', 'PL', 'IE' );
		foreach ( $expected_core_countries as $country ) {
			$this->assertContains( $country, $countries, "Country {$country} should be in shipping from countries" );
		}

		// Test that we have a reasonable number of countries.
		$this->assertGreaterThan( 10, count( $countries ), 'Should have more than 10 countries' );
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
			// German tracking numbers (high confidence).
			array( '12345678901234', 'DE', 'FR', 95 ), // 14 digits from Germany.
			array( '123456789012', 'DE', 'NL', 95 ),   // 12 digits from Germany.
			array( '1234567890123A', 'DE', 'BE', 95 ), // 13 digits + letter from Germany.

			// UK tracking numbers (high confidence).
			array( 'ABCDEFGHIJKL', 'GB', 'DE', 95 ),   // 12 letters UK format.
			array( 'ABCDEFGHIJKLMN', 'GB', 'FR', 95 ), // 14 letters UK format.
			array( 'AB1234567890', 'GB', 'FR', 95 ),   // 2 letters + 10 digits UK.
			array( '123456789012', 'GB', 'DE', 95 ),   // Pure numeric UK.
			array( '1234567890123A', 'GB', 'FR', 95 ), // 13 digits + letter UK.

			// French tracking numbers (high confidence).
			array( '12345678901234', 'FR', 'DE', 95 ), // 14 digits from France.
			array( '123456789012', 'FR', 'GB', 95 ),   // 12 digits from France.

			// Netherlands tracking numbers (high confidence).
			array( '12345678901234', 'NL', 'DE', 95 ), // 14 digits from Netherlands.
			array( '123456789012', 'NL', 'BE', 95 ),   // 12 digits from Netherlands.

			// Belgian tracking numbers (high confidence).
			array( '12345678901234', 'BE', 'NL', 90 ), // 14 digits from Belgium.
			array( '123456789012', 'BE', 'FR', 90 ),   // 12 digits from Belgium.

			// Polish tracking numbers (high confidence).
			array( '12345678901234', 'PL', 'DE', 90 ), // 14 digits from Poland.
			array( 'PL1234567890', 'PL', 'DE', 90 ),   // Country code format from Poland.

			// Irish tracking numbers (medium confidence).
			array( '12345678901234', 'IE', 'GB', 85 ), // 14 digits from Ireland.
			array( 'ABCDEFGHIJKLMN', 'IE', 'GB', 85 ), // 14 letters from Ireland.
			array( 'IE1234567890', 'IE', 'GB', 85 ),   // Country code format from Ireland.

			// Austrian tracking numbers (medium confidence).
			array( '12345678901234', 'AT', 'DE', 80 ), // 14 digits from Austria.
			array( '123456789012', 'AT', 'CH', 80 ),   // 12 digits from Austria.

			// Swiss tracking numbers (medium confidence).
			array( '12345678901234', 'CH', 'DE', 75 ), // 14 digits from Switzerland.
			array( 'ABCDEFGHIJKLMN', 'CH', 'DE', 75 ), // 14 letters from Switzerland.
			array( 'CH1234567890', 'CH', 'AT', 75 ),   // Country code format from Switzerland.

			// Spanish tracking numbers (medium confidence).
			array( '123456789012', 'ES', 'FR', 75 ),   // 12 digits from Spain.
			array( 'ES1234567890', 'ES', 'PT', 75 ),   // Country code format from Spain.

			// Italian tracking numbers (medium confidence).
			array( '123456789012', 'IT', 'FR', 75 ),   // 12 digits from Italy.
			array( 'IT1234567890', 'IT', 'CH', 75 ),   // Country code format from Italy.

			// Cross-border with destination boost.
			array( '12345678901234', 'DE', 'FR', 95 ), // DE to FR (both DPD countries).
			array( '123456789012', 'GB', 'NL', 95 ),   // GB to NL (both DPD countries).
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
			// Extended format with spaces (28 character format).
			array( '0081 827 0998 0000 0200 45 327 276 N', 'DE', 'FR', 95 ),

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

		if ( ! is_null( $result ) ) {
			$this->assertIsArray( $result );
			$this->assertArrayHasKey( 'ambiguity_score', $result );
			// Should have reduced confidence due to unsupported origin country.
			$this->assertLessThan( 85, $result['ambiguity_score'], 'Should have reduced confidence for unsupported origin' );
			$this->assertGreaterThanOrEqual( 40, $result['ambiguity_score'], 'Should still be above minimum threshold' );
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
		// Same tracking number from high-confidence country should have higher score.
		$result_high   = $this->provider->try_parse_tracking_number( '12345678901234', 'DE', 'FR' );
		$result_medium = $this->provider->try_parse_tracking_number( '12345678901234', 'AT', 'FR' );

		$this->assertIsArray( $result_high );
		$this->assertIsArray( $result_medium );

		$this->assertGreaterThan(
			$result_medium['ambiguity_score'],
			$result_high['ambiguity_score'],
			'High-confidence country should have higher score than medium-confidence country'
		);
	}
}
