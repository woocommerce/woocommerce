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
	 * @testdox Should tag Square as preferred (and preferred for offline) only when the merchant self-identified as selling offline.
	 *
	 * @dataProvider data_provider_square_offline_preferred_tags
	 *
	 * @param array|null $onboarding_profile     The onboarding profile option value. Null to simulate a skipped profiler.
	 * @param bool       $expect_offline_preferred Whether Square is expected to carry the preferred (offline) tags.
	 */
	public function test_get_country_extensions_square_offline_preferred_tags( ?array $onboarding_profile, bool $expect_offline_preferred ) {
		// Arrange.
		if ( null === $onboarding_profile ) {
			delete_option( OnboardingProfile::DATA_OPTION );
		} else {
			update_option( OnboardingProfile::DATA_OPTION, $onboarding_profile );
		}

		// Act.
		$extensions   = $this->sut->get_country_extensions( 'US' );
		$square_index = array_search( PaymentsExtensionSuggestions::SQUARE, array_column( $extensions, 'id' ), true );
		$this->assertNotFalse( $square_index, 'Square should be in the US suggestions.' );
		$square = $extensions[ $square_index ];

		// Assert.
		if ( $expect_offline_preferred ) {
			$this->assertContains( PaymentsExtensionSuggestions::TAG_PREFERRED, $square['tags'] );
			$this->assertContains( PaymentsExtensionSuggestions::TAG_PREFERRED_OFFLINE, $square['tags'] );
		} else {
			$this->assertNotContains( PaymentsExtensionSuggestions::TAG_PREFERRED, $square['tags'] );
			$this->assertNotContains( PaymentsExtensionSuggestions::TAG_PREFERRED_OFFLINE, $square['tags'] );
		}

		delete_option( OnboardingProfile::DATA_OPTION );
	}

	/**
	 * Data provider for test_get_country_extensions_square_offline_preferred_tags.
	 *
	 * @return array
	 */
	public function data_provider_square_offline_preferred_tags(): array {
		return array(
			'selling offline only'              => array(
				array(
					'business_choice'       => 'im_already_selling',
					'selling_online_answer' => 'no_im_selling_offline',
				),
				true,
			),
			'selling both online and offline'   => array(
				array(
					'business_choice'       => 'im_already_selling',
					'selling_online_answer' => 'im_selling_both_online_and_offline',
				),
				true,
			),
			'selling online only'               => array(
				array(
					'business_choice'       => 'im_already_selling',
					'selling_online_answer' => 'yes_im_selling_online',
				),
				false,
			),
			'not already selling'               => array(
				array(
					'business_choice'       => 'im_just_starting_my_business',
					'selling_online_answer' => 'no_im_selling_offline',
				),
				false,
			),
			'already selling, no online answer' => array(
				array(
					'business_choice' => 'im_already_selling',
				),
				false,
			),
			'profiler skipped'                  => array(
				null,
				false,
			),
		);
	}

	/**
	 * Test getting payment extension suggestions by country with per-country config that uses merges.
	 */
	public function test_get_country_extensions_with_per_country_merges() {
		// Act.
		$extensions = $this->sut->get_country_extensions( 'MX' );

		// Assert.
		$this->assertCount( 7, $extensions );
		$this->assertSame(
			array(
				PaymentsExtensionSuggestions::STRIPE,
				PaymentsExtensionSuggestions::MERCADO_PAGO,
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

		$mercado_pago = $extensions[1];
		// The links should include the country-specific Mercado Pago URLs merged with the base details.
		$this->assertEqualsCanonicalizing(
			array(
				// These are coming from the per-country details.
				array(
					'_type' => PaymentsProviders::LINK_TYPE_PRICING,
					'url'   => 'https://www.mercadopago.com.mx/costs-section',
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_TERMS,
					'url'   => 'https://www.mercadopago.com.mx/ayuda/terminos-y-politicas_194',
				),
				// These are base details for the suggestion.
				array(
					'_type' => PaymentsProviders::LINK_TYPE_ABOUT,
					'url'   => 'https://woocommerce.com/products/mercado-pago-checkout/',
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_DOCS,
					'url'   => 'https://woocommerce.com/document/mercado-pago/',
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_SUPPORT,
					'url'   => 'https://woocommerce.com/my-account/contact-support/?select=mercado-pago-checkout',
				),
			),
			$mercado_pago['links']
		);

		$klarna = $extensions[5];
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
	 * Test that Mercado Pago is the preferred provider, Visa is demoted, and the country-localized links are merged
	 * across the 5 LATAM markets where Mercado Pago has primary placement (AR, CL, CO, PE, UY).
	 *
	 * @dataProvider data_provider_mercado_pago_preferred_markets
	 *
	 * @param string $country_code    ISO 3166-1 alpha-2 country code.
	 * @param string $mercadopago_tld TLD path for the country (e.g. `com.ar`, `cl`).
	 */
	public function test_get_country_extensions_with_mercado_pago_as_preferred_provider( string $country_code, string $mercadopago_tld ) {
		// Act.
		$extensions = $this->sut->get_country_extensions( $country_code );

		// Assert ordering: Mercado Pago first, Visa demoted into the "other payment options" group.
		$this->assertSame(
			array(
				PaymentsExtensionSuggestions::MERCADO_PAGO,
				PaymentsExtensionSuggestions::PAYPAL_FULL_STACK,
				PaymentsExtensionSuggestions::VISA,
				PaymentsExtensionSuggestions::PAYPAL_WALLET,
				PaymentsExtensionSuggestions::HELIOPAY,
			),
			array_column( $extensions, 'id' ),
			"Mercado Pago should be the first suggestion in {$country_code}, with Visa demoted."
		);

		$mercado_pago = $extensions[0];
		$this->assertContains(
			PaymentsExtensionSuggestions::TAG_PREFERRED,
			$mercado_pago['tags'],
			"Mercado Pago should carry the preferred tag in {$country_code}."
		);

		$visa = $extensions[2];
		$this->assertNotContains(
			PaymentsExtensionSuggestions::TAG_PREFERRED,
			$visa['tags'],
			"Visa should not carry the preferred tag in {$country_code}."
		);

		// The country-localized PRICING/TERMS links should be merged on top of the base ABOUT/DOCS/SUPPORT links.
		$this->assertEqualsCanonicalizing(
			array(
				// These are coming from the per-country details.
				array(
					'_type' => PaymentsProviders::LINK_TYPE_PRICING,
					'url'   => "https://www.mercadopago.{$mercadopago_tld}/costs-section",
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_TERMS,
					'url'   => "https://www.mercadopago.{$mercadopago_tld}/ayuda/terminos-y-politicas_194",
				),
				// These are base details for the suggestion.
				array(
					'_type' => PaymentsProviders::LINK_TYPE_ABOUT,
					'url'   => 'https://woocommerce.com/products/mercado-pago-checkout/',
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_DOCS,
					'url'   => 'https://woocommerce.com/document/mercado-pago/',
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_SUPPORT,
					'url'   => 'https://woocommerce.com/my-account/contact-support/?select=mercado-pago-checkout',
				),
			),
			$mercado_pago['links']
		);
	}

	/**
	 * Data provider for the markets where Mercado Pago is the preferred provider.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function data_provider_mercado_pago_preferred_markets(): array {
		return array(
			'Argentina' => array( 'AR', 'com.ar' ),
			'Chile'     => array( 'CL', 'cl' ),
			'Colombia'  => array( 'CO', 'com.co' ),
			'Peru'      => array( 'PE', 'com.pe' ),
			'Uruguay'   => array( 'UY', 'com.uy' ),
		);
	}

	/**
	 * Test that in Brazil, Stripe stays preferred and Mercado Pago is the first entry in "other payment options"
	 * with its Portuguese-localized links merged on top of the base details.
	 */
	public function test_get_country_extensions_with_mercado_pago_in_other_options_for_br() {
		// Act.
		$extensions = $this->sut->get_country_extensions( 'BR' );

		// Assert ordering: Stripe preferred, Mercado Pago at index 1.
		$this->assertSame(
			array(
				PaymentsExtensionSuggestions::STRIPE,
				PaymentsExtensionSuggestions::MERCADO_PAGO,
				PaymentsExtensionSuggestions::PAYPAL_FULL_STACK,
				PaymentsExtensionSuggestions::VISA,
				PaymentsExtensionSuggestions::PAYPAL_WALLET,
				PaymentsExtensionSuggestions::HELIOPAY,
			),
			array_column( $extensions, 'id' )
		);

		$stripe = $extensions[0];
		$this->assertContains( PaymentsExtensionSuggestions::TAG_PREFERRED, $stripe['tags'] );

		// Mercado Pago should NOT carry the preferred tag in Brazil — Stripe stays primary per the 10.8 fallback.
		$mercado_pago = $extensions[1];
		$this->assertNotContains( PaymentsExtensionSuggestions::TAG_PREFERRED, $mercado_pago['tags'] );

		// BR uses Portuguese paths (`ajuda/termos-e-politicas`) vs. Spanish (`ayuda/terminos-y-politicas`) in the other markets.
		$this->assertEqualsCanonicalizing(
			array(
				// These are coming from the per-country details.
				array(
					'_type' => PaymentsProviders::LINK_TYPE_PRICING,
					'url'   => 'https://www.mercadopago.com.br/costs-section',
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_TERMS,
					'url'   => 'https://www.mercadopago.com.br/ajuda/termos-e-politicas_194',
				),
				// These are base details for the suggestion.
				array(
					'_type' => PaymentsProviders::LINK_TYPE_ABOUT,
					'url'   => 'https://woocommerce.com/products/mercado-pago-checkout/',
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_DOCS,
					'url'   => 'https://woocommerce.com/document/mercado-pago/',
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_SUPPORT,
					'url'   => 'https://woocommerce.com/my-account/contact-support/?select=mercado-pago-checkout',
				),
			),
			$mercado_pago['links']
		);
	}

	/**
	 * @testdox Helcim has complete base suggestion details.
	 */
	public function test_helcim_has_complete_base_details(): void {
		$extension = $this->sut->get_by_id( 'helcim' );

		$this->assertIsArray( $extension );
		if ( ! is_array( $extension ) ) {
			return;
		}

		$this->assertSame( PaymentsExtensionSuggestions::TYPE_PSP, $extension['_type'] );
		$this->assertSame(
			array(
				'_type' => PaymentsExtensionSuggestions::PLUGIN_TYPE_WPORG,
				'slug'  => 'helcim-commerce-for-woocommerce',
			),
			$extension['plugin']
		);
		$this->assertEqualsCanonicalizing(
			array(
				array(
					'_type' => PaymentsProviders::LINK_TYPE_PRICING,
					'url'   => 'https://www.helcim.com/pricing/',
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_ABOUT,
					'url'   => 'https://woocommerce.com/products/helcim-commerce-for-woocommerce/',
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_TERMS,
					'url'   => 'https://legal.helcim.com/terms-of-service/',
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_DOCS,
					'url'   => 'https://woocommerce.com/document/helcim-commerce-for-woocommerce/',
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_SUPPORT,
					'url'   => 'https://woocommerce.com/my-account/contact-support/?select=helcim-commerce-for-woocommerce',
				),
			),
			$extension['links']
		);
		$this->assertNotEmpty( $extension['icon'] );
		$this->assertNotEmpty( $extension['title'] );
		$this->assertNotEmpty( $extension['description'] );
	}

	/**
	 * @testdox KOMOJU has complete base suggestion details.
	 */
	public function test_komoju_has_complete_base_details(): void {
		$extension = $this->sut->get_by_id( 'komoju' );

		$this->assertIsArray( $extension );
		if ( ! is_array( $extension ) ) {
			return;
		}

		$this->assertSame( PaymentsExtensionSuggestions::TYPE_PSP, $extension['_type'] );
		$this->assertSame(
			array(
				'_type' => PaymentsExtensionSuggestions::PLUGIN_TYPE_WPORG,
				'slug'  => 'komoju-japanese-payments',
			),
			$extension['plugin']
		);
		$this->assertEqualsCanonicalizing(
			array(
				array(
					'_type' => PaymentsProviders::LINK_TYPE_PRICING,
					'url'   => 'https://en.komoju.com/pricing/',
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_ABOUT,
					'url'   => 'https://woocommerce.com/products/komoju-japanese-payments/',
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_TERMS,
					'url'   => 'https://toc.komoju.com/toc/',
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_DOCS,
					'url'   => 'https://woocommerce.com/document/komoju-japanese-payments/',
				),
				array(
					'_type' => PaymentsProviders::LINK_TYPE_SUPPORT,
					'url'   => 'https://woocommerce.com/my-account/contact-support/?select=komoju-japanese-payments',
				),
			),
			$extension['links']
		);
		$this->assertNotEmpty( $extension['icon'] );
		$this->assertNotEmpty( $extension['title'] );
		$this->assertNotEmpty( $extension['description'] );
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
