<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\EvriHermesShippingProvider;

/**
 * Unit tests for EvriHermesShippingProvider class.
 */
class EvriHermesShippingProviderTest extends \WP_UnitTestCase {
	/**
	 * The provider instance being tested.
	 *
	 * @var EvriHermesShippingProvider
	 */
	private EvriHermesShippingProvider $provider;

	/**
	 * Sets up the test fixture.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->provider = new EvriHermesShippingProvider();
	}

	/**
	 * Tests the tracking URL generation.
	 */
	public function test_get_tracking_url(): void {
		$this->assertEquals(
			'https://www.evri.com/track/1234567890123456',
			$this->provider->get_tracking_url( '1234567890123456' )
		);

		// Test URL encoding.
		$this->assertEquals(
			'https://www.evri.com/track/H123%2F456',
			$this->provider->get_tracking_url( 'H123/456' )
		);
	}

	/**
	 * Data provider for tracking number validation tests.
	 *
	 * @return array<array{string, string, string, bool, int|null}> Test cases.
	 */
	public function trackingNumberProvider(): array {
		return array(
			// 16-digit numeric patterns (base confidence 90).
			array( '1234567890123456', 'GB', 'IE', true, 92 ), // GB origin gets +2 boost.
			array( '9876543210987654', 'GB', 'FR', true, 92 ), // GB origin gets +2 boost.
			array( '1234567890123456', 'GB', 'DE', true, 92 ), // GB origin gets +2 boost.
			array( '9876543210987654', 'GB', 'US', true, 92 ), // GB origin gets +2 boost.

			// Legacy patterns with prefixes (1-2 letters + 14-15 digits, base confidence 90).
			array( 'H12345678901234', 'GB', 'FR', true, 92 ),   // H + 14 digits, GB origin.
			array( 'E123456789012345', 'GB', 'DE', true, 92 ),  // E + 15 digits, GB origin.
			array( 'HM12345678901234', 'GB', 'US', true, 92 ),  // HM + 14 digits, GB origin.
			array( 'EV123456789012345', 'GB', 'CA', true, 92 ), // EV + 15 digits, GB origin.
			array( 'HH12345678901234', 'GB', 'IE', true, 92 ),  // HH + 14 digits, GB origin.
			array( 'H12345678901234', 'GB', 'IT', true, 92 ),   // H + 14 digits, GB origin.
			array( 'E123456789012345', 'GB', 'ES', true, 92 ),  // E + 15 digits, GB origin.

			// MH + 16 digits pattern (base confidence 90).
			array( 'MH1234567890123456', 'GB', 'DE', true, 92 ), // GB origin gets +2 boost.
			array( 'MH9876543210987654', 'GB', 'FR', true, 92 ), // GB origin gets +2 boost.

			// 8-digit calling card pattern (confidence 80).
			array( '12345678', 'GB', 'IE', true, 80 ),
			array( '87654321', 'GB', 'FR', true, 80 ),
			array( '11223344', 'GB', 'DE', true, 80 ),

			// Legacy 13-15 digit patterns (confidence 75 + 15 boost for GB = 90).
			array( '1234567890123', 'GB', 'FR', true, 90 ),   // 13 digits, GB origin.
			array( '12345678901234', 'GB', 'DE', true, 90 ),  // 14 digits, GB origin.
			array( '123456789012345', 'GB', 'IE', true, 90 ), // 15 digits, GB origin.
			array( '1234567890123', 'GB', 'ES', true, 90 ),   // 13 digits, GB origin.
			array( '12345678901234', 'GB', 'IT', true, 90 ),  // 14 digits, GB origin.

			// Invalid formats.
			array( '123456789012', 'GB', 'FR', false, null ),     // 12 digits (too short).
			array( '12345678901234567', 'GB', 'DE', false, null ), // 17 digits (too long).
			array( 'INVALID123', 'GB', 'FR', false, null ),       // Invalid format.
			array( '1234567', 'GB', 'IE', false, null ),          // 7 digits (too short for calling card).
			array( '123456789', 'GB', 'FR', false, null ),        // 9 digits (too short for calling card).

			// Invalid with unsupported countries (Evri only ships from GB).
			array( '1234567890123456', 'US', 'CA', false, null ), // US/CA not supported.
			array( 'H12345678901234', 'JP', 'AU', false, null ),  // JP/AU not supported.
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

		// Test that Evri only ships from UK.
		$this->assertEquals( array( 'GB' ), $from_countries );

		// Test that main European countries are supported for shipping to.
		$expected_countries = array( 'GB', 'IE', 'FR', 'DE', 'IT', 'ES', 'NL', 'BE' );
		foreach ( $expected_countries as $country ) {
			$this->assertContains( $country, $to_countries );
		}

		// Test that more destinations are supported than origins.
		$this->assertGreaterThan( count( $from_countries ), count( $to_countries ) );
	}

	/**
	 * Tests tracking number normalization (spaces, case sensitivity).
	 */
	public function test_tracking_number_normalization(): void {
		$test_cases = array(
			// With spaces.
			array( '1234 5678 9012 3456', 'GB', 'FR' ),
			array( ' H123 456 789 01234 ', 'GB', 'DE' ),
			array( 'E 123 456 789 012 345', 'GB', 'IE' ),

			// Mixed case.
			array( 'h12345678901234', 'GB', 'FR' ),
			array( 'Ev123456789012345', 'GB', 'DE' ),
			array( 'mh1234567890123456', 'GB', 'IE' ),
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
		$result = $this->provider->try_parse_tracking_number( '1234567890123456', '', 'FR' );
		$this->assertNull( $result );

		// Empty destination country.
		$result = $this->provider->try_parse_tracking_number( '1234567890123456', 'GB', '' );
		$this->assertNull( $result );

		// All empty.
		$result = $this->provider->try_parse_tracking_number( '', '', '' );
		$this->assertNull( $result );
	}

	/**
	 * Tests GB origin boost scoring.
	 */
	public function test_gb_origin_boost(): void {
		// Same 16-digit number from GB vs invalid non-GB origin.
		$gb_result = $this->provider->try_parse_tracking_number( '1234567890123456', 'GB', 'FR' );
		$de_result = $this->provider->try_parse_tracking_number( '1234567890123456', 'DE', 'FR' );

		$this->assertNotNull( $gb_result );
		$this->assertNull( $de_result ); // Should be null since Evri only ships from GB.

		// GB should get +2 boost (92).
		$this->assertEquals( 92, $gb_result['ambiguity_score'] );
	}

	/**
	 * Tests specific pattern formats.
	 */
	public function test_pattern_formats(): void {
		// Test 16-digit format.
		$result = $this->provider->try_parse_tracking_number( '1234567890123456', 'GB', 'FR' );
		$this->assertNotNull( $result );
		$this->assertEquals( 92, $result['ambiguity_score'] );

		// Test letter prefix formats.
		$h_result = $this->provider->try_parse_tracking_number( 'H12345678901234', 'GB', 'DE' );
		$this->assertNotNull( $h_result );
		$this->assertEquals( 92, $h_result['ambiguity_score'] );

		$mh_result = $this->provider->try_parse_tracking_number( 'MH1234567890123456', 'GB', 'IE' );
		$this->assertNotNull( $mh_result );
		$this->assertEquals( 92, $mh_result['ambiguity_score'] );

		// Test calling card format.
		$card_result = $this->provider->try_parse_tracking_number( '12345678', 'GB', 'FR' );
		$this->assertNotNull( $card_result );
		$this->assertEquals( 80, $card_result['ambiguity_score'] );

		// Test legacy format.
		$legacy_result = $this->provider->try_parse_tracking_number( '1234567890123', 'GB', 'DE' );
		$this->assertNotNull( $legacy_result );
		$this->assertEquals( 90, $legacy_result['ambiguity_score'] );
	}

	/**
	 * Tests provider metadata.
	 */
	public function test_provider_metadata(): void {
		$this->assertEquals( 'evri-hermes', $this->provider->get_key() );
		$this->assertEquals( 'Evri (Hermes)', $this->provider->get_name() );
		$this->assertStringEndsWith(
			'/assets/images/shipping_providers/evri-hermes.png',
			$this->provider->get_icon()
		);
	}
}
