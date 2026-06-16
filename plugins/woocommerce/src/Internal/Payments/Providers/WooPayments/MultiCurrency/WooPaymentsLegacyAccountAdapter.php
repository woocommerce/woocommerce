<?php
/**
 * WooPaymentsLegacyAccountAdapter class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyAccountInterface;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsLegacyRuntime;

/**
 * Adapts the local WooPayments account service to the native multi-currency boundary.
 *
 * @since 11.0.0
 * @internal Transitional bridge while WooPayments provider transport is absorbed into core.
 */
class WooPaymentsLegacyAccountAdapter implements MultiCurrencyAccountInterface {

	/**
	 * WooPayments legacy runtime.
	 *
	 * @var WooPaymentsLegacyRuntime
	 */
	private WooPaymentsLegacyRuntime $legacy_runtime;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsLegacyRuntime $legacy_runtime WooPayments legacy runtime.
	 */
	final public function init( WooPaymentsLegacyRuntime $legacy_runtime ): void {
		$this->legacy_runtime = $legacy_runtime;
	}

	/**
	 * Tell whether the rate provider account is connected.
	 *
	 * @param bool $on_error Value to return on provider errors.
	 * @return bool
	 */
	public function is_provider_connected( bool $on_error = false ): bool {
		$account = $this->get_legacy_account();
		if ( ! $account || ! is_callable( array( $account, 'is_provider_connected' ) ) ) {
			return false;
		}

		try {
			return (bool) $account->is_provider_connected( $on_error );
		} catch ( \Throwable $e ) {
			return $on_error;
		}
	}

	/**
	 * Tell whether the connected account is rejected.
	 *
	 * @return bool
	 */
	public function is_account_rejected(): bool {
		$account = $this->get_legacy_account();
		if ( ! $account || ! is_callable( array( $account, 'is_account_rejected' ) ) ) {
			return false;
		}

		try {
			return (bool) $account->is_account_rejected();
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	/**
	 * Get cached provider account data.
	 *
	 * @param bool $force_refresh Whether to force-refresh provider data.
	 * @return array<string,mixed>|bool
	 */
	public function get_cached_account_data( bool $force_refresh = false ) {
		$account = $this->get_legacy_account();
		if ( ! $account || ! is_callable( array( $account, 'get_cached_account_data' ) ) ) {
			return false;
		}

		try {
			$account_data = $account->get_cached_account_data( $force_refresh );
		} catch ( \Throwable $e ) {
			return false;
		}

		return is_array( $account_data ) ? $account_data : false;
	}

	/**
	 * Get account-supported customer currencies.
	 *
	 * @return string[]
	 */
	public function get_account_customer_supported_currencies(): array {
		return $this->call_legacy_array_method( 'get_account_customer_supported_currencies' );
	}

	/**
	 * Get provider-supported countries.
	 *
	 * @return string[]
	 */
	public function get_supported_countries(): array {
		return $this->call_legacy_array_method( 'get_supported_countries' );
	}

	/**
	 * Get the provider onboarding URL.
	 *
	 * @return string
	 */
	public function get_provider_onboarding_page_url(): string {
		$account = $this->get_legacy_account();
		if ( ! $account || ! is_callable( array( $account, 'get_provider_onboarding_page_url' ) ) ) {
			return '';
		}

		try {
			return (string) $account->get_provider_onboarding_page_url();
		} catch ( \Throwable $e ) {
			return '';
		}
	}

	/**
	 * Call a legacy account method expected to return an array.
	 *
	 * @param string $method_name Method name.
	 * @return string[]
	 */
	private function call_legacy_array_method( string $method_name ): array {
		$account = $this->get_legacy_account();
		if ( ! $account || ! is_callable( array( $account, $method_name ) ) ) {
			return array();
		}

		try {
			$result = $account->{$method_name}();
		} catch ( \Throwable $e ) {
			return array();
		}

		return is_array( $result ) ? $result : array();
	}

	/**
	 * Get the local WooPayments account service when the legacy runtime is loaded.
	 *
	 * @return object|null
	 */
	private function get_legacy_account(): ?object {
		return $this->legacy_runtime->get_account_service();
	}
}
