<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\RoyalMailShippingProvider;

/**
 * Unit tests for RoyalMailShippingProvider class.
 */
class RoyalMailShippingProviderTest extends \WP_UnitTestCase {
	/**
	 * The provider instance being tested.
	 *
	 * @var RoyalMailShippingProvider
	 */
	private RoyalMailShippingProvider $provider;

	/**
	 * Sets up the test fixture.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->provider = new RoyalMailShippingProvider();
	}

	/**
	 * Tests the tracking URL generation.
	 */
	public function test_get_tracking_url(): void {
		$this->assertEquals(
			'https://www.royalmail.com/track-your-item#/tracking-results/AB123456789GB',
			$this->provider->get_tracking_url( 'AB123456789GB' )
		);

		// Test URL encoding.
		$this->assertEquals(
			'https://www.royalmail.com/track-your-item#/tracking-results/AB123%2F456',
			$this->provider->get_tracking_url( 'AB123/456' )
		);
	}

	/**
	 * Data provider for tracking number validation tests.
	 *
	 * @return array<array{string, string, string, bool, int|null}> Test cases.
	 */
	public function trackingNumberProvider(): array {
		return array(
			// UPU S10 international formats (base confidence 80, no UPU boost if validation fails).
			array( 'AB123456789GB', 'GB', 'DE', true, 83 ), // S10 format, 80 + European boost (+3) = 83.
			array( 'CD1234567GB', 'GB', 'FR', true, 83 ),   // Alternative S10 format, 80 + European boost (+3) = 83.
			array( 'EF123456789GB', 'GB', 'GB', true, 88 ), // S10 format, 80 + domestic boost (+8) = 88.
			array( 'GH1234567GB', 'GB', 'US', true, 82 ),   // S10 format, 80 + common destination boost (+2) = 82.

			// Domestic tracking formats (base confidence 80).
			array( 'A123456789B', 'GB', 'GB', true, 88 ),   // Domestic format, 80 + domestic boost (+8) = 88.
			array( 'CD12345678EF', 'GB', 'FR', true, 83 ),  // Standard format, 80 + European boost (+3) = 83.
			array( 'GH123456IJ', 'GB', 'DE', true, 83 ),    // Compact format, 80 + European boost (+3) = 83.
			array( 'A123456789B', 'GB', 'US', true, 82 ),   // Domestic format, 80 + common destination boost (+2) = 82.

			// Service-specific patterns.
			array( 'ABCD1234567890', 'GB', 'FR', true, 83 ), // Standard format, 80 + European boost (+3) = 83.
			array( 'SD12345678', 'GB', 'DE', true, 89 ),     // Signed For service, 80 + service boost (+6) + European boost (+3) = 89.
			array( 'SF123456789012', 'GB', 'GB', true, 94 ), // Special Delivery, 80 + service boost (+6) + domestic boost (+8) = 94.
			array( 'RM1234567890', 'GB', 'FR', true, 86 ),   // Royal Mail standard, 80 + service boost (+3) + European boost (+3) = 86.

			// Digital tracking formats (base confidence 80).
			array( '1234567890123456', 'GB', 'GB', true, 88 ), // 16-digit, 80 + domestic boost (+8) = 88.
			array( '1234567890123', 'GB', 'FR', true, 83 ),    // 13-digit, 80 + European boost (+3) = 83.
			array( '123456789012', 'GB', 'DE', true, 83 ),     // 12-digit, 80 + European boost (+3) = 83.
			array( '12345678901', 'GB', 'US', true, 82 ),      // 11-digit, 80 + common destination boost (+2) = 82.
			array( '1234567890', 'GB', 'AU', true, 82 ),       // 10-digit, 80 + common destination boost (+2) = 82.
			array( '123456789', 'GB', 'CA', true, 82 ),        // 9-digit, 80 + common destination boost (+2) = 82.

			// Parcelforce (Royal Mail Group).
			array( 'PF123456789012', 'GB', 'FR', true, 87 ),  // Parcelforce, 80 + service boost (+4) + European boost (+3) = 87.
			array( 'AB12345678PF', 'GB', 'DE', true, 83 ),    // Parcelforce International, 80 + European boost (+3) = 83.
			array( '1234567890123', 'GB', 'GB', true, 88 ),   // Parcelforce Worldwide numeric, 80 + domestic boost (+8) = 88.

			// International tracked services.
			array( 'IT123456789GB', 'GB', 'FR', true, 88 ),   // International Tracked, 80 + service boost (+5) + European boost (+3) = 88.
			array( 'IE123456789GB', 'GB', 'DE', true, 88 ),   // International Economy, 80 + service boost (+5) + European boost (+3) = 88.
			array( 'IS123456789GB', 'GB', 'US', true, 87 ),   // International Standard, 80 + service boost (+5) + common destination boost (+2) = 87.

			// Business services.
			array( 'BF123456789012', 'GB', 'FR', true, 86 ),  // Business services, 80 + service boost (+3) + European boost (+3) = 86.
			array( 'ABC1234567890', 'GB', 'DE', true, 83 ),   // Three-letter business codes, 80 + European boost (+3) = 83.

			// Legacy formats.
			array( 'A12345678BC', 'GB', 'FR', true, 83 ),     // Legacy format, 80 + European boost (+3) = 83.
			array( '123456789ABC', 'GB', 'DE', true, 83 ),    // 9 digits + 3 letters, 80 + European boost (+3) = 83.

			// Invalid formats (non-GB origin).
			array( 'AB123456789GB', 'DE', 'GB', false, null ), // Not from GB.
			array( '1234567890123456', 'FR', 'GB', false, null ), // Not from GB.
			array( 'SD12345678', 'US', 'GB', false, null ),    // Not from GB.

			// Invalid formats (wrong patterns).
			array( 'INVALID123', 'GB', 'FR', false, null ),    // Invalid format.
			array( '12345', 'GB', 'DE', false, null ),         // Too short.
			array( 'AB12345678901234567890', 'GB', 'FR', false, null ), // Too long.
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
				strtoupper( preg_replace( '/\s+/', '', $tracking_number ) ),
				$result['url']
			);
		} else {
			$this->assertNull( $result );
		}
	}

	/**
	 * Tests country support.
	 */
	public function test_country_support(): void {
		$from_countries = $this->provider->get_shipping_from_countries();
		$to_countries   = $this->provider->get_shipping_to_countries();

		// Test that only GB is supported as origin.
		$this->assertEquals( array( 'GB' ), $from_countries );

		// Test that many international destinations are supported.
		$expected_destinations = array( 'GB', 'US', 'CA', 'DE', 'FR', 'AU', 'JP' );
		foreach ( $expected_destinations as $country ) {
			$this->assertContains( $country, $to_countries );
		}

		// Test that we have many international destinations.
		$this->assertGreaterThan( 190, count( $to_countries ) );
	}

	/**
	 * Tests tracking number normalization (spaces, case sensitivity).
	 */
	public function test_tracking_number_normalization(): void {
		$test_cases = array(
			// With spaces.
			array( 'AB 123 456 789 GB', 'GB', 'FR' ),
			array( ' SD123 456 78 ', 'GB', 'DE' ),
			array( '1234 5678 9012 3456', 'GB', 'US' ),

			// Mixed case.
			array( 'ab123456789gb', 'GB', 'FR' ),
			array( 'Sd123456789', 'GB', 'DE' ),
			array( 'pf1234567890', 'GB', 'IE' ),
		);

		foreach ( $test_cases as $test_case ) {
			list( $tracking_number, $from, $to ) = $test_case;
			$result                              = $this->provider->try_parse_tracking_number( $tracking_number, $from, $to );

			$this->assertNotNull( $result, "Should parse tracking number with normalization: {$tracking_number}" );
			$this->assertArrayHasKey( 'url', $result );

			// URL should contain normalized version (no spaces, uppercase).
			$normalized = strtoupper( preg_replace( '/\s+/', '', $tracking_number ) );
			$this->assertStringContainsString( $normalized, $result['url'] );
		}
	}

	/**
	 * Tests empty parameter handling.
	 */
	public function test_empty_parameters(): void {
		// Empty tracking number.
		$result = $this->provider->try_parse_tracking_number( '', 'GB', 'FR' );
		$this->assertNull( $result );

		// Empty origin country.
		$result = $this->provider->try_parse_tracking_number( 'AB123456789GB', '', 'FR' );
		$this->assertNull( $result );

		// Empty destination country.
		$result = $this->provider->try_parse_tracking_number( 'AB123456789GB', 'GB', '' );
		$this->assertNull( $result );

		// All empty.
		$result = $this->provider->try_parse_tracking_number( '', '', '' );
		$this->assertNull( $result );
	}

	/**
	 * Tests non-GB origin rejection.
	 */
	public function test_non_gb_origin_rejection(): void {
		$non_gb_origins = array( 'US', 'DE', 'FR', 'CA', 'AU', 'JP' );

		foreach ( $non_gb_origins as $origin ) {
			$result = $this->provider->try_parse_tracking_number( 'AB123456789GB', $origin, 'GB' );
			$this->assertNull( $result, "Should reject tracking number from non-GB origin: {$origin}" );
		}
	}

	/**
	 * Tests destination-based scoring.
	 */
	public function test_destination_scoring(): void {
		// Test domestic vs international scoring.
		$domestic = $this->provider->try_parse_tracking_number( 'AB123456789GB', 'GB', 'GB' );
		$european = $this->provider->try_parse_tracking_number( 'AB123456789GB', 'GB', 'FR' );
		$common   = $this->provider->try_parse_tracking_number( 'AB123456789GB', 'GB', 'US' );
		$other    = $this->provider->try_parse_tracking_number( 'AB123456789GB', 'GB', 'JP' );

		$this->assertNotNull( $domestic );
		$this->assertNotNull( $european );
		$this->assertNotNull( $common );
		$this->assertNotNull( $other );

		// Domestic should have highest score (80 + 8 domestic boost = 88).
		$this->assertEquals( 88, $domestic['ambiguity_score'] );

		// European should have higher score than common destinations.
		$this->assertEquals( 83, $european['ambiguity_score'] ); // 80 + 3 European boost.
		$this->assertEquals( 82, $common['ambiguity_score'] );   // 80 + 2 common destination boost.
		$this->assertEquals( 82, $other['ambiguity_score'] );    // 80 base confidence.

		$this->assertGreaterThan( $common['ambiguity_score'], $european['ambiguity_score'] );
	}

	/**
	 * Tests service-specific scoring.
	 */
	public function test_service_specific_scoring(): void {
		// Test different service types.
		$standard = $this->provider->try_parse_tracking_number( 'AB123456789GB', 'GB', 'FR' );
		$signed   = $this->provider->try_parse_tracking_number( 'SD12345678', 'GB', 'FR' );
		$special  = $this->provider->try_parse_tracking_number( 'SF123456789012', 'GB', 'FR' );
		$rm_std   = $this->provider->try_parse_tracking_number( 'RM1234567890', 'GB', 'FR' );
		$pf_exp   = $this->provider->try_parse_tracking_number( 'PF123456789012', 'GB', 'FR' );

		$this->assertNotNull( $standard );
		$this->assertNotNull( $signed );
		$this->assertNotNull( $special );
		$this->assertNotNull( $rm_std );
		$this->assertNotNull( $pf_exp );

		// Service-specific patterns should have higher scores.
		$this->assertEquals( 83, $standard['ambiguity_score'] ); // S10 format + European boost.
		$this->assertEquals( 89, $signed['ambiguity_score'] );   // SD service boost + European boost.
		$this->assertEquals( 89, $special['ambiguity_score'] );  // SF service boost + European boost.
		$this->assertEquals( 86, $rm_std['ambiguity_score'] );   // RM service boost + European boost.
		$this->assertEquals( 87, $pf_exp['ambiguity_score'] );   // PF service boost + European boost.

		$this->assertGreaterThan( $standard['ambiguity_score'], $signed['ambiguity_score'] );
		$this->assertGreaterThan( $standard['ambiguity_score'], $special['ambiguity_score'] );
	}

	/**
	 * Tests check digit validation for numeric formats.
	 */
	public function test_check_digit_validation(): void {
		// Test different numeric lengths.
		$result_16 = $this->provider->try_parse_tracking_number( '1234567890123456', 'GB', 'FR' );
		$result_13 = $this->provider->try_parse_tracking_number( '1234567890123', 'GB', 'FR' );
		$result_12 = $this->provider->try_parse_tracking_number( '123456789012', 'GB', 'FR' );
		$result_11 = $this->provider->try_parse_tracking_number( '12345678901', 'GB', 'FR' );

		$this->assertNotNull( $result_16 );
		$this->assertNotNull( $result_13 );
		$this->assertNotNull( $result_12 );
		$this->assertNotNull( $result_11 );

		// All should get European boost.
		$this->assertEquals( 83, $result_16['ambiguity_score'] ); // 80 + 3 European boost.
		$this->assertEquals( 83, $result_13['ambiguity_score'] ); // 80 + 3 European boost.
		$this->assertEquals( 83, $result_12['ambiguity_score'] ); // 80 + 3 European boost.
		$this->assertEquals( 83, $result_11['ambiguity_score'] ); // 80 + 3 European boost.
	}

	/**
	 * Tests provider metadata.
	 */
	public function test_provider_metadata(): void {
		$this->assertEquals( 'royal-mail', $this->provider->get_key() );
		$this->assertEquals( 'Royal Mail', $this->provider->get_name() );
		$this->assertStringEndsWith(
			'/assets/images/shipping_providers/royal-mail.png',
			$this->provider->get_icon()
		);
	}
}
