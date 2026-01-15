<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Suggestions;

use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentsExtensionSuggestionIncentives;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentsExtensionSuggestions;
use WC_Unit_Test_Case;

/**
 * PaymentsExtensionSuggestions provider test.
 *
 * @class PaymentsExtensionSuggestions
 */
class PaymentsExtensionSuggestionsTest extends WC_Unit_Test_Case {
	/**
	 * System under test.
	 *
	 * @var PaymentsExtensionSuggestions
	 */
	protected PaymentsExtensionSuggestions $sut;

	/**
	 * The suggestion incentives provider mock.
	 *
	 * @var PaymentsExtensionSuggestionIncentives
	 */
	protected $suggestion_incentives;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Mock the incentives provider class.
		$this->suggestion_incentives = $this->getMockBuilder( PaymentsExtensionSuggestionIncentives::class )->getMock();

		$this->sut = new PaymentsExtensionSuggestions();
		$this->sut->init( $this->suggestion_incentives );
	}

	/**
	 * Test getting payment extension suggestions by invalid country.
	 */
	public function test_get_country_extensions_invalid_country() {
		$extensions = $this->sut->get_country_extensions( 'XX' );
		$this->assertEmpty( $extensions );
	}

	/**
	 * Test getting payment extension suggestions by valid country.
	 */
	public function test_get_country_extensions_valid_country() {
		$extensions = $this->sut->get_country_extensions( 'US' );
		$this->assertNotEmpty( $extensions );
	}

	/**
	 * Test for each country that we can generate and have the proper number of suggestions when the merchant is selling online.
	 *
	 * This guards against misconfigurations in the data.
	 *
	 * @dataProvider data_provider_get_country_extensions_count_with_merchant_selling_online
	 *
	 * @param string $country        The country code.
	 * @param int    $expected_count The expected number of suggestions.
	 */
	public function test_get_country_extensions_count_with_merchant_selling_online( string $country, int $expected_count ) {
		// Merchant is selling online.
		// Arrange.
		update_option(
			OnboardingProfile::DATA_OPTION,
			array(
				'business_choice'       => 'im_already_selling',
				'selling_online_answer' => 'yes_im_selling_online',
			)
		);

		// Act.
		$extensions = $this->sut->get_country_extensions( $country );

		// Assert.
		$this->assertCount( $expected_count, $extensions, "For merchant selling online, the country $country should have $expected_count suggestions." );

		// Merchant skipped the profiler. We assume they are selling only online.
		// Arrange.
		update_option(
			OnboardingProfile::DATA_OPTION,
			array() // No data.
		);

		// Act.
		$extensions = $this->sut->get_country_extensions( $country );

		// Assert.
		$this->assertCount( $expected_count, $extensions, "For merchant who skipped the profiler, the country $country should have $expected_count suggestions." );

		// Merchant didn't answer the profiler questions fully. We assume they are selling only online.
		// Arrange.
		update_option(
			OnboardingProfile::DATA_OPTION,
			array(
				'business_choice'       => 'im_already_selling',
				'selling_online_answer' => '', // No answer.
			)
		);

		// Act.
		$extensions = $this->sut->get_country_extensions( $country );

		// Assert.
		$this->assertCount( $expected_count, $extensions, "Country $country should have $expected_count suggestions." );

		// Clean up.
		delete_option( OnboardingProfile::DATA_OPTION );
	}

	/**
	 * Data provider for test_get_country_extensions_count_with_merchant_selling_online.
	 *
	 * @return array
	 */
	public function data_provider_get_country_extensions_count_with_merchant_selling_online(): array {
		// The counts are based on the data in PaymentExtensionSuggestions::$country_extensions.
		$country_suggestions_count = array(
			'CA' => 10,
			'US' => 11,
			'GB' => 14,
			'AT' => 13,
			'BE' => 11,
			'BG' => 7,
			'HR' => 8,
			'CY' => 9,
			'CZ' => 8,
			'DK' => 12,
			'EE' => 7,
			'FI' => 11,
			'FO' => 3,
			'FR' => 12,
			'GI' => 4,
			'DE' => 13,
			'GR' => 8,
			'GL' => 3,
			'HU' => 9,
			'IE' => 11,
			'IT' => 10,
			'LV' => 6,
			'LI' => 5,
			'LT' => 7,
			'LU' => 8,
			'MT' => 7,
			'MD' => 3,
			'NL' => 10,
			'NO' => 9,
			'PL' => 9,
			'PT' => 10,
			'RO' => 8,
			'SM' => 3,
			'SK' => 7,
			'ES' => 12,
			'SE' => 10,
			'CH' => 8,
			'AG' => 5,
			'AI' => 3,
			'AR' => 4,
			'AW' => 3,
			'BS' => 5,
			'BB' => 5,
			'BZ' => 5,
			'BM' => 5,
			'BO' => 2,
			'BQ' => 3,
			'BR' => 5,
			'VG' => 3,
			'KY' => 5,
			'CL' => 4,
			'CO' => 4,
			'CR' => 5,
			'CW' => 3,
			'DM' => 5,
			'DO' => 5,
			'EC' => 4,
			'SV' => 5,
			'FK' => 2,
			'GF' => 4,
			'GD' => 5,
			'GP' => 4,
			'GT' => 5,
			'GY' => 3,
			'HN' => 5,
			'JM' => 5,
			'MQ' => 4,
			'MX' => 6,
			'NI' => 5,
			'PA' => 5,
			'PY' => 2,
			'PE' => 4,
			'KN' => 5,
			'LC' => 5,
			'SX' => 3,
			'VC' => 3,
			'SR' => 3,
			'TT' => 5,
			'TC' => 5,
			'UY' => 4,
			'VI' => 3,
			'VE' => 3,
			'AU' => 12,
			'BD' => 2,
			'CN' => 5,
			'FJ' => 3,
			'GU' => 1,
			'HK' => 8,
			'IN' => 7,
			'ID' => 4,
			'JP' => 7,
			'MY' => 5,
			'NC' => 3,
			'NZ' => 9,
			'PW' => 3,
			'PH' => 4,
			'SG' => 7,
			'LK' => 2,
			'KR' => 3,
			'TH' => 5,
			'VN' => 4,
			'DZ' => 3,
			'AO' => 1,
			'BJ' => 1,
			'BW' => 3,
			'BF' => 1,
			'BI' => 1,
			'CM' => 1,
			'CV' => 1,
			'CF' => 1,
			'TD' => 1,
			'KM' => 1,
			'CG' => 1,
			'CI' => 1,
			'EG' => 4,
			'CD' => 1,
			'DJ' => 1,
			'GQ' => 1,
			'ER' => 1,
			'SZ' => 3,
			'ET' => 1,
			'GA' => 1,
			'GH' => 2,
			'GM' => 1,
			'GN' => 1,
			'GW' => 1,
			'KE' => 3,
			'LS' => 3,
			'LR' => 1,
			'LY' => 1,
			'MG' => 1,
			'MW' => 3,
			'ML' => 1,
			'MR' => 1,
			'MU' => 3,
			'MA' => 4,
			'MZ' => 3,
			'NA' => 1,
			'NE' => 1,
			'NG' => 2,
			'RE' => 3,
			'RW' => 1,
			'ST' => 1,
			'SN' => 3,
			'SC' => 3,
			'SL' => 1,
			'SO' => 1,
			'ZA' => 5,
			'SS' => 1,
			'TZ' => 1,
			'TG' => 1,
			'TN' => 1,
			'UG' => 1,
			'EH' => 1,
			'ZM' => 1,
			'ZW' => 1,
			'BH' => 3,
			'IQ' => 1,
			'IL' => 2,
			'JO' => 4,
			'KW' => 3,
			'LB' => 1,
			'OM' => 4,
			'PK' => 3,
			'QA' => 3,
			'SA' => 5,
			'AE' => 8,
			'YE' => 1,
			'AD' => 3,
			'AF' => 1,
			'AL' => 2,
			'AM' => 1,
			'AQ' => 1,
			'AS' => 1,
			'AX' => 1,
			'AZ' => 1,
			'BA' => 2,
			'BL' => 2,
			'BN' => 1,
			'BT' => 1,
			'BV' => 1,
			'BY' => 1,
			'CC' => 1,
			'CK' => 1,
			'CU' => 1,
			'CX' => 1,
			'FM' => 1,
			'GE' => 2,
			'GG' => 1,
			'GS' => 1,
			'HM' => 1,
			'HT' => 1,
			'IM' => 1,
			'IO' => 1,
			'IR' => 0,
			'IS' => 3,
			'JE' => 1,
			'KG' => 1,
			'KH' => 1,
			'KI' => 1,
			'KZ' => 2,
			'LA' => 1,
			'MC' => 2,
			'ME' => 1,
			'MF' => 1,
			'MH' => 1,
			'MK' => 1,
			'MM' => 1,
			'MN' => 1,
			'MO' => 1,
			'MP' => 1,
			'MS' => 1,
			'MV' => 1,
			'NF' => 1,
			'NP' => 1,
			'NR' => 1,
			'NU' => 1,
			'PF' => 2,
			'PG' => 1,
			'PM' => 1,
			'PN' => 1,
			'PR' => 2,
			'PS' => 1,
			'RS' => 2,
			'RU' => 1,
			'SB' => 1,
			'SD' => 1,
			'SH' => 1,
			'SI' => 6,
			'SJ' => 1,
			'TF' => 1,
			'TJ' => 1,
			'TK' => 1,
			'TL' => 1,
			'TM' => 1,
			'TO' => 1,
			'TR' => 1,
			'TV' => 1,
			'TW' => 2,
			'UA' => 1,
			'UM' => 1,
			'UZ' => 1,
			'VA' => 1,
			'VU' => 1,
			'WF' => 1,
			'WS' => 1,
		);

		$data = array();
		foreach ( $country_suggestions_count as $country => $count ) {
			$data[] = array( $country, $count );
		}

		return $data;
	}

	/**
	 * Test for each country that we can generate and have the proper number of suggestions when the merchant is selling offline.
	 *
	 * This guards against misconfigurations in the data.
	 *
	 * @dataProvider data_provider_get_country_extensions_count_with_merchant_selling_offline
	 *
	 * @param string $country        The country code.
	 * @param int    $expected_count The expected number of suggestions.
	 */
	public function test_get_country_extensions_count_with_merchant_selling_offline( string $country, int $expected_count ) {
		// Merchant is selling offline.
		// Arrange.
		update_option(
			OnboardingProfile::DATA_OPTION,
			array(
				'business_choice'       => 'im_already_selling',
				'selling_online_answer' => 'no_im_selling_offline',
			)
		);

		// Act.
		$extensions = $this->sut->get_country_extensions( $country );

		// Assert.
		$this->assertCount( $expected_count, $extensions, "For merchant selling offline, the country $country should have $expected_count suggestions." );

		// Merchant is selling both online and offline.
		// Arrange.
		update_option(
			OnboardingProfile::DATA_OPTION,
			array(
				'business_choice'       => 'im_already_selling',
				'selling_online_answer' => 'im_selling_both_online_and_offline',
			)
		);

		// Act.
		$extensions = $this->sut->get_country_extensions( $country );

		// Assert.
		$this->assertCount( $expected_count, $extensions, "For merchant selling both online and offline, the country $country should have $expected_count suggestions." );

		// Clean up.
		delete_option( OnboardingProfile::DATA_OPTION );
	}

	/**
	 * Data provider for test_get_country_extensions_count_with_merchant_selling_offline.
	 *
	 * @return array
	 */
	public function data_provider_get_country_extensions_count_with_merchant_selling_offline(): array {
		// The counts are based on the data in PaymentExtensionSuggestions::$country_extensions.
		$country_suggestions_count = array(
			'CA' => 10,
			'US' => 11,
			'GB' => 14,
			'AT' => 13,
			'BE' => 11,
			'BG' => 7,
			'HR' => 8,
			'CY' => 9,
			'CZ' => 8,
			'DK' => 12,
			'EE' => 7,
			'FI' => 11,
			'FO' => 3,
			'FR' => 12,
			'GI' => 4,
			'DE' => 13,
			'GR' => 8,
			'GL' => 3,
			'HU' => 9,
			'IE' => 11,
			'IT' => 10,
			'LV' => 6,
			'LI' => 5,
			'LT' => 7,
			'LU' => 8,
			'MT' => 7,
			'MD' => 3,
			'NL' => 10,
			'NO' => 9,
			'PL' => 9,
			'PT' => 10,
			'RO' => 8,
			'SM' => 3,
			'SK' => 7,
			'ES' => 12,
			'SE' => 10,
			'CH' => 8,
			'AG' => 5,
			'AI' => 3,
			'AR' => 4,
			'AW' => 3,
			'BS' => 5,
			'BB' => 5,
			'BZ' => 5,
			'BM' => 5,
			'BO' => 2,
			'BQ' => 3,
			'BR' => 5,
			'VG' => 3,
			'KY' => 5,
			'CL' => 4,
			'CO' => 4,
			'CR' => 5,
			'CW' => 3,
			'DM' => 5,
			'DO' => 5,
			'EC' => 4,
			'SV' => 5,
			'FK' => 2,
			'GF' => 4,
			'GD' => 5,
			'GP' => 4,
			'GT' => 5,
			'GY' => 3,
			'HN' => 5,
			'JM' => 5,
			'MQ' => 4,
			'MX' => 6,
			'NI' => 5,
			'PA' => 5,
			'PY' => 2,
			'PE' => 4,
			'KN' => 5,
			'LC' => 5,
			'SX' => 3,
			'VC' => 3,
			'SR' => 3,
			'TT' => 5,
			'TC' => 5,
			'UY' => 4,
			'VI' => 3,
			'VE' => 3,
			'AU' => 12,
			'BD' => 2,
			'CN' => 5,
			'FJ' => 3,
			'GU' => 1,
			'HK' => 8,
			'IN' => 7,
			'ID' => 4,
			'JP' => 7,
			'MY' => 5,
			'NC' => 3,
			'NZ' => 9,
			'PW' => 3,
			'PH' => 4,
			'SG' => 7,
			'LK' => 2,
			'KR' => 3,
			'TH' => 5,
			'VN' => 4,
			'DZ' => 3,
			'AO' => 1,
			'BJ' => 1,
			'BW' => 3,
			'BF' => 1,
			'BI' => 1,
			'CM' => 1,
			'CV' => 1,
			'CF' => 1,
			'TD' => 1,
			'KM' => 1,
			'CG' => 1,
			'CI' => 1,
			'EG' => 4,
			'CD' => 1,
			'DJ' => 1,
			'GQ' => 1,
			'ER' => 1,
			'SZ' => 3,
			'ET' => 1,
			'GA' => 1,
			'GH' => 2,
			'GM' => 1,
			'GN' => 1,
			'GW' => 1,
			'KE' => 3,
			'LS' => 3,
			'LR' => 1,
			'LY' => 1,
			'MG' => 1,
			'MW' => 3,
			'ML' => 1,
			'MR' => 1,
			'MU' => 3,
			'MA' => 4,
			'MZ' => 3,
			'NA' => 1,
			'NE' => 1,
			'NG' => 2,
			'RE' => 3,
			'RW' => 1,
			'ST' => 1,
			'SN' => 3,
			'SC' => 3,
			'SL' => 1,
			'SO' => 1,
			'ZA' => 5,
			'SS' => 1,
			'TZ' => 1,
			'TG' => 1,
			'TN' => 1,
			'UG' => 1,
			'EH' => 1,
			'ZM' => 1,
			'ZW' => 1,
			'BH' => 3,
			'IQ' => 1,
			'IL' => 2,
			'JO' => 4,
			'KW' => 3,
			'LB' => 1,
			'OM' => 4,
			'PK' => 3,
			'QA' => 3,
			'SA' => 5,
			'AE' => 8,
			'YE' => 1,
			'AD' => 3,
			'AF' => 1,
			'AL' => 2,
			'AM' => 1,
			'AQ' => 1,
			'AS' => 1,
			'AX' => 1,
			'AZ' => 1,
			'BA' => 2,
			'BL' => 2,
			'BN' => 1,
			'BT' => 1,
			'BV' => 1,
			'BY' => 1,
			'CC' => 1,
			'CK' => 1,
			'CU' => 1,
			'CX' => 1,
			'FM' => 1,
			'GE' => 2,
			'GG' => 1,
			'GS' => 1,
			'HM' => 1,
			'HT' => 1,
			'IM' => 1,
			'IO' => 1,
			'IR' => 0,
			'IS' => 3,
			'JE' => 1,
			'KG' => 1,
			'KH' => 1,
			'KI' => 1,
			'KZ' => 2,
			'LA' => 1,
			'MC' => 2,
			'ME' => 1,
			'MF' => 1,
			'MH' => 1,
			'MK' => 1,
			'MM' => 1,
			'MN' => 1,
			'MO' => 1,
			'MP' => 1,
			'MS' => 1,
			'MV' => 1,
			'NF' => 1,
			'NP' => 1,
			'NR' => 1,
			'NU' => 1,
			'PF' => 2,
			'PG' => 1,
			'PM' => 1,
			'PN' => 1,
			'PR' => 2,
			'PS' => 1,
			'RS' => 2,
			'RU' => 1,
			'SB' => 1,
			'SD' => 1,
			'SH' => 1,
			'SI' => 6,
			'SJ' => 1,
			'TF' => 1,
			'TJ' => 1,
			'TK' => 1,
			'TL' => 1,
			'TM' => 1,
			'TO' => 1,
			'TR' => 1,
			'TV' => 1,
			'TW' => 2,
			'UA' => 1,
			'UM' => 1,
			'UZ' => 1,
			'VA' => 1,
			'VU' => 1,
			'WF' => 1,
			'WS' => 1,
		);

		$data = array();
		foreach ( $country_suggestions_count as $country => $count ) {
			$data[] = array( $country, $count );
		}

		return $data;
	}

	/**
	 * Test getting payment extension suggestions by country with per-country config that uses merges.
	 */
	public function test_get_country_extensions_with_per_country_merges() {
		// Act.
		$extensions = $this->sut->get_country_extensions( 'MX' );

		// Assert.
		$this->assertCount( 6, $extensions );
		$this->assertSame(
			array(
				PaymentsExtensionSuggestions::STRIPE,
				PaymentsExtensionSuggestions::PAYPAL_FULL_STACK,
				PaymentsExtensionSuggestions::VISA,
				PaymentsExtensionSuggestions::PAYPAL_WALLET,
				PaymentsExtensionSuggestions::KLARNA,
				PaymentsExtensionSuggestions::HELIOPAY,
			),
			array_column( $extensions, 'id' )
		);

		$stripe = $extensions[0];
		// It should have the preferred tag.
		$this->assertContains( PaymentsExtensionSuggestions::TAG_PREFERRED, $stripe['tags'] );

		$klarna = $extensions[4];
		// The links should be the expected ones.
		$this->assertEqualsCanonicalizing(
			array(
				// These are coming from the per-country details.
				array(
					'_type' => PaymentsProviders::LINK_TYPE_PRICING,
					'url'   => 'https://www.klarna.com/mx/negocios/',
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_TERMS,
					'url'   => 'https://www.klarna.com/mx/terminos-y-condiciones/',
				),
				// These are base details for the suggestion.
				array(
					'_type' => PaymentsProviders::LINK_TYPE_ABOUT,
					'url'   => 'https://woocommerce.com/products/klarna-payments/',
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_DOCS,
					'url'   => 'https://woocommerce.com/document/klarna-payments/',
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_SUPPORT,
					'url'   => 'https://woocommerce.com/my-account/contact-support/?select=klarna-payments',
				),
			),
			$klarna['links']
		);
	}

	/**
	 * Test getting payment extension suggestions by ID.
	 */
	public function test_get_extension_by_id() {
		$extension = $this->sut->get_by_id( 'woopayments' );
		$this->assertNotEmpty( $extension );
		$this->assertIsArray( $extension );
		$this->assertArrayHasKey( 'id', $extension );
		$this->assertSame( 'woopayments', $extension['id'] );
	}

	/**
	 * Test getting payment extension suggestions by ID with invalid ID.
	 */
	public function test_get_extension_by_id_with_invalid_id() {
		$extension = $this->sut->get_by_id( 'bogus_id' );
		$this->assertNull( $extension );
	}

	/**
	 * Test getting payment extension suggestions by plugin slug.
	 */
	public function test_get_extension_by_plugin_slug() {
		$extension = $this->sut->get_by_plugin_slug( 'woocommerce-payments' );
		$this->assertNotEmpty( $extension );
		$this->assertIsArray( $extension );
		$this->assertArrayHasKey( 'id', $extension );
		$this->assertSame( 'woopayments', $extension['id'] );
	}

	/**
	 * Test getting payment extension suggestions by plugin slug with invalid slug.
	 */
	public function test_get_extension_by_plugin_slug_with_invalid_slug() {
		$extension = $this->sut->get_by_plugin_slug( 'bogus_slug' );
		$this->assertNull( $extension );
	}
}
