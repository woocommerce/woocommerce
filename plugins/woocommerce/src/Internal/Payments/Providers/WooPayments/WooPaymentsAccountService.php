<?php
/**
 * WooPaymentsAccountService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Reads Core-owned WooPayments account readiness from preserved persisted data.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsAccountService {

	private const ACCOUNT_OPTION = 'wcpay_account_data';

	private const SETTINGS_OPTION = 'woocommerce_woocommerce_payments_settings';

	private const ONBOARDING_TEST_MODE_OPTION = 'wcpay_onboarding_test_mode';

	private const DEV_MODE_ENVIRONMENTS = array(
		'development',
		'staging',
	);

	/**
	 * Legacy proxy.
	 *
	 * @var LegacyProxy
	 */
	private LegacyProxy $legacy_proxy;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param LegacyProxy $legacy_proxy Legacy proxy.
	 */
	final public function init( LegacyProxy $legacy_proxy ): void {
		$this->legacy_proxy = $legacy_proxy;
	}

	/**
	 * Get normalized WooPayments account cache data.
	 *
	 * @return array<string,mixed>
	 */
	public function get_cached_account_data(): array {
		try {
			$account_data = $this->legacy_proxy->call_function( 'get_option', self::ACCOUNT_OPTION, array() );
		} catch ( \Throwable $e ) {
			return array();
		}

		if ( ! is_array( $account_data ) || empty( $account_data['data'] ) || ! is_array( $account_data['data'] ) ) {
			return array();
		}

		return $account_data['data'];
	}

	/**
	 * Clear the preserved WooPayments account cache.
	 *
	 * This mirrors the plugin behavior after account creation/finalization so the next account read can refresh the
	 * provider state instead of continuing to use stale cached readiness.
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		try {
			$this->legacy_proxy->call_function( 'delete_option', self::ACCOUNT_OPTION );
			$this->legacy_proxy->call_function( 'wp_cache_delete', self::ACCOUNT_OPTION, 'options' );
		} catch ( \Throwable $e ) {
			return;
		}
	}

	/**
	 * Persist normalized WooPayments account cache data.
	 *
	 * @param array<string,mixed> $account_data Account data.
	 * @return void
	 */
	public function cache_account_data( array $account_data ): void {
		try {
			$this->legacy_proxy->call_function(
				'update_option',
				self::ACCOUNT_OPTION,
				array(
					'data'               => $account_data,
					'fetched'            => $this->legacy_proxy->call_function( 'time' ),
					'errored'            => false,
					'consecutive_errors' => 0,
				)
			);
			$this->legacy_proxy->call_function( 'wp_cache_delete', self::ACCOUNT_OPTION, 'options' );
		} catch ( \Throwable $e ) {
			return;
		}
	}

	/**
	 * Immediately overwrite the preserved account cache with a connected-but-no-account payload.
	 *
	 * This avoids reading a stale account while the platform finishes deleting it.
	 *
	 * @return void
	 */
	public function overwrite_cache_with_no_account(): void {
		try {
			$this->legacy_proxy->call_function(
				'update_option',
				self::ACCOUNT_OPTION,
				array(
					'data'               => array(),
					'fetched'            => $this->legacy_proxy->call_function( 'time' ),
					'errored'            => false,
					'consecutive_errors' => 0,
				)
			);
			$this->legacy_proxy->call_function( 'wp_cache_delete', self::ACCOUNT_OPTION, 'options' );
		} catch ( \Throwable $e ) {
			return;
		}
	}

	/**
	 * Get the connected WooPayments account ID.
	 *
	 * @return string
	 */
	public function get_account_id(): string {
		$account_data = $this->get_cached_account_data();

		return isset( $account_data['account_id'] ) && is_scalar( $account_data['account_id'] )
			? (string) $account_data['account_id']
			: '';
	}

	/**
	 * Get the mode-specific publishable key.
	 *
	 * @return string
	 */
	public function get_publishable_key(): string {
		$key_name        = $this->is_test_mode_enabled() ? 'test_publishable_key' : 'live_publishable_key';
		$account_data    = $this->get_cached_account_data();
		$publishable_key = $account_data[ $key_name ] ?? '';

		return is_scalar( $publishable_key ) ? (string) $publishable_key : '';
	}

	/**
	 * Tell whether WooPayments native processing has enough account data to act.
	 *
	 * @return bool
	 */
	public function can_process_payments(): bool {
		$account_data = $this->get_cached_account_data();

		return '' !== $this->get_account_id()
			&& '' !== $this->get_publishable_key()
			&& $this->is_truthy( $account_data['payments_enabled'] ?? false )
			&& $this->is_truthy( $account_data['details_submitted'] ?? false );
	}

	/**
	 * Tell whether WooPayments has an account cache entry.
	 *
	 * @return bool
	 */
	public function has_account(): bool {
		return '' !== $this->get_account_id();
	}

	/**
	 * Tell whether the cached account can currently receive payments.
	 *
	 * @return bool
	 */
	public function has_working_account(): bool {
		$account_data = $this->get_cached_account_data();

		return $this->has_account() && $this->is_truthy( $account_data['payments_enabled'] ?? false );
	}

	/**
	 * Tell whether the cached account is a test-drive account.
	 *
	 * @return bool
	 */
	public function has_test_account(): bool {
		$account_data = $this->get_cached_account_data();

		return $this->has_account() && $this->is_truthy( $account_data['is_test_drive'] ?? false );
	}

	/**
	 * Tell whether the cached account is a sandbox account.
	 *
	 * @return bool
	 */
	public function has_sandbox_account(): bool {
		$account_data = $this->get_cached_account_data();

		return $this->has_account()
			&& array_key_exists( 'is_live', $account_data )
			&& ! $this->is_truthy( $account_data['is_live'] )
			&& ! $this->has_test_account();
	}

	/**
	 * Tell whether the cached account is a live account.
	 *
	 * @return bool
	 */
	public function has_live_account(): bool {
		$account_data = $this->get_cached_account_data();

		return $this->has_account() && $this->is_truthy( $account_data['is_live'] ?? false );
	}

	/**
	 * Tell whether WooPayments is in test mode.
	 *
	 * @return bool
	 */
	public function is_test_mode_enabled(): bool {
		$test_mode_onboarding = $this->is_test_mode_onboarding_enabled();
		if ( $test_mode_onboarding ) {
			$test_mode = true;
		} else {
			$settings  = $this->get_gateway_settings();
			$test_mode = 'yes' === ( $settings['test_mode'] ?? 'no' );
		}

		/**
		 * Allows WooPayments to process payments in test mode.
		 *
		 * @since 11.0.0
		 *
		 * @param bool $test_mode Whether WooPayments should process payments in test mode.
		 */
		return (bool) apply_filters( 'wcpay_test_mode', $test_mode );
	}

	/**
	 * Get the current WooPayments mode slug.
	 *
	 * @return string
	 */
	public function get_mode(): string {
		return $this->is_test_mode_enabled() ? 'test' : 'live';
	}

	/**
	 * Tell whether WooPayments is in test-mode onboarding.
	 *
	 * @return bool
	 */
	private function is_test_mode_onboarding_enabled(): bool {
		$test_mode_onboarding = $this->is_dev_mode_enabled() || $this->is_onboarding_test_mode_enabled();

		/**
		 * Allows WooPayments to use test mode onboarding.
		 *
		 * @since 11.0.0
		 *
		 * @param bool $test_mode_onboarding Whether WooPayments should use test mode onboarding.
		 */
		return (bool) apply_filters( 'wcpay_test_mode_onboarding', $test_mode_onboarding );
	}

	/**
	 * Tell whether WooPayments development mode is enabled.
	 *
	 * @return bool
	 */
	private function is_dev_mode_enabled(): bool {
		$dev_mode = $this->is_wcpay_dev_mode_defined()
			|| $this->is_wp_environment_dev_mode()
			|| $this->is_wp_development_mode_enabled();

		/**
		 * Allows WooPayments to enter dev mode.
		 *
		 * @since 11.0.0
		 *
		 * @param bool $dev_mode Whether WooPayments should enter dev mode.
		 */
		return (bool) apply_filters( 'wcpay_dev_mode', $dev_mode );
	}

	/**
	 * Tell whether WooPayments dev mode constant is defined.
	 *
	 * @return bool
	 */
	private function is_wcpay_dev_mode_defined(): bool {
		return defined( 'WCPAY_DEV_MODE' ) && WCPAY_DEV_MODE;
	}

	/**
	 * Tell whether the current WordPress environment implies WooPayments dev mode.
	 *
	 * @return bool
	 */
	private function is_wp_environment_dev_mode(): bool {
		if ( ! function_exists( 'wp_get_environment_type' ) ) {
			return false;
		}

		return in_array( wp_get_environment_type(), self::DEV_MODE_ENVIRONMENTS, true );
	}

	/**
	 * Tell whether WordPress development mode implies WooPayments dev mode.
	 *
	 * @return bool
	 */
	private function is_wp_development_mode_enabled(): bool {
		return function_exists( 'wp_get_development_mode' ) && '' !== wp_get_development_mode();
	}

	/**
	 * Get persisted gateway settings.
	 *
	 * @return array<string,mixed>
	 */
	private function get_gateway_settings(): array {
		try {
			$settings = $this->legacy_proxy->call_function( 'get_option', self::SETTINGS_OPTION, array() );
		} catch ( \Throwable $e ) {
			return array();
		}

		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Tell whether onboarding test mode is enabled.
	 *
	 * @return bool
	 */
	private function is_onboarding_test_mode_enabled(): bool {
		try {
			$value = $this->legacy_proxy->call_function( 'get_option', self::ONBOARDING_TEST_MODE_OPTION, 'no' );
		} catch ( \Throwable $e ) {
			return false;
		}

		return in_array( $value, array( 'yes', '1' ), true );
	}

	/**
	 * Normalize persisted booleans.
	 *
	 * @param mixed $value Raw boolean-like value.
	 * @return bool
	 */
	private function is_truthy( $value ): bool {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) ?? false;
	}
}
