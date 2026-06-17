<?php
/**
 * WooPaymentsAccountService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Reads Core-owned WooPayments account readiness from preserved persisted data.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsAccountService implements RegisterHooksInterface {

	private const ACCOUNT_OPTION = 'wcpay_account_data';

	private const SETTINGS_OPTION = 'woocommerce_woocommerce_payments_settings';

	private const ONBOARDING_TEST_MODE_OPTION = 'wcpay_onboarding_test_mode';

	private const ONBOARDING_DISABLED_TRANSIENT = 'wcpay_on_boarding_disabled';

	private const ACCOUNT_CACHE_ADMIN_TTL = 2 * HOUR_IN_SECONDS;

	private const ACCOUNT_CACHE_FRONTEND_TTL = DAY_IN_SECONDS;

	private const ACCOUNT_CACHE_ERRORED_TTL_LADDER = array(
		2 * MINUTE_IN_SECONDS,
		5 * MINUTE_IN_SECONDS,
		10 * MINUTE_IN_SECONDS,
		15 * MINUTE_IN_SECONDS,
	);

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
	 * In-request account cache contents.
	 *
	 * @var array<string,mixed>|false
	 */
	private $account_cache = false;

	/**
	 * Whether account cache contents have been loaded for this request.
	 *
	 * @var bool
	 */
	private bool $account_cache_loaded = false;

	/**
	 * Whether account refreshes are disabled for this request.
	 *
	 * @var bool
	 */
	private bool $refresh_disabled = false;

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
	 * Register account cache hooks.
	 */
	public function register() {
		if ( false === has_action( 'action_scheduler_before_execute', array( $this, 'disable_refresh' ) ) ) {
			add_action( 'action_scheduler_before_execute', array( $this, 'disable_refresh' ) );
		}
	}

	/**
	 * Disable account cache refreshes for this request.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function disable_refresh(): void {
		$this->refresh_disabled = true;
	}

	/**
	 * Get normalized WooPayments account cache data.
	 *
	 * @param bool $force_refresh Whether to force a live account refresh.
	 * @return array<string,mixed>
	 */
	public function get_cached_account_data( bool $force_refresh = false ): array {
		$cache_contents = $this->get_account_cache();
		$data           = null;
		$old_data       = null;

		if (
			is_array( $cache_contents )
			&& array_key_exists( 'data', $cache_contents )
			&& $this->is_valid_cached_account( $cache_contents['data'] )
		) {
			$data     = $cache_contents['data'];
			$old_data = $data;
		}

		if ( $this->should_refresh_account_cache( $cache_contents, $force_refresh ) ) {
			$data      = $this->fetch_account_data();
			$errored   = false === $data;
			$refreshed = ! $errored;

			if ( $errored ) {
				$data = $old_data;
			}

			$this->write_account_cache( $data, $errored );

			if ( $refreshed ) {
				/**
				 * Allows native WooPayments integrations to react when account data is refreshed.
				 *
				 * @since 11.0.0
				 *
				 * @param array<string,mixed> $account_data Refreshed WooPayments account data.
				 */
				do_action( 'woocommerce_payments_account_refreshed', $data );
			}
		}

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Refetch WooPayments account data and persist the refreshed account cache.
	 *
	 * @return array<string,mixed>
	 */
	public function refresh_account_data(): array {
		return $this->get_cached_account_data( true );
	}

	/**
	 * Fetch account data from the native WooPayments API.
	 *
	 * @return array<string,mixed>|false
	 */
	private function fetch_account_data() {
		$api_client = $this->get_api_client();
		if ( ! $api_client || ! $api_client->is_available() ) {
			return false;
		}

		try {
			$this->legacy_proxy->call_function( 'delete_transient', self::ONBOARDING_DISABLED_TRANSIENT );
			$account_data = $api_client->get_account( $this->get_woocommerce_store_id() );
		} catch ( WooPaymentsApiException $e ) {
			if ( 'wcpay_account_not_found' === $e->get_error_code() ) {
				$account_data = array();
			} elseif ( 'wcpay_on_boarding_disabled' === $e->get_error_code() ) {
				$account_data = array();
				$this->legacy_proxy->call_function( 'set_transient', self::ONBOARDING_DISABLED_TRANSIENT, true, 2 * HOUR_IN_SECONDS );
			} else {
				return false;
			}
		} catch ( \Throwable $e ) {
			return false;
		}

		if ( ! $this->is_valid_cached_account( $account_data ) ) {
			return false;
		}

		return $account_data;
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
			$this->account_cache        = false;
			$this->account_cache_loaded = false;
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
		$this->write_account_cache( $account_data, false );
	}

	/**
	 * Immediately overwrite the preserved account cache with a connected-but-no-account payload.
	 *
	 * This avoids reading a stale account while the platform finishes deleting it.
	 *
	 * @return void
	 */
	public function overwrite_cache_with_no_account(): void {
		$this->write_account_cache( array(), false );
	}

	/**
	 * Get the raw persisted account cache wrapper.
	 *
	 * @return array<string,mixed>|false
	 */
	private function get_account_cache() {
		if ( $this->account_cache_loaded ) {
			return $this->account_cache;
		}

		try {
			$cache = $this->legacy_proxy->call_function( 'get_option', self::ACCOUNT_OPTION );
		} catch ( \Throwable $e ) {
			$cache = false;
		}

		$this->account_cache        = is_array( $cache ) ? $cache : false;
		$this->account_cache_loaded = true;

		return $this->account_cache;
	}

	/**
	 * Write account data with cache metadata.
	 *
	 * @param mixed $account_data Account data.
	 * @param bool  $errored      Whether the refresh that produced this write errored.
	 * @return void
	 */
	private function write_account_cache( $account_data, bool $errored ): void {
		$consecutive_errors = 0;
		if ( $errored ) {
			$previous           = $this->get_account_cache();
			$previous_count     = is_array( $previous ) && isset( $previous['consecutive_errors'] )
				? (int) $previous['consecutive_errors']
				: 0;
			$consecutive_errors = $previous_count + 1;
		}

		try {
			$cache_contents = array(
				'data'               => $account_data,
				'fetched'            => $this->legacy_proxy->call_function( 'time' ),
				'errored'            => $errored,
				'consecutive_errors' => $consecutive_errors,
			);

			$this->account_cache        = $cache_contents;
			$this->account_cache_loaded = true;

			$result = $this->legacy_proxy->call_function( 'update_option', self::ACCOUNT_OPTION, $cache_contents, 'no' );
			if ( false !== $result ) {
				$this->legacy_proxy->call_function( 'wp_cache_delete', self::ACCOUNT_OPTION, 'options' );
			}
		} catch ( \Throwable $e ) {
			return;
		}
	}

	/**
	 * Tell whether the account cache should be refreshed.
	 *
	 * @param mixed $cache_contents Raw cache wrapper.
	 * @param bool  $force_refresh  Whether to force refresh.
	 * @return bool
	 */
	private function should_refresh_account_cache( $cache_contents, bool $force_refresh ): bool {
		if ( $force_refresh ) {
			return true;
		}

		if (
			defined( 'DOING_CRON' )
			|| ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() )
			|| $this->refresh_disabled
		) {
			return false;
		}

		if ( false === $cache_contents ) {
			return true;
		}

		if (
			! is_array( $cache_contents )
			|| empty( $cache_contents )
			|| ! array_key_exists( 'data', $cache_contents )
			|| ! isset( $cache_contents['fetched'] )
			|| ! array_key_exists( 'errored', $cache_contents )
		) {
			return true;
		}

		if ( ! $cache_contents['errored'] && ! $this->is_valid_cached_account( $cache_contents['data'] ) ) {
			return true;
		}

		return $this->is_account_cache_expired( $cache_contents );
	}

	/**
	 * Tell whether account cache contents are expired.
	 *
	 * @param array<string,mixed> $cache_contents Account cache wrapper.
	 * @return bool
	 */
	private function is_account_cache_expired( array $cache_contents ): bool {
		$fetched = is_numeric( $cache_contents['fetched'] ?? null ) ? (int) $cache_contents['fetched'] : 0;
		$ttl     = $this->get_account_cache_ttl( $cache_contents );

		try {
			$now = (int) $this->legacy_proxy->call_function( 'time' );
		} catch ( \Throwable $e ) {
			$now = time();
		}

		return $fetched + $ttl < $now;
	}

	/**
	 * Get the account cache TTL for the current request context.
	 *
	 * @param array<string,mixed> $cache_contents Account cache wrapper.
	 * @return int
	 */
	private function get_account_cache_ttl( array $cache_contents ): int {
		if ( is_admin() ) {
			$ttl = ! empty( $cache_contents['errored'] )
				? $this->get_errored_account_cache_ttl( (int) ( $cache_contents['consecutive_errors'] ?? 0 ) )
				: self::ACCOUNT_CACHE_ADMIN_TTL;
		} else {
			$ttl = self::ACCOUNT_CACHE_FRONTEND_TTL;
		}

		/**
		 * Filters the WooPayments account database cache TTL.
		 *
		 * @since 11.0.0
		 *
		 * @param int                 $ttl            Cache TTL in seconds.
		 * @param string              $key            Cache option key.
		 * @param array<string,mixed> $cache_contents Account cache wrapper.
		 */
		return (int) apply_filters( 'wcpay_database_cache_ttl', $ttl, self::ACCOUNT_OPTION, $cache_contents );
	}

	/**
	 * Get the progressive backoff TTL for errored account cache refreshes.
	 *
	 * @param int $consecutive_errors Consecutive error count.
	 * @return int
	 */
	private function get_errored_account_cache_ttl( int $consecutive_errors ): int {
		$index = max( 0, min( count( self::ACCOUNT_CACHE_ERRORED_TTL_LADDER ) - 1, $consecutive_errors - 1 ) );

		return self::ACCOUNT_CACHE_ERRORED_TTL_LADDER[ $index ];
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
	 * Get a persisted WooPayments gateway setting.
	 *
	 * @param string $key      Setting key.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	public function get_gateway_setting( string $key, $fallback = null ) {
		$settings = $this->get_gateway_settings();

		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $fallback;
	}

	/**
	 * Tell whether WooPayments is in test-mode onboarding.
	 *
	 * @return bool
	 */
	public function is_test_mode_onboarding_enabled(): bool {
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
	public function is_dev_mode_enabled(): bool {
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
	 * Get the native WooPayments API client when account data needs a live refresh.
	 *
	 * @return WooPaymentsApiClient|null
	 */
	protected function get_api_client(): ?WooPaymentsApiClient {
		if ( ! function_exists( 'wc_get_container' ) ) {
			return null;
		}

		try {
			return wc_get_container()->get( WooPaymentsApiClient::class );
		} catch ( \Throwable $e ) {
			return null;
		}
	}

	/**
	 * Get the WooCommerce store ID sent with account refresh requests.
	 *
	 * @return string
	 */
	private function get_woocommerce_store_id(): string {
		$option_name = class_exists( '\WC_Install' ) && defined( '\WC_Install::STORE_ID_OPTION' )
			? \WC_Install::STORE_ID_OPTION
			: 'woocommerce_store_id';

		try {
			$store_id = $this->legacy_proxy->call_function( 'get_option', $option_name, '' );
		} catch ( \Throwable $e ) {
			return '';
		}

		return is_scalar( $store_id ) ? (string) $store_id : '';
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
	 * Validate refreshed account data before caching it.
	 *
	 * @param mixed $account_data Account data.
	 * @return bool
	 */
	private function is_valid_cached_account( $account_data ): bool {
		if ( null === $account_data || false === $account_data ) {
			return false;
		}

		if ( ! is_array( $account_data ) ) {
			return false;
		}

		if ( array() === $account_data ) {
			return true;
		}

		if ( $this->is_truthy( $account_data['is_live'] ?? false ) ) {
			return true;
		}

		if ( ! $this->is_onboarding_test_mode_enabled() && $this->is_dev_mode_enabled() ) {
			try {
				$this->legacy_proxy->call_function( 'update_option', self::ONBOARDING_TEST_MODE_OPTION, 'yes' );
				$this->legacy_proxy->call_function( 'wp_cache_delete', self::ONBOARDING_TEST_MODE_OPTION, 'options' );
			} catch ( \Throwable $e ) {
				return $this->is_test_mode_onboarding_enabled();
			}
		}

		return $this->is_test_mode_onboarding_enabled();
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
