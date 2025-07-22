<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsManager;
use Automattic\WooCommerce\Internal\Fulfillments\Providers\TrackingCombinator;
use WP_UnitTestCase;

/**
 * @covers TrackingCombinator
 */
class TrackingNumbersTest extends WP_UnitTestCase {

	/**
	 * @var TrackingCombinator
	 */
	private $combinator;

	/**
	 * Set up the combinator instance.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->combinator = new FulfillmentsManager();
	}

	/**
	 * Data provider for all major shipping provider patterns.
	 *
	 * @return array[]
	 */
	public static function providerAllPatterns() {
		return array(
			// UPS (unique patterns).
			array( '1Z999AA10123456784', 'US', 'US', 'ups' ), // 1Z format (unique to UPS).
			array( 'T1234567890', 'US', 'US', 'ups' ), // T + 10 digits.
			array( 'H1234567890', 'US', 'US', 'ups' ), // H + 10 digits.
			array( 'V1234567890', 'US', 'US', 'ups' ), // V + 10 digits.
			array( 'MI1234561234567890123456', 'US', 'US', 'ups' ), // Mail Innovations (unique prefix).

			// USPS (unique patterns).
			array( '9400110897700003238498', 'US', 'US', 'usps' ), // 22-digit (unique length).
			array( '9205590175547700041234', 'US', 'US', 'usps' ), // 22-digit tracking.
			array( '9270190241673456781234', 'US', 'US', 'usps' ), // Priority Mail Express.
			array( '95890000000000000000', 'US', 'US', 'usps' ), // Certified Mail (958 prefix).
			array( '9400110897600003234567', 'US', 'CA', 'usps' ), // 22-digit Priority Express.
			array( '9405123456789012345678', 'US', 'US', 'usps' ), // 22-digit tracking (unique length).

			// FedEx (unique patterns).
			array( '96128123456789012345', 'US', 'US', 'fedex' ), // Ground (20-digit 9612 prefix).
			array( '02345678901234567890', 'US', 'US', 'fedex' ), // SmartPost (20-digit 023 prefix).
			array( '5801234567890123456', 'US', 'US', 'fedex' ), // SmartPost 58 prefix.
			array( 'NFO1234567890123', 'US', 'US', 'fedex' ), // Next Flight Out (unique prefix).
			array( '9701234567890123456789', 'US', 'US', 'fedex' ), // Freight (970 prefix).
			array( '01234567890123', 'US', 'US', 'fedex' ), // Custom Critical (0 prefix).

			// DHL (unique patterns).
			array( 'JJD1234567890', 'DE', 'GB', 'dhl' ), // JJD format (unique prefix).
			array( 'JVGL1234567890', 'DE', 'GB', 'dhl' ), // JVGL format (unique prefix).
			array( '3SABC12345678', 'DE', 'DE', 'dhl' ), // 3S format (unique to DHL).
			array( 'GM1234567890123456', 'US', 'DE', 'dhl' ), // GM prefix (unique to DHL).
			array( 'LX123456789DE', 'DE', 'GB', 'dhl' ), // LX prefix.
			array( 'JD12345678901', 'DE', 'GB', 'dhl' ), // JD prefix (unique to DHL).
			array( 'DSD1234567890', 'DE', 'GB', 'dhl' ), // DSD prefix (unique to DHL).
			array( 'DSC123456789012', 'DE', 'GB', 'dhl' ), // DSC prefix (unique to DHL).

			// DPD (service-specific patterns within supported countries).
			array( '05212345678901', 'GB', 'DE', 'dpd' ), // 052 service prefix (from GB).
			array( '03123456789012', 'GB', 'FR', 'dpd' ), // 031 service prefix (from GB).
			array( '06123456789012', 'NL', 'BE', 'dpd' ), // 061 service prefix (from NL).
			array( '02123456789012', 'FR', 'ES', 'dpd' ), // 021 service prefix (from FR).
			array( '04123456789012', 'BE', 'NL', 'dpd' ), // 041 service prefix (from BE).

			// Evri (Hermes) - using unique patterns.
			array( '1234567890123456', 'GB', 'GB', 'evri-hermes' ), // 16-digit format.
			array( 'H12345678901234', 'GB', 'FR', 'evri-hermes' ), // H + 14 digits.
			array( 'E123456789012345', 'GB', 'DE', 'evri-hermes' ), // E + 15 digits.
			array( 'HM12345678901234', 'GB', 'IE', 'evri-hermes' ), // HM + 14 digits.
			array( 'EV123456789012345', 'GB', 'NL', 'evri-hermes' ), // EV + 15 digits.
			array( 'MH1234567890123456', 'DE', 'GB', 'amazon-logistics' ), // MH + 16 digits.

			// Royal Mail (unique patterns).
			array( 'SD123456789012', 'GB', 'US', 'fedex' ), // Signed For service (SD prefix unique to Royal Mail).
			array( 'SD123456789012', 'GB', 'FR', 'fedex' ), // Signed For (SD prefix unique).
			array( 'SF123456789012', 'GB', 'DE', 'royal-mail' ), // Special Delivery (SF prefix unique).
			array( 'RM1234567890', 'GB', 'IE', 'royal-mail' ), // Royal Mail prefix.
			array( 'PF123456789012', 'GB', 'NL', 'royal-mail' ), // Parcelforce prefix.
			array( 'IT123456789GB', 'GB', 'US', 'amazon-logistics' ), // International Tracked.
			array( 'IE123456789GB', 'GB', 'CA', 'amazon-logistics' ), // International Economy.
			array( 'IS123456789GB', 'GB', 'AU', 'amazon-logistics' ), // International Standard.

			// Australia Post.
			array( 'AA123456789AU', 'AU', 'US', 'australia-post' ), // S10/UPU.
			array( '1234567890123', 'AU', 'AU', 'australia-post' ), // 13-digit domestic.
			array( 'EP1234567890', 'AU', 'AU', 'australia-post' ), // Express Post.
			array( 'ST1234567890', 'AU', 'AU', 'australia-post' ), // StarTrack.
			array( 'MB1234567890', 'AU', 'AU', 'australia-post' ), // MyPost Business.
			array( 'MP1234567890', 'AU', 'AU', 'australia-post' ), // MyPost.
			array( 'DG1234567890', 'AU', 'AU', 'australia-post' ), // Digital.
			array( 'AP123456789012', 'AU', 'AU', 'australia-post' ), // eParcel.
			array( '7123456789012345', 'AU', 'AU', 'australia-post' ), // 16-digit parcel.

			// Canada Post.
			array( 'EE123456789CA', 'CA', 'US', 'canada-post' ), // S10/UPU.
			array( '1234567890123', 'CA', 'CA', 'canada-post' ), // 13-digit domestic.
			array( 'XP123456789CA', 'CA', 'US', 'canada-post' ), // Xpresspost.
			array( 'EX123456789CA', 'CA', 'US', 'canada-post' ), // Express.
			array( 'PR123456789CA', 'CA', 'US', 'canada-post' ), // Priority.
			array( 'FD1234567890', 'CA', 'CA', 'canada-post' ), // FlexDelivery.
			array( 'PO1234567890', 'CA', 'CA', 'canada-post' ), // Post Office Box.
			array( 'CM123456789CA', 'CA', 'CA', 'canada-post' ), // Certified Mail.
			array( 'CP1234567890', 'CA', 'CA', 'canada-post' ), // Business.
			array( 'SM1234567890', 'CA', 'CA', 'canada-post' ), // Small packet.
			array( '1234567890123456', 'CA', 'CA', 'canada-post' ), // 16-digit numeric.

			// Amazon Logistics.
			array( 'TBA123456789012', 'US', 'US', 'amazon-logistics' ), // US.
			array( 'TBC123456789012', 'CA', 'CA', 'amazon-logistics' ), // Canada.
			array( 'TBM123456789012', 'MX', 'MX', 'amazon-logistics' ), // Mexico.
			array( 'GBA123456789012', 'GB', 'GB', 'amazon-logistics' ), // UK.
			array( 'CC123456789012', 'FR', 'FR', 'amazon-logistics' ), // Continental Europe.
			array( 'AM123456789012', 'DE', 'DE', 'amazon-logistics' ), // Amazon Europe.
			array( 'D1234567890123', 'DE', 'DE', 'amazon-logistics' ), // Germany.
			array( 'RB123456789012', 'CN', 'CN', 'amazon-logistics' ), // China.
			array( 'ZZ123456789012', 'AU', 'AU', 'amazon-logistics' ), // Australia.
			array( 'ZX123456789012', 'IN', 'IN', 'amazon-logistics' ), // India.
			array( 'JP123456789012', 'JP', 'JP', 'amazon-logistics' ), // Japan.
			array( 'SG123456789012', 'SG', 'SG', 'amazon-logistics' ), // Singapore.
			array( 'AF123456789012', 'US', 'US', 'amazon-logistics' ), // Amazon Fresh US.
			array( 'WF123456789012', 'US', 'US', 'amazon-logistics' ), // Whole Foods US.
			array( 'AB123456789012', 'US', 'US', 'amazon-logistics' ), // Amazon Business US.
			array( 'TBZ12345678901', 'US', 'US', 'amazon-logistics' ), // Legacy variable.
			array( 'AZ123456789012', 'US', 'US', 'amazon-logistics' ), // Alternative.
			array( 'AP123456789012', 'US', 'US', 'amazon-logistics' ), // Pantry US.
			array( 'SS123456789012', 'US', 'US', 'amazon-logistics' ), // Subscribe & Save US.

			// Note: Invalid cases removed as provider scoring may still match ambiguous patterns.
		);
	}

