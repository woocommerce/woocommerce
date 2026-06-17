<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyDomainMap;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyDomainMap class.
 */
class MultiCurrencyDomainMapTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should record the source module and core namespace.
	 */
	public function test_records_source_module_and_core_namespace(): void {
		$this->assertSame( 'includes/multi-currency', MultiCurrencyDomainMap::SOURCE_MODULE, 'B0 should map the whole plugin multi-currency module.' );
		$this->assertSame( 'Automattic\\WooCommerce\\Internal\\MultiCurrency', MultiCurrencyDomainMap::CORE_NAMESPACE, 'B0 should establish the core internal domain home.' );
	}

	/**
	 * @testdox Should split interface supply between core defaults and the rate provider seam.
	 */
	public function test_splits_interface_supply_strategy(): void {
		$this->assertSame(
			array( 'cache', 'localization', 'settings' ),
			MultiCurrencyDomainMap::get_core_default_interfaces(),
			'Cache, localization, and settings are core-owned defaults in B1.'
		);
		$this->assertSame(
			array( 'account', 'api_client' ),
			MultiCurrencyDomainMap::get_rate_provider_interfaces(),
			'Account and API client behavior must flow through the rate-provider seam.'
		);
	}

	/**
	 * @testdox Should keep WooPayments rate source implementation in the payments provider namespace.
	 */
	public function test_woopayments_rate_source_implementation_lives_in_payments_provider_namespace(): void {
		$this->assertTrue( class_exists( 'Automattic\\WooCommerce\\Internal\\Payments\\Providers\\WooPayments\\MultiCurrency\\WooPaymentsCurrencyRateProvider' ) );
		$this->assertTrue( class_exists( 'Automattic\\WooCommerce\\Internal\\Payments\\Providers\\WooPayments\\MultiCurrency\\WooPaymentsCurrencyRateProviderRegistrar' ) );
		$this->assertTrue( class_exists( 'Automattic\\WooCommerce\\Internal\\Payments\\Providers\\WooPayments\\MultiCurrency\\WooPaymentsLegacyAccountAdapter' ) );
		$this->assertTrue( class_exists( 'Automattic\\WooCommerce\\Internal\\Payments\\Providers\\WooPayments\\MultiCurrency\\WooPaymentsLegacyApiClientAdapter' ) );
	}

	/**
	 * @testdox Should not keep WooPayments rate source implementation in the generic multi-currency namespace.
	 */
	public function test_woopayments_rate_source_implementation_is_not_in_generic_multi_currency_namespace(): void {
		$this->assertFalse( class_exists( 'Automattic\\WooCommerce\\Internal\\MultiCurrency\\Providers\\WooPaymentsCurrencyRateProvider' ) );
		$this->assertFalse( class_exists( 'Automattic\\WooCommerce\\Internal\\MultiCurrency\\Providers\\WooPaymentsCurrencyRateProviderRegistrar' ) );
		$this->assertFalse( class_exists( 'Automattic\\WooCommerce\\Internal\\MultiCurrency\\Providers\\WooPaymentsLegacyAccountAdapter' ) );
		$this->assertFalse( class_exists( 'Automattic\\WooCommerce\\Internal\\MultiCurrency\\Providers\\WooPaymentsLegacyApiClientAdapter' ) );
	}

	/**
	 * @testdox Should keep generic settings controller account wiring provider-neutral.
	 */
	public function test_settings_controller_account_wiring_is_provider_neutral(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads local plugin source for domain-boundary regression coverage.
		$source = (string) file_get_contents( WC()->plugin_path() . '/src/Internal/MultiCurrency/MultiCurrencySettingsController.php' );

		$this->assertStringContainsString( 'MultiCurrencyProviderAccountResolver', $source, 'Settings controller should use the provider-neutral account resolver.' );
		$this->assertStringNotContainsString( 'WooPaymentsLegacyAccountAdapter', $source, 'Settings controller should not type-hint the WooPayments account adapter.' );
		$this->assertStringNotContainsString( 'Internal\\Payments\\Providers\\WooPayments', $source, 'Settings controller should not import WooPayments provider implementation details.' );
	}

	/**
	 * @testdox Should keep generic account resolver wiring provider-neutral.
	 */
	public function test_provider_account_resolver_wiring_is_provider_neutral(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads local plugin source for domain-boundary regression coverage.
		$source = (string) file_get_contents( WC()->plugin_path() . '/src/Internal/MultiCurrency/Providers/MultiCurrencyProviderAccountResolver.php' );

		$this->assertStringContainsString( 'MultiCurrencyAccountInterface', $source, 'Generic resolver should expose only the provider-neutral account boundary.' );
		$this->assertStringNotContainsString( 'WooPaymentsLegacyAccountAdapter', $source, 'Generic resolver should not type-hint the WooPayments account adapter.' );
		$this->assertStringNotContainsString( 'Internal\\Payments\\Providers\\WooPayments', $source, 'Generic resolver should not import WooPayments provider implementation details.' );
	}

	/**
	 * @testdox Should keep generic admin note account wiring provider-neutral.
	 */
	public function test_admin_note_account_wiring_is_provider_neutral(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads local plugin source for domain-boundary regression coverage.
		$source = (string) file_get_contents( WC()->plugin_path() . '/src/Internal/MultiCurrency/MultiCurrencyAdminNoteController.php' );

		$this->assertStringContainsString( 'MultiCurrencyProviderAccountResolver', $source, 'Admin note controller should use the provider-neutral account resolver.' );
		$this->assertStringNotContainsString( 'WooPaymentsProvider', $source, 'Admin note controller should not type-hint the WooPayments provider.' );
		$this->assertStringNotContainsString( 'Internal\\Payments\\Providers\\WooPayments', $source, 'Admin note controller should not import WooPayments provider implementation details.' );
	}

	/**
	 * @testdox Should keep generic rate-provider registry factory wiring provider-neutral.
	 */
	public function test_rate_provider_registry_factory_wiring_is_provider_neutral(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads local plugin source for domain-boundary regression coverage.
		$source = (string) file_get_contents( WC()->plugin_path() . '/src/Internal/MultiCurrency/Providers/CurrencyRateProviderRegistryFactory.php' );

		$this->assertStringContainsString( 'CurrencyRateProviderRegistrarInterface', $source, 'Generic registry factory should expose only the provider-neutral registrar boundary.' );
		$this->assertStringNotContainsString( 'WooPaymentsCurrencyRateProviderRegistrar', $source, 'Generic registry factory should not type-hint the WooPayments registrar.' );
		$this->assertStringNotContainsString( 'Internal\\Payments\\Providers\\WooPayments', $source, 'Generic registry factory should not import WooPayments provider implementation details.' );
	}

	/**
	 * @testdox Should explicitly bootstrap WooPayments account boundaries before multi-currency settings.
	 */
	public function test_woopayments_account_bootstrap_registers_before_multi_currency_settings(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads local plugin source for domain-boundary regression coverage.
		$source = (string) file_get_contents( WC()->plugin_path() . '/includes/class-woocommerce.php' );

		$bootstrap_position = strpos( $source, 'WooPaymentsMultiCurrencyProviderBootstrap::class' );
		$settings_position  = strpos( $source, 'MultiCurrencySettingsController::class' );

		$this->assertNotFalse( $bootstrap_position, 'WooPayments account bootstrap should be explicitly registered.' );
		$this->assertNotFalse( $settings_position, 'Multi-currency settings controller should still be registered.' );

		if ( false === $bootstrap_position || false === $settings_position ) {
			return;
		}

		$this->assertLessThan( $settings_position, $bootstrap_position, 'WooPayments account bootstrap should run before settings consumes the resolver.' );
	}

	/**
	 * @testdox Should record hard-preserved multi-currency order meta keys.
	 */
	public function test_records_preserved_order_meta_keys(): void {
		$this->assertSame(
			array(
				'_wcpay_multi_currency_stripe_exchange_rate',
				'_wcpay_multi_currency_order_exchange_rate',
				'_wcpay_multi_currency_order_default_currency',
			),
			MultiCurrencyDomainMap::get_preserved_order_meta_keys(),
			'B0 should pin the Bucket-E multi-currency order meta keys.'
		);
	}

	/**
	 * @testdox Should record hard-preserved multi-currency option keys.
	 */
	public function test_records_preserved_option_keys(): void {
		$option_keys = MultiCurrencyDomainMap::get_preserved_option_keys();

		$this->assertContains( 'wcpay_multi_currency_enable_auto_currency', $option_keys, 'Automatic currency switching setting must be preserved.' );
		$this->assertContains( 'wcpay_multi_currency_enable_storefront_switcher', $option_keys, 'Storefront switcher setting must be preserved.' );
		$this->assertContains( 'wcpay_multi_currency_setup_completed', $option_keys, 'Setup completion state must be preserved.' );
		$this->assertContains( 'wcpay_multi_currency_exchange_rate_{currency}', $option_keys, 'Per-currency exchange-rate settings must be preserved.' );
	}

	/**
	 * @testdox Should record price pipeline hooks that require mutual exclusion.
	 */
	public function test_records_price_pipeline_hooks(): void {
		$hooks = MultiCurrencyDomainMap::get_price_pipeline_hooks();

		$this->assertContains( 'woocommerce_currency', $hooks, 'Currency switching must not double-register.' );
		$this->assertContains( 'woocommerce_product_variation_get_price', $hooks, 'Variation price conversion must not double-register.' );
		$this->assertContains( 'woocommerce_get_variation_prices_hash', $hooks, 'Variation price hashes must include a single currency owner.' );
		$this->assertContains( 'woocommerce_shipping_method_add_rate_args', $hooks, 'Shipping rate conversion must not double-register.' );
		$this->assertContains( 'woocommerce_coupon_get_amount', $hooks, 'Coupon conversion must not double-register.' );
		$this->assertContains( 'woocommerce_order_get_total', $hooks, 'Order display currency setup must not double-register.' );
		$this->assertContains( 'woocommerce_get_formatted_order_total', $hooks, 'Order display currency cleanup must not double-register.' );
		$this->assertContains( 'woocommerce_cart_hash', $hooks, 'Cart hash conversion must not double-register.' );
		$this->assertContains( 'woocommerce_new_order', $hooks, 'Order currency meta writing must not double-register.' );
		$this->assertContains( 'woocommerce_order_status_changed', $hooks, 'Customer currency tracking must not double-register.' );
		$this->assertContains( 'rest_post_dispatch', $hooks, 'Store API price-filter conversion must not double-register.' );
		$this->assertContains( 'query_loop_block_query_vars', $hooks, 'Block query price-filter conversion must not double-register.' );
	}

	/**
	 * @testdox Should centralize projection service graph construction in the factory.
	 */
	public function test_projection_service_construction_is_centralized_in_factory(): void {
		$source_files = array(
			'src/Internal/MultiCurrency/MultiCurrencyFrontendPricesController.php',
			'src/Internal/MultiCurrency/MultiCurrencyFrontendCurrenciesController.php',
			'src/Internal/MultiCurrency/MultiCurrencyAsyncPriceRendererController.php',
			'src/Internal/MultiCurrency/MultiCurrencyRestController.php',
			'src/Internal/MultiCurrency/MultiCurrencyBookingsCompatibilityController.php',
			'src/Internal/MultiCurrency/MultiCurrencyDepositsCompatibilityController.php',
			'src/Internal/MultiCurrency/MultiCurrencyProductAddOnsCompatibilityController.php',
			'src/Internal/MultiCurrency/MultiCurrencyNameYourPriceCompatibilityController.php',
			'src/Internal/MultiCurrency/MultiCurrencyPreOrdersCompatibilityController.php',
			'src/Internal/MultiCurrency/MultiCurrencySubscriptionsCompatibilityController.php',
			'src/Internal/MultiCurrency/Shadow/MultiCurrencyShadowMode.php',
		);

		foreach ( $source_files as $source_file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads local plugin source for domain-boundary regression coverage.
			$source = (string) file_get_contents( WC()->plugin_path() . '/' . $source_file );

			$this->assertStringNotContainsString( 'new MultiCurrencyPriceProjectionService(', $source, "{$source_file} should delegate price projection construction to the factory." );
			$this->assertStringNotContainsString( 'new MultiCurrencyFrontendProjectionService(', $source, "{$source_file} should delegate frontend projection construction to the factory." );
			$this->assertStringNotContainsString( 'new MultiCurrencyPriceCalculator(', $source, "{$source_file} should delegate price calculator construction to the factory." );
		}
	}

	/**
	 * @testdox Should inject state builder factory access into controllers.
	 */
	public function test_state_builder_factory_access_is_injected_into_controllers(): void {
		$source_files = array(
			'src/Internal/MultiCurrency/MultiCurrencyAnalyticsController.php',
			'src/Internal/MultiCurrency/MultiCurrencyPointsRewardsCompatibilityController.php',
			'src/Internal/MultiCurrency/MultiCurrencySubscriptionsCompatibilityController.php',
			'src/Internal/MultiCurrency/MultiCurrencyNameYourPriceCompatibilityController.php',
			'src/Internal/MultiCurrency/MultiCurrencySwitcherBlockController.php',
			'src/Internal/MultiCurrency/MultiCurrencyStorefrontIntegrationController.php',
			'src/Internal/MultiCurrency/MultiCurrencySelectedCurrencyController.php',
			'src/Internal/MultiCurrency/MultiCurrencyStoreCurrencyLifecycleController.php',
			'src/Internal/MultiCurrency/MultiCurrencyRestController.php',
			'src/Internal/MultiCurrency/MultiCurrencyCompatibilityController.php',
			'src/Internal/MultiCurrency/MultiCurrencyTrackingController.php',
			'src/Internal/MultiCurrency/MultiCurrencySwitcherWidgetController.php',
		);

		foreach ( $source_files as $source_file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads local plugin source for domain-boundary regression coverage.
			$source = (string) file_get_contents( WC()->plugin_path() . '/' . $source_file );

			$this->assertDoesNotMatchRegularExpression(
				'/wc_get_container\(\)\s*->get\(\s*MultiCurrencyStateBuilderFactory::class\s*\)/',
				$source,
				"{$source_file} should receive the state builder factory through init injection."
			);
		}
	}

	/**
	 * @testdox Should inject projection service factory access into controllers.
	 */
	public function test_projection_service_factory_access_is_injected_into_controllers(): void {
		$source_files = array(
			'src/Internal/MultiCurrency/MultiCurrencyFrontendPricesController.php',
			'src/Internal/MultiCurrency/MultiCurrencyFrontendCurrenciesController.php',
			'src/Internal/MultiCurrency/MultiCurrencyAsyncPriceRendererController.php',
			'src/Internal/MultiCurrency/MultiCurrencyRestController.php',
			'src/Internal/MultiCurrency/MultiCurrencyBookingsCompatibilityController.php',
			'src/Internal/MultiCurrency/MultiCurrencyDepositsCompatibilityController.php',
			'src/Internal/MultiCurrency/MultiCurrencyProductAddOnsCompatibilityController.php',
			'src/Internal/MultiCurrency/MultiCurrencyNameYourPriceCompatibilityController.php',
			'src/Internal/MultiCurrency/MultiCurrencyPreOrdersCompatibilityController.php',
			'src/Internal/MultiCurrency/MultiCurrencySubscriptionsCompatibilityController.php',
			'src/Internal/MultiCurrency/Shadow/MultiCurrencyShadowMode.php',
		);

		foreach ( $source_files as $source_file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads local plugin source for domain-boundary regression coverage.
			$source = (string) file_get_contents( WC()->plugin_path() . '/' . $source_file );

			$this->assertDoesNotMatchRegularExpression(
				'/wc_get_container\(\)\s*->get\(\s*MultiCurrencyProjectionServiceFactory::class\s*\)/',
				$source,
				"{$source_file} should receive the projection service factory through init injection."
			);
		}
	}

	/**
	 * @testdox Should centralize runtime service graph construction in the factory.
	 */
	public function test_runtime_service_construction_is_centralized_in_factory(): void {
		$source_files           = array(
			'src/Internal/MultiCurrency/MultiCurrencyFrontendPricesController.php',
			'src/Internal/MultiCurrency/MultiCurrencySelectedCurrencyController.php',
			'src/Internal/MultiCurrency/MultiCurrencyFrontendCurrenciesController.php',
			'src/Internal/MultiCurrency/MultiCurrencyAsyncPriceRendererController.php',
			'src/Internal/MultiCurrency/MultiCurrencyRestRequestOverrideController.php',
			'src/Internal/MultiCurrency/MultiCurrencyAnalyticsController.php',
			'src/Internal/MultiCurrency/MultiCurrencyTrackingController.php',
			'src/Internal/MultiCurrency/MultiCurrencySwitcherBlockController.php',
			'src/Internal/MultiCurrency/MultiCurrencySwitcherWidgetController.php',
			'src/Internal/MultiCurrency/MultiCurrencyStorefrontIntegrationController.php',
			'src/Internal/MultiCurrency/MultiCurrencyStoreCurrencyLifecycleController.php',
		);
		$forbidden_constructors = array(
			'new MultiCurrencyRequestContext(',
			'new MultiCurrencyOrderContextService(',
			'new MultiCurrencySelectedCurrencyPersistenceService(',
			'new MultiCurrencyGeolocationService(',
			'new MultiCurrencySwitcherProjectionService(',
			'new MultiCurrencyAnalyticsProjectionService(',
			'new MultiCurrencyAnalyticsSqlProjectionService(',
			'new MultiCurrencyTrackingProjectionService(',
			'new MultiCurrencyTrackingOrderCountProjectionService(',
			'new MultiCurrencyStoreCurrencyLifecycleService(',
			'new MultiCurrencyDatabaseCache(',
		);

		foreach ( $source_files as $source_file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads local plugin source for domain-boundary regression coverage.
			$source = (string) file_get_contents( WC()->plugin_path() . '/' . $source_file );

			foreach ( $forbidden_constructors as $forbidden_constructor ) {
				$this->assertStringNotContainsString(
					$forbidden_constructor,
					$source,
					"{$source_file} should delegate {$forbidden_constructor} construction to the runtime service factory."
				);
			}
		}
	}

	/**
	 * @testdox Should record the plugin compatibility integrations that B1 must preserve.
	 */
	public function test_records_compatibility_integrations(): void {
		$integrations = MultiCurrencyDomainMap::get_compatibility_integrations();

		$this->assertContains( 'WooCommerceSubscriptions', $integrations, 'Subscriptions compatibility must be preserved.' );
		$this->assertContains( 'WooCommerceBookings', $integrations, 'Bookings compatibility must be preserved.' );
		$this->assertContains( 'WooCommerceProductAddOns', $integrations, 'Product Add-ons compatibility must be preserved.' );
		$this->assertContains( 'WooCommerceDeposits', $integrations, 'Deposits compatibility must be preserved.' );
	}
}
