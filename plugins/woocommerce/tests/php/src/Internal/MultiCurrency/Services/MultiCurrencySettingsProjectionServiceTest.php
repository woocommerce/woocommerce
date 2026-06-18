<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySettingsProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencySettingsProjectionService class.
 */
class MultiCurrencySettingsProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should project connected settings page manifest.
	 */
	public function test_projects_connected_settings_page_manifest(): void {
		$manifest = MultiCurrencySettingsProjectionService::get_settings_page_manifest(
			true,
			false,
			false,
			false
		);

		$this->assertSame( 'wcpay_multi_currency', $manifest['id'] );
		$this->assertSame( 'Multi-currency', $manifest['label'] );
		$this->assertSame( 'settings', $manifest['mode'] );
		$this->assertTrue( $manifest['hide_save_button'] );
		$this->assertSame(
			array(
				array(
					'type' => 'wcpay_multi_currency_settings_page',
				),
			),
			$manifest['settings']
		);
	}

	/**
	 * @testdox Should omit boot contexts that must not instantiate settings pages.
	 */
	public function test_omits_boot_contexts_that_must_not_instantiate_settings_pages(): void {
		$this->assertSame(
			array(),
			MultiCurrencySettingsProjectionService::get_settings_page_manifest( true, true, false, false )
		);
		$this->assertSame(
			array(),
			MultiCurrencySettingsProjectionService::get_settings_page_manifest( true, false, true, false )
		);
		$this->assertSame(
			array(),
			MultiCurrencySettingsProjectionService::get_settings_page_manifest( true, false, false, true )
		);
	}

	/**
	 * @testdox Should project onboarding settings page manifest when provider is disconnected.
	 */
	public function test_projects_onboarding_settings_page_manifest_when_provider_is_disconnected(): void {
		$manifest = MultiCurrencySettingsProjectionService::get_settings_page_manifest(
			false,
			false,
			false,
			false
		);

		$this->assertSame( 'wcpay_multi_currency', $manifest['id'] );
		$this->assertSame( 'Multi-currency', $manifest['label'] );
		$this->assertSame( 'onboarding_cta', $manifest['mode'] );
		$this->assertTrue( $manifest['hide_save_button'] );
		$this->assertSame( MultiCurrencySettingsProjectionService::get_onboarding_settings(), $manifest['settings'] );
	}

	/**
	 * @testdox Should project settings hooks container and page detection.
	 */
	public function test_projects_settings_hooks_container_and_page_detection(): void {
		$this->assertSame(
			array(
				'actions' => array(
					array(
						'hook'     => 'admin_print_scripts',
						'callback' => 'maybe_add_print_emoji_detection_script',
						'priority' => 10,
					),
					array(
						'hook'     => 'woocommerce_admin_field_wcpay_multi_currency_settings_page',
						'callback' => 'render_settings_container',
						'priority' => 10,
					),
					array(
						'hook'     => 'woocommerce_admin_field_wcpay_currencies_settings_onboarding_cta',
						'callback' => 'render_onboarding_cta',
						'priority' => 10,
					),
				),
			),
			MultiCurrencySettingsProjectionService::get_hook_manifest()
		);
		$this->assertSame(
			'<div id="wcpay_multi_currency_settings_container" class="wc-settings-prevent-change-event" aria-describedby="wcpay_multi_currency_settings_container-description"></div>',
			MultiCurrencySettingsProjectionService::get_settings_container_markup()
		);
		$this->assertTrue(
			MultiCurrencySettingsProjectionService::is_multi_currency_settings_page(
				true,
				'wcpay_multi_currency',
				'woocommerce_page_wc-settings'
			)
		);
		$this->assertFalse(
			MultiCurrencySettingsProjectionService::is_multi_currency_settings_page(
				true,
				'checkout',
				'woocommerce_page_wc-settings'
			)
		);
		$this->assertFalse(
			MultiCurrencySettingsProjectionService::is_multi_currency_settings_page(
				false,
				'wcpay_multi_currency',
				'woocommerce_page_wc-settings'
			)
		);
	}

	/**
	 * @testdox Should project onboarding settings and CTA markup.
	 */
	public function test_projects_onboarding_settings_and_cta_markup(): void {
		$settings = MultiCurrencySettingsProjectionService::get_onboarding_settings();
		$href     = 'https://example.test/onboarding?next=currencies&bad=<script>';
		$markup   = MultiCurrencySettingsProjectionService::get_onboarding_cta_markup( $href );

		$this->assertSame(
			array(
				array(
					'title' => 'Enabled currencies',
					'desc'  => 'Accept payments in multiple currencies. Prices are converted based on exchange rates and rounding rules. <a href="https://woocommerce.com/document/woopayments/currencies/multi-currency-setup/">Learn more</a>',
					'type'  => 'title',
					'id'    => 'wcpay_multi_currency_enabled_currencies',
				),
				array(
					'type' => 'wcpay_currencies_settings_onboarding_cta',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'wcpay_multi_currency_enabled_currencies',
				),
			),
			$settings
		);
		$this->assertStringContainsString(
			'To add new currencies to your store, please finish setting up WooPayments.',
			$markup
		);
		$this->assertStringContainsString( 'id="wcpay_enabled_currencies_onboarding_cta"', $markup );
		$this->assertStringContainsString( 'class="button-primary"', $markup );
		$this->assertStringContainsString( 'Get started', $markup );
		$this->assertStringContainsString( 'href="' . esc_url( $href ) . '"', $markup );
		$this->assertStringNotContainsString( '<script>', $markup );
	}

	/**
	 * @testdox Should project admin assets and JS config flag.
	 */
	public function test_projects_admin_assets_and_js_config_flag(): void {
		$asset_manifest = MultiCurrencySettingsProjectionService::get_admin_asset_manifest();

		$this->assertTrue( MultiCurrencySettingsProjectionService::should_enqueue_admin_assets( 'wcpay_multi_currency' ) );
		$this->assertFalse( MultiCurrencySettingsProjectionService::should_enqueue_admin_assets( 'checkout' ) );
		$this->assertSame(
			array(
				'script' => array(
					'entry'  => 'multi-currency-settings',
					'handle' => 'wc-admin-multi-currency-settings',
				),
				'style'  => array(
					'entry'  => 'multi-currency-settings',
					'file'   => 'style',
					'handle' => 'wc-admin-multi-currency-settings',
				),
			),
			$asset_manifest
		);
		$this->assertSame(
			array(
				'foo'                    => 'bar',
				'isMultiCurrencyEnabled' => true,
			),
			MultiCurrencySettingsProjectionService::add_props_to_wcpay_js_config( array( 'foo' => 'bar' ) )
		);
	}
}
