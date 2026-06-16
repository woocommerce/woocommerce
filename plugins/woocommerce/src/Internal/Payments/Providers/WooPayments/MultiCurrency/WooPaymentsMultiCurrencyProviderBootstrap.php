<?php
/**
 * WooPaymentsMultiCurrencyProviderBootstrap class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Providers\MultiCurrencyProviderAccountResolver;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Configures WooPayments-owned native multi-currency provider boundaries.
 *
 * @since 11.0.0
 * @internal Transitional bootstrap while WooPayments multi-currency runtime is absorbed into core.
 */
class WooPaymentsMultiCurrencyProviderBootstrap implements RegisterHooksInterface {

	/**
	 * Provider account resolver.
	 *
	 * @var MultiCurrencyProviderAccountResolver
	 */
	private MultiCurrencyProviderAccountResolver $account_resolver;

	/**
	 * WooPayments account adapter.
	 *
	 * @var WooPaymentsLegacyAccountAdapter
	 */
	private WooPaymentsLegacyAccountAdapter $account_adapter;

	/**
	 * Initialize the bootstrap.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyProviderAccountResolver $account_resolver Provider account resolver.
	 * @param WooPaymentsLegacyAccountAdapter      $account_adapter  WooPayments account adapter.
	 */
	final public function init( MultiCurrencyProviderAccountResolver $account_resolver, WooPaymentsLegacyAccountAdapter $account_adapter ): void {
		$this->account_resolver = $account_resolver;
		$this->account_adapter  = $account_adapter;
	}

	/**
	 * Register WooPayments multi-currency account boundaries.
	 *
	 * @since 11.0.0
	 */
	public function register(): void {
		$this->account_resolver->set_account( $this->account_adapter );
	}
}
