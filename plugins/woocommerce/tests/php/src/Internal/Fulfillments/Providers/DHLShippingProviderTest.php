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

			// DHL Air Waybill without valid mod11 check digit (90).
			array( '12345678903', 'US', 'GB', true, 90 ),      // 11-digit AWB without valid check digit.

			// DHL Air Waybill with invalid check digit (90).
			array( '12345678901', 'US', 'GB', true, 90 ),      // 11-digit AWB with invalid check digit.

			// DHL 10-digit (gets 98 or 90 based on mod11 check digit).
			array( '1234567890', 'DE', 'FR', true, 98 ),       // 10-digit with mod11 validation.
			array( '1234567895', 'DE', 'FR', true, 90 ),       // 10-digit without valid mod11.

			// Valid country combinations supported by DHL.
			array( '1234567896', 'BG', 'RO', true, 90 ),    // 10-digit without valid mod11.
			array( '1234567890', 'BG', 'RO', true, 98 ),    // 10-digit with mod11 validation.

			// DHL eCommerce North America.
			array( 'GM1234567890123456', 'US', 'CA', true, 95 ),  // US/CA optimized.
			array( 'GM1234567890123456', 'DE', 'FR', true, 80 ),  // International.

			// DHL eCommerce Asia-Pacific (92 score for pattern match).
			array( 'LX123456789DE', 'US', 'DE', true, 92 ),       // LX pattern.
			array( 'RX123456789GB', 'DE', 'GB', true, 92 ),       // RX pattern.
			array( 'AU123456789AU', 'AU', 'US', true, 92 ),       // AU pattern.
			array( 'AU123456789AU', 'DE', 'US', true, 92 ),       // AU pattern from DE.
			array( 'TH123456789TH', 'AU', 'US', true, 92 ),       // TH pattern from AU.
			array( 'TH123456789TH', 'DE', 'US', true, 92 ),       // TH pattern from DE.

			// DHL eCommerce Europe (14-digit gets 60 score for non-DE domestic).
			array( '12345678901234', 'GB', 'US', true, 60 ),      // 14-digit non-DE.

			// DHL Parcel Europe (3S pattern gets 95 score).
			array( '3SAB12345678', 'DE', 'NL', true, 95 ),       // 3S pattern.
			array( '3SCD98765432', 'FR', 'BE', true, 95 ),       // 3S pattern.
			array( '3SXY12345678', 'US', 'CA', true, 95 ),       // 3S pattern from US.

			// DHL Same Day.
			array( 'DSD123456789012', 'DE', 'US', true, 92 ),

			// DHL Piece Numbers.
			array( 'JD12345678901', 'DE', 'US', true, 90 ),

			// DHL Supply Chain.
			array( 'DSC1234567890123', 'DE', 'US', true, 85 ),

			// DHL Legacy formats (matches S10 pattern, gets 75 score).
			array( 'LZ123456787DE', 'DE', 'US', true, 75 ),  // Matches S10 pattern.
			array( 'LZ123456789DE', 'DE', 'US', true, 75 ),  // Matches S10 pattern.

			// DHL Global Forwarding.
			array( '1AB1234', 'DE', 'US', true, 90 ),
			array( 'ABC12345', 'US', 'GB', true, 88 ),

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
			if ( preg_match( '/^(GM|LX|RX|CN|SG|MY|HK|AU|TH|420)/', $tracking_number ) ) {
				$this->assertStringContainsString( 'dhlglobalmail.com', $result['url'] );
			} elseif ( preg_match( '/^3S/', $tracking_number ) ) {
				$this->assertStringContainsString( 'dhl.de', $result['url'] );
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

		// 3S format gives same score regardless of origin
		$de_result = $this->provider->try_parse_tracking_number( '3SAB12345678', 'DE', 'US' );
		$us_result = $this->provider->try_parse_tracking_number( '3SAB12345678', 'US', 'DE' );

		$this->assertEquals( 95, $de_result['ambiguity_score'] );
		$this->assertEquals( 95, $us_result['ambiguity_score'] );
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