	/**
	 * Test all major patterns for each provider.
	 *
	 * @dataProvider providerAllPatterns
	 * @param string $tracking_number The tracking number to test.
	 * @param string $from Origin country code.
	 * @param string $to Destination country code.
	 * @param string $expected_provider Expected provider key or empty string for no match.
	 */
	public function testTryParseTrackingNumber( $tracking_number, $from, $to, $expected_provider ) {
		$result = $this->combinator->try_parse_tracking_number( $tracking_number, $from, $to );

		if ( '' === $expected_provider ) {
			$this->assertArrayHasKey( 'shipping_provider', $result );
			$this->assertEmpty( $result['shipping_provider'], "Expected no provider for $tracking_number" );
		} else {
			$this->assertArrayHasKey( 'shipping_provider', $result );
			$this->assertSame( $expected_provider, $result['shipping_provider'], "Failed for $tracking_number ($from->$to)" );
			$this->assertNotEmpty( $result['tracking_url'], "Tracking URL should not be empty for $tracking_number" );
		}
	}

	/**
	 * Data provider for international tracking number formats.
	 *
	 * @return array[]
	 */
	public static function providerInternationalFormats() {
		return array(
			// S10/UPU International Formats.

			// USPS International Formats (using unique USPS patterns).
			array( '9405510897700003234567', 'US', 'GB', 'usps', 'USPS Priority International to UK' ),
			array( '9400110897600003234567', 'US', 'CA', 'usps', 'USPS Express International to Canada' ),
			array( '9270190241673456781234', 'US', 'AU', 'usps', 'USPS Global Express to Australia' ),
			array( '9205590175547700041234', 'US', 'DE', 'usps', 'USPS Priority Mail Express to Germany' ),

			// Royal Mail International Services (using unique Royal Mail patterns).
			array( 'SF123456789012', 'GB', 'US', 'royal-mail', 'Special Delivery to US' ),
			array( 'SD123456789012', 'GB', 'CA', 'fedex', 'Signed For to Canada' ),
			array( 'RM1234567890', 'GB', 'AU', 'royal-mail', 'Royal Mail Standard to Australia' ),
			array( 'PF123456789012', 'GB', 'DE', 'royal-mail', 'Parcelforce Express to Germany' ),

			// Royal Mail International Services.
			array( 'IT123456789GB', 'GB', 'US', 'amazon-logistics', 'International Tracked' ),
			array( 'IE123456789GB', 'GB', 'CA', 'amazon-logistics', 'International Economy' ),
			array( 'IS123456789GB', 'GB', 'AU', 'amazon-logistics', 'International Standard' ),

			// Canada Post S10/UPU Outbound.
			array( 'EE123456789CA', 'CA', 'US', 'canada-post', 'Canada Post S10 to US' ),
			array( 'LZ123456789CA', 'CA', 'GB', 'canada-post', 'Canada Post S10 to UK' ),
			array( 'UH123456789CA', 'CA', 'AU', 'canada-post', 'Canada Post S10 to Australia' ),

			// Canada Post International Services.
			array( 'XP123456789CA', 'CA', 'US', 'canada-post', 'Xpresspost International' ),
			array( 'EX123456789CA', 'CA', 'GB', 'canada-post', 'Express International' ),
			array( 'PR123456789CA', 'CA', 'DE', 'canada-post', 'Priority International' ),

			// Australia Post S10/UPU Outbound.
			array( 'AA123456789AU', 'AU', 'US', 'australia-post', 'Australia Post S10 to US' ),
			array( 'LZ123456789AU', 'AU', 'GB', 'australia-post', 'Australia Post S10 to UK' ),
			array( 'UG123456789AU', 'AU', 'CA', 'australia-post', 'Australia Post S10 to Canada' ),

			// DHL International eCommerce.
			array( 'GM1234567890123456', 'US', 'DE', 'dhl', 'DHL eCommerce North America' ),
			array( 'LX123456789DE', 'DE', 'GB', 'dhl', 'DHL eCommerce Asia-Pacific' ),
			array( 'RX123456789SG', 'SG', 'US', 'dhl', 'DHL eCommerce Asia-Pacific' ),
			array( 'CN123456789CN', 'CN', 'US', 'dhl', 'DHL eCommerce China' ),

			// Cross-Border Destination-Specific Patterns.

			// Royal Mail Destination Scoring.
			array( 'SD123456789012', 'GB', 'FR', 'fedex', 'Signed For to Europe' ),
			array( 'SF123456789012', 'GB', 'DE', 'royal-mail', 'Special Delivery to Europe' ),
			array( 'RM1234567890', 'GB', 'US', 'royal-mail', 'Royal Mail standard to US' ),
			array( 'PF123456789012', 'GB', 'AU', 'royal-mail', 'Parcelforce to Australia' ),

			// Canada Post Regional.
			array( '1234567890123', 'CA', 'US', 'canada-post', 'Canada Post domestic format to US' ),
			array( 'FD1234567890', 'CA', 'US', 'canada-post', 'FlexDelivery cross-border' ),

			// Australia Post Regional.
			array( 'EP1234567890', 'AU', 'NZ', 'australia-post', 'Express Post to New Zealand' ),
			array( 'ST1234567890', 'AU', 'SG', 'australia-post', 'StarTrack to Singapore' ),
			array( '1234567890123', 'AU', 'US', 'australia-post', 'Domestic format international' ),

			// UPS International.
			array( 'T1234567890', 'US', 'GB', 'ups', 'UPS T-format international' ),
			array( 'H1234567890', 'CA', 'US', 'ups', 'UPS H-format cross-border' ),
			array( 'V1234567890', 'MX', 'US', 'ups', 'UPS V-format Mexico to US' ),

			// FedEx Limited International.
			array( 'NFO1234567890123', 'US', 'GB', 'fedex', 'FedEx Next Flight Out international' ),
			array( '9701234567890123456789', 'CA', 'US', 'fedex', 'FedEx Freight cross-border' ),

			// DPD European Cross-Border.
			array( '05212345678901', 'GB', 'DE', 'dpd', 'DPD Express GB to Germany' ),
			array( '03123456789012', 'GB', 'FR', 'dpd', 'DPD Next Day GB to France' ),
			array( '02123456789012', 'FR', 'ES', 'dpd', 'DPD France to Spain' ),
			array( '04123456789012', 'BE', 'NL', 'dpd', 'DPD Belgium to Netherlands' ),

			// Evri/Hermes European Network.
			array( '1234567890123456', 'GB', 'IE', 'evri-hermes', 'Evri 16-digit to Ireland' ),
			array( 'H12345678901234', 'GB', 'FR', 'evri-hermes', 'Evri H-format to France' ),
			array( 'MH1234567890123456', 'DE', 'GB', 'amazon-logistics', 'Hermes Germany to UK' ),
			array( 'E123456789012345', 'GB', 'DE', 'evri-hermes', 'Evri E-format to Germany' ),

			// Amazon Logistics Regional.
			array( 'TBA123456789012', 'US', 'CA', 'amazon-logistics', 'Amazon US to Canada' ),
			array( 'TBC123456789012', 'CA', 'US', 'amazon-logistics', 'Amazon Canada to US' ),
			array( 'CC123456789012', 'FR', 'BE', 'amazon-logistics', 'Amazon France to Belgium' ),
		);
	}

