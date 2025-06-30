<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\DHLShippingProvider;

/**
 * Unit tests for DHLShippingProvider class.
 */
class DHLShippingProviderTest extends \WP_UnitTestCase {
	/**
	 * The provider instance being tested.
	 *
	 * @var DHLShippingProvider
	 */
	private DHLShippingProvider $provider;

	/**
	 * Sets up the test fixture.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->provider = new DHLShippingProvider();
	}

	/**
	 * Tests the tracking URL generation for different service types.
	 */
	public function test_get_tracking_url(): void {
		// Test Express tracking URL.
		$this->assertEquals(
			'https://www.dhl.com/en/express/tracking.html?AWB=1234567890',
			$this->provider->get_tracking_url( '1234567890' )
		);

		// Test eCommerce tracking URL.
		$this->assertEquals(
			'https://webtrack.dhlglobalmail.com/?trackingnumber=GM1234567890123456',
			$this->provider->get_tracking_url( 'GM1234567890123456' )
		);

		// Test case insensitivity.
		$this->assertEquals(
			'https://webtrack.dhlglobalmail.com/?trackingnumber=LX123456789DE',
			$this->provider->get_tracking_url( 'lx123456789de' )
		);
	}

	/**
	 * Data provider for tracking number validation tests.
	 *
	 * @return array<array{string, string, string, bool, int|null}> Test cases.
	 */
	public function trackingNumberProvider(): array {
		return array(
			// DHL Express formats.
			array( 'JJD1234567890', 'DE', 'US', true, 98 ),  // JJD format.
			array( 'JVGL1234567890', 'NL', 'DE', true, 98 ),  // JVGL format.
			array( '12345678901', 'US', 'GB', true, 95 ),      // 11-digit AWB.
			array( '1234567890', 'DE', 'FR', true, 85 ),       // 10-digit.

			// DHL eCommerce formats.
			array( 'GM1234567890123456', 'US', 'CA', true, 95 ),  // US/CA optimized.
			array( 'GM1234567890123456', 'DE', 'FR', true, 80 ),  // International.
			array( 'LX123456789DE', 'US', 'DE', true, 90 ),       // International eCommerce.
			array( 'RX123456789GB', 'DE', 'GB', true, 90 ),
			array( '12345678901234', 'GB', 'US', true, 85 ),      // UK eCommerce.

			// DHL Parcel formats.
			array( '3SAB12345678', 'DE', 'NL', true, 95 ),       // European optimized.
			array( '3SCD98765432', 'FR', 'BE', true, 95 ),
			array( '3SXY12345678', 'US', 'CA', true, 85 ),       // Non-European.

			// DHL Global Forwarding.
			array( '1AB1234', 'DE', 'US', true, 90 ),
			array( 'ABC12345', 'US', 'GB', true, 90 ),

			// Invalid formats.
			array( 'INVALID123', 'DE', 'US', false, null ),
			array( '12345', 'US', 'GB', false, null ),
			array( 'JJD123', 'DE', 'FR', false, null ),  // Too short.
			array( 'GM123', 'US', 'CA', false, null ),    // Too short.
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

			// Verify URL matches expected service type.
			if ( preg_match( '/^(GM|LX|RX)/', $tracking_number ) ) {
				$this->assertStringContainsString( 'dhlglobalmail.com', $result['url'] );
			} else {
				$this->assertStringContainsString( 'dhl.com/en/express', $result['url'] );
			}
		} else {
			$this->assertNull( $result );
		}
	}

	/**
	 * Tests regional scoring differences for eCommerce formats.
	 */
	public function test_regional_scoring_differences(): void {
		// GM format scores higher from US/CA.
		$us_result = $this->provider->try_parse_tracking_number( 'GM1234567890123456', 'US', 'DE' );
		$de_result = $this->provider->try_parse_tracking_number( 'GM1234567890123456', 'DE', 'US' );

		$this->assertEquals( 95, $us_result['ambiguity_score'] );
		$this->assertEquals( 80, $de_result['ambiguity_score'] );

		// 3S format scores higher from Europe
		$de_result = $this->provider->try_parse_tracking_number( '3SAB12345678', 'DE', 'US' );
		$us_result = $this->provider->try_parse_tracking_number( '3SAB12345678', 'US', 'DE' );

		$this->assertEquals( 95, $de_result['ambiguity_score'] );
		$this->assertEquals( 85, $us_result['ambiguity_score'] );
	}

	/**
	 * Tests case insensitivity in tracking number parsing.
	 */
	public function test_case_insensitivity(): void {
		$lowercase = $this->provider->try_parse_tracking_number( 'jjd1234567890', 'DE', 'US' );
		$mixedcase = $this->provider->try_parse_tracking_number( 'JvGl1234567890', 'NL', 'DE' );
		$uppercase = $this->provider->try_parse_tracking_number( 'JJD1234567890', 'DE', 'FR' );

		$this->assertEquals( 98, $lowercase['ambiguity_score'] );
		$this->assertEquals( 98, $mixedcase['ambiguity_score'] );
		$this->assertEquals( 98, $uppercase['ambiguity_score'] );
	}
}
