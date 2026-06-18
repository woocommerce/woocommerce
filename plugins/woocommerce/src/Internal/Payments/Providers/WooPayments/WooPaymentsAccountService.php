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
use RuntimeException;

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

	private const ONBOARDING_STRIPE_CONNECTED_OPTION = '_wcpay_onboarding_stripe_connected';

	private const ONBOARDING_CONNECTION_SUCCESS_MODAL_OPTION = 'wcpay_connection_success_modal_dismissed';

	private const ONBOARDING_STATE_TRANSIENT = 'wcpay_stripe_onboarding_state';

	private const EMBEDDED_KYC_IN_PROGRESS_OPTION = 'wcpay_onboarding_embedded_kyc_in_progress';

	private const WOOPAY_ENABLED_BY_DEFAULT_TRANSIENT = 'woopay_enabled_by_default';

	private const ONBOARDING_INIT_IN_PROGRESS_TRANSIENT = 'wcpay_onboarding_init_in_progress';

	private const TEST_MODE_ENABLED_DATE_OPTION = 'wcpay_test_mode_enabled_date';

	private const TEST_TO_LIVE_NOTICE_ELIGIBLE_TRANSIENT = 'wcpay_test_to_live_eligible';

	private const POST_KYC_ACTIVATION_ELIGIBLE_TRANSIENT = 'wcpay_post_kyc_activation_eligible';

	private const NOX_PROFILE_OPTION = 'woocommerce_woopayments_nox_profile';

	private const NOX_ONBOARDING_LOCKED_OPTION = 'woocommerce_woopayments_nox_onboarding_locked';

	private const ACCOUNT_DELETION_PENDING_OPTION = 'wcpay_account_deletion_pending_id';

	private const DATABASE_CACHE_OPTIONS = array(
		self::ACCOUNT_OPTION,
		'wcpay_address_autocomplete_jwt',
		'wcpay_onboarding_fields_data',
		'wcpay_business_types_data',
		'wcpay_fraud_services_data',
		'wcpay_recommended_payment_methods',
		'wcpay_dispute_status_counts_cache',
		'wcpay_test_dispute_status_counts_cache',
		'wcpay_active_dispute_cache',
		'wcpay_authorization_summary_cache',
		'wcpay_test_authorization_summary_cache',
		'wcpay_connect_incentive',
		'wcpay_tracking_info_cache',
	);

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
	 * Refetch WooPayments account data and fail when fresh data cannot be fetched.
	 *
	 * Normal account reads intentionally fall back to stale data on provider failures for storefront stability. Webhook
	 * account events need a strict path so upstream retries happen instead of acknowledging stale account state.
	 *
	 * @since 11.0.0
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When fresh account data cannot be fetched.
	 */
	public function refresh_account_data_strict(): array {
		$data = $this->fetch_account_data();
		if ( false === $data ) {
			throw new WooPaymentsApiException( 'Unable to refresh WooPayments account data.', 'wcpay_account_refresh_failed', 500 );
		}

		$cache_contents = $this->build_account_cache_contents( $data, false );
		$this->persist_account_cache( $cache_contents );
		if ( ! $this->is_persisted_account_cache( $cache_contents ) ) {
			throw new WooPaymentsApiException( 'Unable to persist refreshed WooPayments account data.', 'wcpay_account_refresh_persist_failed', 500 );
		}

		/**
		 * Allows native WooPayments integrations to react when account data is refreshed.
		 *
		 * @since 11.0.0
		 *
		 * @param array<string,mixed> $data Refreshed WooPayments account data.
		 */
		do_action( 'woocommerce_payments_account_refreshed', $data );

		return $data;
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
	 * Get the account ID currently preserved in the local account cache without forcing a refresh.
	 *
	 * @since 11.0.0
	 * @return string
	 */
	public function get_preserved_account_id(): string {
		$cache = $this->get_account_cache();
		$data  = is_array( $cache ) && isset( $cache['data'] ) && is_array( $cache['data'] ) ? $cache['data'] : array();

		return isset( $data['account_id'] ) && is_scalar( $data['account_id'] )
			? (string) $data['account_id']
			: '';
	}

	/**
	 * Persist the account deletion currently being processed so retries can continue after partial cleanup.
	 *
	 * @since 11.0.0
	 * @param string $account_id Account ID.
	 * @return void
	 * @throws RuntimeException When the marker cannot be persisted.
	 */
	public function mark_account_deletion_pending( string $account_id ): void {
		$this->legacy_proxy->call_function( 'update_option', self::ACCOUNT_DELETION_PENDING_OPTION, $account_id, false );
		$this->legacy_proxy->call_function( 'wp_cache_delete', self::ACCOUNT_DELETION_PENDING_OPTION, 'options' );
		if ( $account_id !== $this->get_pending_account_deletion_id() ) {
			throw new RuntimeException( 'Unable to persist the pending WooPayments account deletion marker.' );
		}
	}

	/**
	 * Get the pending account deletion ID, if any.
	 *
	 * @since 11.0.0
	 * @return string
	 */
	public function get_pending_account_deletion_id(): string {
		$account_id = $this->legacy_proxy->call_function( 'get_option', self::ACCOUNT_DELETION_PENDING_OPTION, '' );

		return is_scalar( $account_id ) ? (string) $account_id : '';
	}

	/**
	 * Clear the pending account deletion marker.
	 *
	 * @since 11.0.0
	 * @return void
	 * @throws RuntimeException When the marker cannot be cleared.
	 */
	public function clear_pending_account_deletion(): void {
		$this->legacy_proxy->call_function( 'delete_option', self::ACCOUNT_DELETION_PENDING_OPTION );
		$this->legacy_proxy->call_function( 'wp_cache_delete', self::ACCOUNT_DELETION_PENDING_OPTION, 'options' );
		if ( '' !== $this->get_pending_account_deletion_id() ) {
			throw new RuntimeException( 'Unable to clear the pending WooPayments account deletion marker.' );
		}
	}

	/**
	 * Reset preserved WooPayments account/onboarding state after the provider account is deleted.
	 *
	 * This mirrors the extension account-reset cleanup for the Core-owned native runtime without performing a live account
	 * refresh. The webhook handler refreshes account data after cleanup so the platform can drive the next steps.
	 *
	 * @since 11.0.0
	 * @return void
	 */
	public function cleanup_after_account_reset(): void {
		$settings = $this->get_gateway_settings();

		$settings['enabled']                        = 'no';
		$settings['test_mode']                      = 'no';
		$settings['upe_enabled_payment_method_ids'] = array( 'card' );

		$this->legacy_proxy->call_function( 'update_option', self::SETTINGS_OPTION, $settings );
		$this->legacy_proxy->call_function( 'update_option', self::ONBOARDING_STRIPE_CONNECTED_OPTION, array() );
		$this->legacy_proxy->call_function( 'update_option', self::ONBOARDING_TEST_MODE_OPTION, 'no' );
		$this->legacy_proxy->call_function( 'delete_option', self::ONBOARDING_CONNECTION_SUCCESS_MODAL_OPTION );
		$this->legacy_proxy->call_function( 'delete_transient', self::ONBOARDING_STATE_TRANSIENT );
		$this->legacy_proxy->call_function( 'delete_option', self::EMBEDDED_KYC_IN_PROGRESS_OPTION );
		$this->legacy_proxy->call_function( 'delete_transient', self::WOOPAY_ENABLED_BY_DEFAULT_TRANSIENT );
		$this->legacy_proxy->call_function( 'delete_transient', self::ONBOARDING_INIT_IN_PROGRESS_TRANSIENT );
		$this->legacy_proxy->call_function( 'delete_option', self::TEST_MODE_ENABLED_DATE_OPTION );
		$this->legacy_proxy->call_function( 'delete_transient', self::TEST_TO_LIVE_NOTICE_ELIGIBLE_TRANSIENT );
		$this->legacy_proxy->call_function( 'delete_transient', self::POST_KYC_ACTIVATION_ELIGIBLE_TRANSIENT );
		$this->legacy_proxy->call_function( 'delete_transient', self::ONBOARDING_DISABLED_TRANSIENT );
		$this->legacy_proxy->call_function( 'delete_option', self::NOX_PROFILE_OPTION );
		$this->legacy_proxy->call_function( 'delete_option', self::NOX_ONBOARDING_LOCKED_OPTION );

		$this->clear_preserved_database_cache();
	}

	/**
	 * Clear preserved WooPayments database cache keys that hinge on the connected account.
	 *
	 * @return void
	 */
	private function clear_preserved_database_cache(): void {
		$this->account_cache        = false;
		$this->account_cache_loaded = false;

		foreach ( self::DATABASE_CACHE_OPTIONS as $option_name ) {
			$this->legacy_proxy->call_function( 'delete_option', $option_name );
			$this->legacy_proxy->call_function( 'wp_cache_delete', $option_name, 'options' );
		}
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
		try {
			$this->persist_account_cache( $this->build_account_cache_contents( $account_data, $errored ) );
		} catch ( \Throwable $e ) {
			return;
		}
	}

	/**
	 * Build account cache contents with metadata.
	 *
	 * @param mixed $account_data Account data.
	 * @param bool  $errored      Whether the refresh that produced this write errored.
	 * @return array<string,mixed>
	 */
	private function build_account_cache_contents( $account_data, bool $errored ): array {
		$consecutive_errors = 0;
		if ( $errored ) {
			$previous           = $this->get_account_cache();
			$previous_count     = is_array( $previous ) && isset( $previous['consecutive_errors'] )
				? (int) $previous['consecutive_errors']
				: 0;
			$consecutive_errors = $previous_count + 1;
		}

		return array(
			'data'               => $account_data,
			'fetched'            => $this->legacy_proxy->call_function( 'time' ),
			'errored'            => $errored,
			'consecutive_errors' => $consecutive_errors,
		);
	}

	/**
	 * Persist account cache contents.
	 *
	 * @param array<string,mixed> $cache_contents Account cache contents.
	 * @return void
	 */
	private function persist_account_cache( array $cache_contents ): void {
		$this->account_cache        = $cache_contents;
		$this->account_cache_loaded = true;

		$result = $this->legacy_proxy->call_function( 'update_option', self::ACCOUNT_OPTION, $cache_contents, 'no' );
		if ( false !== $result ) {
			$this->legacy_proxy->call_function( 'wp_cache_delete', self::ACCOUNT_OPTION, 'options' );
		}
	}

	/**
	 * Tell whether account cache contents were durably persisted.
	 *
	 * @param array<string,mixed> $cache_contents Expected account cache contents.
	 * @return bool
	 */
	private function is_persisted_account_cache( array $cache_contents ): bool {
		try {
			$this->legacy_proxy->call_function( 'wp_cache_delete', self::ACCOUNT_OPTION, 'options' );
			$persisted = $this->legacy_proxy->call_function( 'get_option', self::ACCOUNT_OPTION );
		} catch ( \Throwable $e ) {
			return false;
		}

		return $cache_contents === $persisted;
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
	 * Get the connected WooPayments account default currency.
	 *
	 * @since 11.0.0
	 *
	 * @return string Lowercase account default currency, or usd when unavailable.
	 */
	public function get_account_default_currency(): string {
		$account_data     = $this->get_cached_account_data();
		$store_currencies = is_array( $account_data['store_currencies'] ?? null ) ? $account_data['store_currencies'] : array();
		$default_currency = $store_currencies['default'] ?? 'usd';

		return is_scalar( $default_currency ) ? strtolower( trim( (string) $default_currency ) ) : 'usd';
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
	 * Tell whether the cached account is rejected.
	 *
	 * @return bool
	 */
	public function is_account_rejected(): bool {
		$account_data = $this->get_cached_account_data();
		$status       = $account_data['status'] ?? '';

		return $this->has_account()
			&& is_scalar( $status )
			&& str_starts_with( (string) $status, 'rejected' );
	}

	/**
	 * Tell whether the cached account is under review.
	 *
	 * @return bool
	 */
	public function is_account_under_review(): bool {
		$account_data = $this->get_cached_account_data();

		return $this->has_account() && 'under_review' === ( $account_data['status'] ?? null );
	}

	/**
	 * Tell whether the cached account completed details submission.
	 *
	 * @return bool
	 */
	public function is_details_submitted(): bool {
		$account_data = $this->get_cached_account_data();

		return $this->is_truthy( $account_data['details_submitted'] ?? false );
	}

	/**
	 * Tell whether the cached account is valid for native WooPayments admin navigation.
	 *
	 * @return bool
	 */
	public function has_valid_account_for_admin_navigation(): bool {
		$account_data = $this->get_cached_account_data();
		$capabilities = is_array( $account_data['capabilities'] ?? null ) ? $account_data['capabilities'] : array();

		return $this->has_account()
			&& $this->is_details_submitted()
			&& isset( $capabilities['card_payments'] )
			&& 'unrequested' !== $capabilities['card_payments'];
	}

	/**
	 * Tell whether the cached account is eligible for in-person payments.
	 *
	 * @return bool
	 */
	public function is_card_present_eligible(): bool {
		$account_data = $this->get_cached_account_data();

		return $this->has_account() && $this->is_truthy( $account_data['card_present_eligible'] ?? false );
	}

	/**
	 * Tell whether the cached account has card readers available.
	 *
	 * @return bool
	 */
	public function has_card_readers_available(): bool {
		$account_data = $this->get_cached_account_data();

		return $this->has_account() && $this->is_truthy( $account_data['has_card_readers_available'] ?? false );
	}

	/**
	 * Tell whether the cached account has previous Capital loans.
	 *
	 * @return bool
	 */
	public function has_previous_capital_loans(): bool {
		$account_data = $this->get_cached_account_data();
		$capital      = is_array( $account_data['capital'] ?? null ) ? $account_data['capital'] : array();

		return $this->has_account() && $this->is_truthy( $capital['has_previous_loans'] ?? false );
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