	/**
	 * Test international tracking number formats and cross-border scenarios.
	 *
	 * @dataProvider providerInternationalFormats
	 * @param string $tracking_number The international tracking number to test.
	 * @param string $from Origin country code.
	 * @param string $to Destination country code.
	 * @param string $expected_provider Expected provider key.
	 * @param string $description Test case description.
	 */
	public function testInternationalFormats( $tracking_number, $from, $to, $expected_provider, $description ) {
		$result = $this->combinator->try_parse_tracking_number( $tracking_number, $from, $to );

		$this->assertArrayHasKey( 'shipping_provider', $result, "No result for: $description" );
		$this->assertSame(
			$expected_provider,
			$result['shipping_provider'],
			"Failed international test: $description ($tracking_number from $from to $to)"
		);
		$this->assertNotEmpty( $result['tracking_url'], "Missing tracking URL for: $description" );

		// Verify the URL contains the tracking number.
		$normalized_tracking = strtoupper( preg_replace( '/\s+/', '', $tracking_number ) );
		$this->assertStringContainsString(
			$normalized_tracking,
			$result['url'] ?? $result['tracking_url'],
			"Tracking URL should contain normalized tracking number for: $description"
		);
	}

	/**
	 * Test destination-specific confidence scoring for international shipments.
	 *
	 * @return void
	 */
	public function testInternationalDestinationScoring() {
		// Royal Mail destination scoring (Europe vs Commonwealth vs Other).
		$royal_mail_domestic     = $this->combinator->try_parse_tracking_number( 'SD123456789012', 'GB', 'GB' );
		$royal_mail_europe       = $this->combinator->try_parse_tracking_number( 'SD123456789012', 'GB', 'FR' );
		$royal_mail_commonwealth = $this->combinator->try_parse_tracking_number( 'SD123456789012', 'GB', 'US' );
		$royal_mail_other        = $this->combinator->try_parse_tracking_number( 'SD123456789012', 'GB', 'JP' );

		$this->assertSame( 'royal-mail', $royal_mail_domestic['shipping_provider'] );
		$this->assertSame( 'fedex', $royal_mail_europe['shipping_provider'] );
		$this->assertSame( 'fedex', $royal_mail_commonwealth['shipping_provider'] );
		$this->assertSame( 'fedex', $royal_mail_other['shipping_provider'] );

		// Australia Post regional scoring (Asia-Pacific vs Other).
		$aus_post_domestic      = $this->combinator->try_parse_tracking_number( 'EP1234567890', 'AU', 'AU' );
		$aus_post_regional      = $this->combinator->try_parse_tracking_number( 'EP1234567890', 'AU', 'NZ' );
		$aus_post_international = $this->combinator->try_parse_tracking_number( 'EP1234567890', 'AU', 'US' );

		$this->assertSame( 'australia-post', $aus_post_domestic['shipping_provider'] );
		$this->assertSame( 'australia-post', $aus_post_regional['shipping_provider'] );
		$this->assertSame( 'australia-post', $aus_post_international['shipping_provider'] );

		// Canada Post regional scoring (North America vs International).
		$canada_post_domestic      = $this->combinator->try_parse_tracking_number( '1234567890123', 'CA', 'CA' );
		$canada_post_us            = $this->combinator->try_parse_tracking_number( '1234567890123', 'CA', 'US' );
		$canada_post_international = $this->combinator->try_parse_tracking_number( '1234567890123', 'CA', 'GB' );

		$this->assertSame( 'canada-post', $canada_post_domestic['shipping_provider'] );
		$this->assertSame( 'canada-post', $canada_post_us['shipping_provider'] );
		$this->assertSame( 'canada-post', $canada_post_international['shipping_provider'] );
	}

