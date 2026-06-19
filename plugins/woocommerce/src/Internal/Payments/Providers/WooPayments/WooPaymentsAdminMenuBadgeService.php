<?php
/**
 * WooPaymentsAdminMenuBadgeService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Provides source-backed WooPayments admin menu badge counts.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsAdminMenuBadgeService {

	private const DISPUTE_STATUS_COUNTS_KEY = 'wcpay_dispute_status_counts_cache';

	private const DISPUTE_STATUS_COUNTS_KEY_TEST_MODE = 'wcpay_test_dispute_status_counts_cache';

	private const AUTHORIZATION_SUMMARY_KEY = 'wcpay_authorization_summary_cache';

	private const AUTHORIZATION_SUMMARY_KEY_TEST_MODE = 'wcpay_test_authorization_summary_cache';

	private const ERRORED_CACHE_RETRY_TTL = MINUTE_IN_SECONDS;

	/**
	 * WooPayments account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private WooPaymentsAccountService $account_service;

	/**
	 * Native WooPayments API client.
	 *
	 * @var WooPaymentsApiClient
	 */
	private WooPaymentsApiClient $api_client;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsAccountService $account_service WooPayments account service.
	 * @param WooPaymentsApiClient      $api_client      Native WooPayments API client.
	 */
	final public function init( WooPaymentsAccountService $account_service, WooPaymentsApiClient $api_client ): void {
		$this->account_service = $account_service;
		$this->api_client      = $api_client;
	}

	/**
	 * Get the number of disputes awaiting a merchant response.
	 *
	 * @return int
	 */
	public function get_disputes_awaiting_response_count(): int {
		$cache_key = $this->account_service->is_test_mode_enabled() ? self::DISPUTE_STATUS_COUNTS_KEY_TEST_MODE : self::DISPUTE_STATUS_COUNTS_KEY;
		$counts    = $this->get_or_add_cached_array(
			$cache_key,
			function (): array {
				return $this->api_client->get_dispute_status_counts();
			}
		);

		return max(
			0,
			(int) ( $counts['needs_response'] ?? 0 ) + (int) ( $counts['warning_needs_response'] ?? 0 )
		);
	}

	/**
	 * Get the number of uncaptured manual-capture transactions.
	 *
	 * @return int
	 */
	public function get_uncaptured_transactions_count(): int {
		if ( ! $this->is_manual_capture_enabled() ) {
			return 0;
		}

		$cache_key = $this->account_service->is_test_mode_enabled() ? self::AUTHORIZATION_SUMMARY_KEY_TEST_MODE : self::AUTHORIZATION_SUMMARY_KEY;
		$summary   = $this->get_or_add_cached_array(
			$cache_key,
			function (): array {
				return $this->api_client->get_authorizations_summary();
			}
		);

		return max( 0, (int) ( $summary['count'] ?? 0 ) );
	}

	/**
	 * Tell whether manual capture is enabled.
	 *
	 * @return bool
	 */
	public function is_manual_capture_enabled(): bool {
		return 'yes' === (string) $this->account_service->get_gateway_setting( 'manual_capture', 'no' );
	}

	/**
	 * Get a cached array, refreshing it when needed and preserving stale valid data on refresh errors.
	 *
	 * @param string   $key       Option key.
	 * @param callable $generator Data generator.
	 * @return array<string,mixed>
	 */
	private function get_or_add_cached_array( string $key, callable $generator ): array {
		$cache_contents = get_option( $key, false );
		$cached_data    = null;

		if ( $this->is_cache_wrapper( $cache_contents ) && is_array( $cache_contents['data'] ) ) {
			$cached_data = $cache_contents['data'];
		}

		if ( ! $this->should_refresh_cache( $key, $cache_contents ) ) {
			return is_array( $cached_data ) ? $cached_data : array();
		}

		$errored = false;
		$data    = $cached_data;

		try {
			$generated = $generator();
			if ( is_array( $generated ) ) {
				$data = $generated;
			} else {
				$errored = true;
			}
		} catch ( Throwable $exception ) {
			$errored = true;
		}

		update_option(
			$key,
			array(
				'data'    => $data,
				'fetched' => time(),
				'errored' => $errored,
			),
			false
		);

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Tell whether the cache should be refreshed.
	 *
	 * @param string $key            Option key.
	 * @param mixed  $cache_contents Cache wrapper.
	 * @return bool
	 */
	private function should_refresh_cache( string $key, $cache_contents ): bool {
		if ( defined( 'DOING_CRON' ) || wp_doing_ajax() ) {
			return false;
		}

		if ( ! $this->is_cache_wrapper( $cache_contents ) ) {
			return true;
		}

		if ( empty( $cache_contents['errored'] ) && ! is_array( $cache_contents['data'] ) ) {
			return true;
		}

		$ttl = (int) apply_filters( 'wcpay_database_cache_ttl', DAY_IN_SECONDS, $key, $cache_contents );
		if ( ! empty( $cache_contents['errored'] ) ) {
			$ttl = min( $ttl, self::ERRORED_CACHE_RETRY_TTL );
		}

		return ( (int) $cache_contents['fetched'] + $ttl ) < time();
	}

	/**
	 * Tell whether a cache wrapper has the expected shape.
	 *
	 * @param mixed $cache_contents Cache wrapper.
	 * @return bool
	 */
	private function is_cache_wrapper( $cache_contents ): bool {
		return is_array( $cache_contents )
			&& array_key_exists( 'data', $cache_contents )
			&& isset( $cache_contents['fetched'] )
			&& array_key_exists( 'errored', $cache_contents );
	}
}
