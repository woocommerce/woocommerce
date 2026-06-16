<?php
/**
 * MultiCurrencyProviderAccountResolver class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Providers;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyAccountInterface;

/**
 * Resolves the active native multi-currency provider account boundary.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyProviderAccountResolver {

	/**
	 * Active provider account boundary.
	 *
	 * @var MultiCurrencyAccountInterface|null
	 */
	private ?MultiCurrencyAccountInterface $account = null;

	/**
	 * Set the active account boundary.
	 *
	 * @internal Used by tests and future explicit provider bootstrap.
	 *
	 * @param MultiCurrencyAccountInterface $account Active provider account boundary.
	 */
	public function set_account( MultiCurrencyAccountInterface $account ): void {
		$this->account = $account;
	}

	/**
	 * Tell whether the active provider account is connected.
	 *
	 * @return bool
	 */
	public function is_provider_connected(): bool {
		if ( null === $this->account ) {
			return false;
		}

		try {
			return $this->account->is_provider_connected();
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	/**
	 * Get the active provider onboarding URL.
	 *
	 * @return string
	 */
	public function get_provider_onboarding_page_url(): string {
		if ( null === $this->account ) {
			return '';
		}

		try {
			return $this->account->get_provider_onboarding_page_url();
		} catch ( \Throwable $e ) {
			return '';
		}
	}
}