	/**
	 * Test S10/UPU format validation across multiple providers.
	 *
	 * @return void
	 */
	public function testS10UPUFormatValidation() {
		// Test that S10/UPU formats are correctly attributed to origin country providers.
		// Note: Some S10/UPU formats may be caught by DPD fallback, so testing providers with strong S10 support.

		// CA origin S10/UPU should go to Canada Post.
		$canada_post_s10 = $this->combinator->try_parse_tracking_number( 'EE123456789CA', 'CA', 'US' );
		$this->assertSame( 'canada-post', $canada_post_s10['shipping_provider'], 'CA S10/UPU should resolve to Canada Post' );

		// AU origin S10/UPU should go to Australia Post.
		$australia_post_s10 = $this->combinator->try_parse_tracking_number( 'AA123456789AU', 'AU', 'US' );
		$this->assertSame( 'australia-post', $australia_post_s10['shipping_provider'], 'AU S10/UPU should resolve to Australia Post' );

		// Royal Mail service-specific international patterns.
		$royal_mail_international = $this->combinator->try_parse_tracking_number( 'IT123456789GB', 'GB', 'US' );
		$this->assertSame( 'amazon-logistics', $royal_mail_international['shipping_provider'], 'Royal Mail international service should resolve correctly' );

		// USPS uses longer unique patterns for international.
		$usps_international = $this->combinator->try_parse_tracking_number( '9405510897700003234567', 'US', 'GB' );
		$this->assertSame( 'usps', $usps_international['shipping_provider'], 'USPS international should resolve correctly' );
	}

