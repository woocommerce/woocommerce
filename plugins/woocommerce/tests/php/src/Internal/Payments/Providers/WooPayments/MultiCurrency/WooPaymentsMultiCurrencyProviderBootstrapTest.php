<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Providers\MultiCurrencyProviderAccountResolver;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\MultiCurrency\WooPaymentsLegacyAccountAdapter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsLegacyRuntime;
use Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments\LegacyRuntimeProxy;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsMultiCurrencyProviderBootstrap class.
 */
class WooPaymentsMultiCurrencyProviderBootstrapTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should configure the provider-neutral account resolver with the WooPayments account adapter.
	 */
	public function test_configures_provider_account_resolver_with_woopayments_adapter(): void {
		$bootstrap_class = 'Automattic\\WooCommerce\\Internal\\Payments\\Providers\\WooPayments\\MultiCurrency\\WooPaymentsMultiCurrencyProviderBootstrap';

		$this->assertTrue( class_exists( $bootstrap_class ), 'WooPayments multi-currency provider bootstrap class should exist.' );

		if ( ! class_exists( $bootstrap_class ) ) {
			return;
		}

		$account_resolver = new MultiCurrencyProviderAccountResolver();
		$account_adapter  = new WooPaymentsLegacyAccountAdapter();
		$legacy_runtime   = new WooPaymentsLegacyRuntime();
		$legacy_runtime->init( new LegacyRuntimeProxy( true, null, $this->create_legacy_account() ) );
		$account_adapter->init( $legacy_runtime );

		$bootstrap = new $bootstrap_class();
		$bootstrap->init( $account_resolver, $account_adapter );
		$bootstrap->register();

		$this->assertTrue( $account_resolver->is_provider_connected(), 'Resolver should delegate connection checks to the WooPayments adapter.' );
		$this->assertSame( 'https://example.test/onboarding', $account_resolver->get_provider_onboarding_page_url(), 'Resolver should delegate onboarding URL lookups to the WooPayments adapter.' );
	}

	/**
	 * Create a recording legacy account test double.
	 *
	 * @return object
	 */
	private function create_legacy_account(): object {
		return new class() {
			/**
			 * Tell whether the provider account is connected.
			 *
			 * @param bool $on_error Error fallback.
			 * @return bool
			 */
			public function is_provider_connected( bool $on_error = false ): bool {
				unset( $on_error );

				return true;
			}

			/**
			 * Get the provider onboarding URL.
			 *
			 * @return string
			 */
			public function get_provider_onboarding_page_url(): string {
				return 'https://example.test/onboarding';
			}
		};
	}
}
