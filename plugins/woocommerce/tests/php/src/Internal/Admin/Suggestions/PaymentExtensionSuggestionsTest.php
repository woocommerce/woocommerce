<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Suggestions;

use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentExtensionSuggestionIncentives;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentExtensionSuggestions;
use WC_Unit_Test_Case;

/**
 * PaymentExtensionSuggestions provider test.
 *
 * @class PaymentExtensionSuggestions
 */
class PaymentExtensionSuggestionsTest extends WC_Unit_Test_Case {
	/**
	 * System under test.
	 *
	 * @var PaymentExtensionSuggestions
	 */
	protected PaymentExtensionSuggestions $sut;

	/**
	 * The suggestion incentives provider mock.
	 *
	 * @var PaymentExtensionSuggestionIncentives
	 */
	protected $suggestion_incentives;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Mock the incentives provider class.
		$this->suggestion_incentives = $this->getMockBuilder( PaymentExtensionSuggestionIncentives::class )->getMock();

		$this->sut = new PaymentExtensionSuggestions();
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
			'CA' => 8,
			'US' => 10,
			'GB' => 11,
			'AT' => 9,
			'BE' => 9,
			'BG' => 6,
			'HR' => 6,
			'CY' => 7,
			'CZ' => 7,
			'DK' => 8,
			'EE' => 5,
			'FI' => 7,
			'FO' => 2,
			'FR' => 10,
			'GI' => 3,
			'DE' => 9,
			'GR' => 7,
			'GL' => 2,
			'HU' => 8,
			'IE' => 10,
			'IT' => 9,
			'LV' => 5,
			'LI' => 4,
			'LT' => 6,
			'LU' => 7,
			'MT' => 6,
			'MD' => 2,
			'NL' => 8,
			'NO' => 6,
			'PL' => 8,
			'PT' => 9,
			'RO' => 7,
			'SM' => 2,
			'SK' => 6,
			'ES' => 11,
			'SE' => 7,
			'CH' => 7,
			'AG' => 4,
			'AI' => 2,
			'AR' => 4,
			'AW' => 2,
			'BS' => 4,
			'BB' => 4,
			'BZ' => 4,
			'BM' => 4,
			'BO' => 1,
			'BQ' => 2,
			'BR' => 5,
			'VG' => 2,
			'KY' => 4,
			'CL' => 4,
			'CO' => 4,
			'CR' => 4,
			'CW' => 2,
			'DM' => 4,
			'DO' => 4,
			'EC' => 3,
			'SV' => 4,
			'FK' => 1,
			'GF' => 3,
			'GD' => 4,
			'GP' => 3,
			'GT' => 4,
			'GY' => 2,
			'HN' => 4,
			'JM' => 4,
			'MQ' => 3,
			'MX' => 6,
			'NI' => 4,
			'PA' => 4,
			'PY' => 1,
			'PE' => 4,
			'KN' => 4,
			'LC' => 4,
			'SX' => 2,
			'VC' => 2,
			'SR' => 2,
			'TT' => 4,
			'TC' => 4,
			'UY' => 4,
			'VI' => 2,
			'VE' => 3,
			'AU' => 9,
			'BD' => 1,
			'CN' => 4,
			'FJ' => 2,
			'GU' => 0,
			'HK' => 7,
			'IN' => 6,
			'ID' => 4,
			'JP' => 7,
			'MY' => 5,
			'NC' => 2,
			'NZ' => 7,
			'PW' => 2,
			'PH' => 4,
			'SG' => 6,
			'LK' => 1,
			'KR' => 2,
			'TH' => 5,
			'VN' => 4,
			'DZ' => 2,
			'AO' => 0,
			'BJ' => 0,
			'BW' => 2,
			'BF' => 0,
			'BI' => 0,
			'CM' => 0,
			'CV' => 0,
			'CF' => 0,
			'TD' => 0,
			'KM' => 0,
			'CG' => 0,
			'CI' => 0,
			'EG' => 3,
			'CD' => 0,
			'DJ' => 0,
			'GQ' => 0,
			'ER' => 0,
			'SZ' => 2,
			'ET' => 0,
			'GA' => 0,
			'GH' => 1,
			'GM' => 0,
			'GN' => 0,
			'GW' => 0,
			'KE' => 2,
			'LS' => 2,
			'LR' => 0,
			'LY' => 0,
			'MG' => 0,
			'MW' => 2,
			'ML' => 0,
			'MR' => 0,
			'MU' => 2,
			'MA' => 3,
			'MZ' => 2,
			'NA' => 0,
			'NE' => 0,
			'NG' => 1,
			'RE' => 2,
			'RW' => 0,
			'ST' => 0,
			'SN' => 2,
			'SC' => 2,
			'SL' => 0,
			'SO' => 0,
			'ZA' => 4,
			'SS' => 0,
			'TZ' => 0,
			'TG' => 0,
			'TN' => 0,
			'UG' => 0,
			'EH' => 0,
			'ZM' => 0,
			'ZW' => 0,
			'BH' => 2,
			'IQ' => 0,
			'IL' => 1,
			'JO' => 2,
			'KW' => 2,
			'LB' => 0,
			'OM' => 3,
			'PK' => 2,
			'QA' => 2,
			'SA' => 3,
			'AE' => 6,
			'YE' => 0,
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
			'CA' => 8,
			'US' => 10,
			'GB' => 11,
			'AT' => 9,
			'BE' => 9,
			'BG' => 6,
			'HR' => 6,
			'CY' => 7,
			'CZ' => 7,
			'DK' => 8,
			'EE' => 5,
			'FI' => 7,
			'FO' => 2,
			'FR' => 10,
			'GI' => 3,
			'DE' => 9,
			'GR' => 7,
			'GL' => 2,
			'HU' => 8,
			'IE' => 10,
			'IT' => 9,
			'LV' => 5,
			'LI' => 4,
			'LT' => 6,
			'LU' => 7,
			'MT' => 6,
			'MD' => 2,
			'NL' => 8,
			'NO' => 6,
			'PL' => 8,
			'PT' => 9,
			'RO' => 7,
			'SM' => 2,
			'SK' => 6,
			'ES' => 11,
			'SE' => 7,
			'CH' => 7,
			'AG' => 4,
			'AI' => 2,
			'AR' => 4,
			'AW' => 2,
			'BS' => 4,
			'BB' => 4,
			'BZ' => 4,
			'BM' => 4,
			'BO' => 1,
			'BQ' => 2,
			'BR' => 5,
			'VG' => 2,
			'KY' => 4,
			'CL' => 4,
			'CO' => 4,
			'CR' => 4,
			'CW' => 2,
			'DM' => 4,
			'DO' => 4,
			'EC' => 3,
			'SV' => 4,
			'FK' => 1,
			'GF' => 3,
			'GD' => 4,
			'GP' => 3,
			'GT' => 4,
			'GY' => 2,
			'HN' => 4,
			'JM' => 4,
			'MQ' => 3,
			'MX' => 6,
			'NI' => 4,
			'PA' => 4,
			'PY' => 1,
			'PE' => 4,
			'KN' => 4,
			'LC' => 4,
			'SX' => 2,
			'VC' => 2,
			'SR' => 2,
			'TT' => 4,
			'TC' => 4,
			'UY' => 4,
			'VI' => 2,
			'VE' => 3,
			'AU' => 9,
			'BD' => 1,
			'CN' => 4,
			'FJ' => 2,
			'GU' => 0,
			'HK' => 7,
			'IN' => 6,
			'ID' => 4,
			'JP' => 7,
			'MY' => 5,
			'NC' => 2,
			'NZ' => 7,
			'PW' => 2,
			'PH' => 4,
			'SG' => 6,
			'LK' => 1,
			'KR' => 2,
			'TH' => 5,
			'VN' => 4,
			'DZ' => 2,
			'AO' => 0,
			'BJ' => 0,
			'BW' => 2,
			'BF' => 0,
			'BI' => 0,
			'CM' => 0,
			'CV' => 0,
			'CF' => 0,
			'TD' => 0,
			'KM' => 0,
			'CG' => 0,
			'CI' => 0,
			'EG' => 3,
			'CD' => 0,
			'DJ' => 0,
			'GQ' => 0,
			'ER' => 0,
			'SZ' => 2,
			'ET' => 0,
			'GA' => 0,
			'GH' => 1,
			'GM' => 0,
			'GN' => 0,
			'GW' => 0,
			'KE' => 2,
			'LS' => 2,
			'LR' => 0,
			'LY' => 0,
			'MG' => 0,
			'MW' => 2,
			'ML' => 0,
			'MR' => 0,
			'MU' => 2,
			'MA' => 3,
			'MZ' => 2,
			'NA' => 0,
			'NE' => 0,
			'NG' => 1,
			'RE' => 2,
			'RW' => 0,
			'ST' => 0,
			'SN' => 2,
			'SC' => 2,
			'SL' => 0,
			'SO' => 0,
			'ZA' => 4,
			'SS' => 0,
			'TZ' => 0,
			'TG' => 0,
			'TN' => 0,
			'UG' => 0,
			'EH' => 0,
			'ZM' => 0,
			'ZW' => 0,
			'BH' => 2,
			'IQ' => 0,
			'IL' => 1,
			'JO' => 2,
			'KW' => 2,
			'LB' => 0,
			'OM' => 3,
			'PK' => 2,
			'QA' => 2,
			'SA' => 3,
			'AE' => 6,
			'YE' => 0,
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
				PaymentExtensionSuggestions::STRIPE,
				PaymentExtensionSuggestions::PAYPAL_FULL_STACK,
				PaymentExtensionSuggestions::MERCADO_PAGO,
				PaymentExtensionSuggestions::PAYPAL_WALLET,
				PaymentExtensionSuggestions::KLARNA,
				PaymentExtensionSuggestions::HELIOPAY,
			),
			array_column( $extensions, 'id' )
		);

		$stripe = $extensions[0];
		// It should have the preferred tag.
		$this->assertContains( PaymentExtensionSuggestions::TAG_PREFERRED, $stripe['tags'] );

		$mercado_pago = $extensions[2];
		// The links should be the expected ones.
		$this->assertEqualsCanonicalizing(
			array(
				// These are coming from the per-country details.
				array(
					'_type' => PaymentExtensionSuggestions::LINK_TYPE_PRICING,
					'url'   => 'https://www.mercadopago.com.mx/costs-section',
				),
				array(
					'_type' => PaymentExtensionSuggestions::LINK_TYPE_TERMS,
					'url'   => 'https://www.mercadopago.com.mx/ayuda/terminos-y-politicas_194',
				),
				// These are base details for the suggestion.
				array(
					'_type' => PaymentExtensionSuggestions::LINK_TYPE_ABOUT,
					'url'   => 'https://woocommerce.com/products/mercado-pago-checkout/',
				),
				array(
					'_type' => PaymentExtensionSuggestions::LINK_TYPE_DOCS,
					'url'   => 'https://woocommerce.com/document/mercado-pago/',
				),
				array(
					'_type' => PaymentExtensionSuggestions::LINK_TYPE_SUPPORT,
					'url'   => 'https://woocommerce.com/my-account/contact-support/?select=mercado-pago-checkout',
				),
			),
			$mercado_pago['links']
		);
		// It should not have the preferred tag.
		$this->assertNotContains( PaymentExtensionSuggestions::TAG_PREFERRED, $mercado_pago['tags'] );

		$klarna = $extensions[4];
		// The links should be the expected ones.
		$this->assertEqualsCanonicalizing(
			array(
				// These are coming from the per-country details.
				array(
					'_type' => PaymentExtensionSuggestions::LINK_TYPE_PRICING,
					'url'   => 'https://www.klarna.com/mx/negocios/',
				),
				array(
					'_type' => PaymentExtensionSuggestions::LINK_TYPE_TERMS,
					'url'   => 'https://www.klarna.com/mx/terminos-y-condiciones/',
				),
				// These are base details for the suggestion.
				array(
					'_type' => PaymentExtensionSuggestions::LINK_TYPE_ABOUT,
					'url'   => 'https://woocommerce.com/products/klarna-payments/',
				),
				array(
					'_type' => PaymentExtensionSuggestions::LINK_TYPE_DOCS,
					'url'   => 'https://woocommerce.com/document/klarna-payments/',
				),
				array(
					'_type' => PaymentExtensionSuggestions::LINK_TYPE_SUPPORT,
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