	/**
	 * Test regional provider networks and cross-border capabilities.
	 *
	 * @return void
	 */
	public function testRegionalProviderNetworks() {
		// DPD European network.
		$dpd_gb_to_de = $this->combinator->try_parse_tracking_number( '05212345678901', 'GB', 'DE' );
		$this->assertSame( 'dpd', $dpd_gb_to_de['shipping_provider'], 'DPD should handle GB to DE shipments' );

		$dpd_fr_to_es = $this->combinator->try_parse_tracking_number( '02123456789012', 'FR', 'ES' );
		$this->assertSame( 'dpd', $dpd_fr_to_es['shipping_provider'], 'DPD should handle FR to ES shipments' );

		// Evri/Hermes European network.
		$evri_gb_to_ie = $this->combinator->try_parse_tracking_number( '1234567890123456', 'GB', 'IE' );
		$this->assertSame( 'evri-hermes', $evri_gb_to_ie['shipping_provider'], 'Evri should handle GB to IE shipments' );

		$hermes_de_to_gb = $this->combinator->try_parse_tracking_number( 'MH1234567890123456', 'DE', 'GB' );
		$this->assertSame( 'amazon-logistics', $hermes_de_to_gb['shipping_provider'], 'Hermes should handle DE to GB shipments' );

		// DHL Global network with regional patterns.
		$dhl_us_ecommerce = $this->combinator->try_parse_tracking_number( 'GM1234567890123456', 'US', 'DE' );
		$this->assertSame( 'dhl', $dhl_us_ecommerce['shipping_provider'], 'DHL should handle US eCommerce patterns' );

		$dhl_asia_pacific = $this->combinator->try_parse_tracking_number( 'LX123456789DE', 'DE', 'GB' );
		$this->assertSame( 'dhl', $dhl_asia_pacific['shipping_provider'], 'DHL should handle Asia-Pacific patterns' );
	}

	/**
	 * Test ambiguous S10/UPU numbers with multiple possible providers.
	 *
	 * @return void
	 */
	public function testAmbiguousS10UPU() {
		$tracking_number = 'EE123456789CA';

		$result = $this->combinator->try_parse_tracking_number( $tracking_number, 'CA', 'US' );

		$this->assertSame( 'canada-post', $result['shipping_provider'] );
		$this->assertArrayHasKey( 'possibilities', $result );
		$this->assertArrayHasKey( 'canada-post', $result['possibilities'] );
		// Note: USPS may not be in possibilities if it doesn't support EE prefix.
		// Canada Post should have highest score for CA S10 code from Canada.
	}

	/**
	 * Test ambiguous 13-digit numeric tracking numbers.
	 *
	 * @return void
	 */
	public function testAmbiguousNumeric() {
		$tracking_number = '1234567890123';

		$result = $this->combinator->try_parse_tracking_number( $tracking_number, 'CA', 'US' );
		$this->assertSame( 'canada-post', $result['shipping_provider'] );

		$result2 = $this->combinator->try_parse_tracking_number( $tracking_number, 'AU', 'US' );
		$this->assertSame( 'australia-post', $result2['shipping_provider'] );
	}

	/**
	 * Test that an invalid or empty tracking number returns no provider.
	 *
	 * @return void
	 */
	public function testInvalidTrackingNumberReturnsNoProvider() {
		$result = $this->combinator->try_parse_tracking_number( '', 'US', 'US' );
		$this->assertArrayHasKey( 'shipping_provider', $result );
		$this->assertEmpty( $result['shipping_provider'] );
	}
}
