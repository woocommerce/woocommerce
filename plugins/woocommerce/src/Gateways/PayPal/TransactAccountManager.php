<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Gateways\PayPal;

use Automattic\Jetpack\Connection\Client as Jetpack_Connection_Client;
use Automattic\WooCommerce\Gateways\PayPal\Constants as PayPalConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PayPal TransactAccountManager Class
 *
 * Handles Transact account management.
 *
 * @since 10.5.0
 */
final class TransactAccountManager {
	/**
	 * The API version for the proxy endpoint.
	 *
	 * @var int
	 *
	 * @since 10.5.0
	 */
	private const WPCOM_PROXY_ENDPOINT_API_VERSION = 2;

	/**
	 * Transact provider type, for provider onboarding.
	 *
	 * @var string
	 *
	 * @since 10.5.0
	 */
	private const TRANSACT_PROVIDER_TYPE = 'paypal_standard';

	/**
	 * Cache key for the merchant account in live mode.
	 *
	 * @var string
	 *
	 * @since 10.5.0
	 */
	private const TRANSACT_MERCHANT_ACCOUNT_CACHE_KEY_LIVE = 'woocommerce_paypal_transact_merchant_account_live';

	/**
	 * Cache key for the merchant account in test mode.
	 *
	 * @var string
	 *
	 * @since 10.5.0
	 */
	private const TRANSACT_MERCHANT_ACCOUNT_CACHE_KEY_TEST = 'woocommerce_paypal_transact_merchant_account_test';

	/**
	 * Cache key for the provider account in live mode.
	 *
	 * @var string
	 *
	 * @since 10.5.0
	 */
	private const TRANSACT_PROVIDER_ACCOUNT_CACHE_KEY_LIVE = 'woocommerce_paypal_transact_provider_account_live';

	/**
	 * Cache key for the provider account in test mode.
	 *
	 * @var string
	 *
	 * @since 10.5.0
	 */
	private const TRANSACT_PROVIDER_ACCOUNT_CACHE_KEY_TEST = 'woocommerce_paypal_transact_provider_account_test';

	/**
	 * The expiry time for the Transact account cache.
	 *
	 * @var int
	 *
	 * @since 10.5.0
	 */
	private const TRANSACT_ACCOUNT_CACHE_EXPIRY = 60 * 60 * 24; // 24 hours.

	/**
	 * Paypal gateway object.
	 *
	 * @var \WC_Gateway_Paypal
	 */
	private \WC_Gateway_Paypal $gateway;

	/**
	 * Constructor.
	 *
	 * @param \WC_Gateway_Paypal $gateway Paypal gateway object.
	 */
	public function __construct( \WC_Gateway_Paypal $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Onboard the merchant with the Transact platform.
	 *
	 * @return void
	 *
	 * @since 10.5.0
	 */
	public function do_onboarding(): void {
		// Check that we have a PayPal email. This is required for processing payments --
		// used as the payee email. Only begin onboarding if this minimum requirement is met.
		if ( empty( $this->gateway->email ) ) {
			return;
		}

		// Register with Jetpack if not already connected.
		$jetpack_connection_manager = $this->gateway->get_jetpack_connection_manager();
		if ( ! $jetpack_connection_manager ) {
			\WC_Gateway_Paypal::log( 'Jetpack connection manager not found.', 'error' );
			return;
		}

		if ( ! $jetpack_connection_manager->is_connected() ) {
			$result = $jetpack_connection_manager->try_registration();
			if ( is_wp_error( $result ) ) {
				\WC_Gateway_Paypal::log( 'Jetpack registration failed: ' . $result->get_error_message(), 'error' );
				return;
			}
		}

		// Fetch (cached) or create the Transact merchant and provider accounts.
		$merchant_account_data = $this->get_transact_account_data( 'merchant' );
		if ( empty( $merchant_account_data ) ) {
			$merchant_account = $this->create_merchant_account();
			if ( empty( $merchant_account ) ) {
				\WC_Gateway_Paypal::log( 'Transact merchant onboarding failed.', 'error' );
				return;
			}

			// Cache the merchant account data.
			$this->update_transact_account_cache(
				$this->get_cache_key( 'merchant' ),
				$merchant_account
			);
		}

		$provider_account_data = $this->get_transact_account_data( 'provider' );
		if ( empty( $provider_account_data ) ) {
			$provider_account = $this->create_provider_account();
			if ( ! $provider_account ) {
				\WC_Gateway_Paypal::log( 'Transact provider onboarding failed.', 'error' );
				return;
			}

			// Cache the provider account data.
			$this->update_transact_account_cache(
				$this->get_cache_key( 'provider' ),
				$provider_account
			);
		}

		// Set an extra flag to indicate that we've completed onboarding,
		// so we can do inexpensive early returns for checkers like
		// WC_Gateway_Paypal::should_use_orders_v2().
		$this->gateway->set_transact_onboarding_complete();
	}

	/**
	 * Get the Transact account (merchant or provider) data. Performs a fetch if the account
	 * is not in cache or expired.
	 *
	 * @since 10.5.0
	 *
	 * @param string $account_type The type of account to get (merchant or provider).
	 * @return mixed Returns null if the transact account cannot be retrieved.
	 */
	public function get_transact_account_data( string $account_type ) {
		$cache_key = $this->get_cache_key( $account_type );

		// Get transact account from cache. If not found, fetch/create it.
		$transact_account = $this->get_transact_account_from_cache( $cache_key );
		if ( empty( $transact_account ) ) {
			$transact_account = 'merchant' === $account_type ? $this->fetch_merchant_account() : $this->fetch_provider_account();

			// Fetch failed.
			if ( empty( $transact_account ) ) {
				return null;
			}

			// Update cache.
			$this->update_transact_account_cache( $cache_key, $transact_account );
		}

		return $transact_account;
	}

	/**
	 * Get the cache key for the transact account.
	 *
	 * @since 10.5.0
	 *
	 * @param string $account_type The type of account to get (merchant or provider).
	 * @return string|null The cache key, or null if the account type is invalid.
	 */
	private function get_cache_key( string $account_type ): ?string {
		if ( 'merchant' === $account_type ) {
			return $this->gateway->testmode ? self::TRANSACT_MERCHANT_ACCOUNT_CACHE_KEY_TEST : self::TRANSACT_MERCHANT_ACCOUNT_CACHE_KEY_LIVE;
		}

		if ( 'provider' === $account_type ) {
			return $this->gateway->testmode ? self::TRANSACT_PROVIDER_ACCOUNT_CACHE_KEY_TEST : self::TRANSACT_PROVIDER_ACCOUNT_CACHE_KEY_LIVE;
		}

		return null;
	}

	/**
	 * Fetch the merchant account from the Transact platform.
	 *
	 * @since 10.5.0
	 *
	 * @return array|null The API response body, or null if the request fails.
	 */
	private function fetch_merchant_account(): ?array {
		$site_id = \Jetpack_Options::get_option( 'id' );
		if ( ! $site_id ) {
			return null;
		}

		$request_body = array(
			'test_mode' => $this->gateway->testmode,
		);

		$response = $this->send_transact_api_request(
			'GET',
			sprintf( '/sites/%d/transact/account', $site_id ),
			$request_body
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $response_data['public_id'] ) ) {
			return null;
		}

		return array( 'public_id' => $response_data['public_id'] );
	}

	/**
	 * Fetch the provider account from the Transact platform.
	 *
	 * @since 10.5.0
	 *
	 * @return bool True if the provider account exists, false otherwise.
	 */
	private function fetch_provider_account(): bool {
		$site_id = \Jetpack_Options::get_option( 'id' );
		if ( ! $site_id ) {
			return false;
		}

		$request_body = array(
			'test_mode'     => $this->gateway->testmode,
			'provider_type' => self::TRANSACT_PROVIDER_TYPE,
		);

		$response = $this->send_transact_api_request(
			'GET',
			sprintf( '/sites/%d/transact/account/%s', $site_id, self::TRANSACT_PROVIDER_TYPE ),
			$request_body
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		// Provider account response only returns an empty onboarding link,
		// which we do not need.
		return true;
	}

	/**
	 * Create the merchant account with the Transact platform.
	 *
	 * @since 10.5.0
	 *
	 * @return array|null The API response body, or null if the request fails.
	 */
	private function create_merchant_account(): ?array {
		$site_id = \Jetpack_Options::get_option( 'id' );
		if ( ! $site_id ) {
			return null;
		}

		$request_body = array( 'test_mode' => $this->gateway->testmode );

		$response = $this->send_transact_api_request(
			'POST',
			sprintf( '/sites/%d/transact/account', $site_id ),
			$request_body
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $response_data['public_id'] ) ) {
			\WC_Gateway_Paypal::log( 'Transact merchant account creation failed. Response body: ' . wc_print_r( $response_data, true ) );
			return null;
		}

		return array( 'public_id' => $response_data['public_id'] );
	}

	/**
	 * Create the provider account with the Transact platform.
	 *
	 * @since 10.5.0
	 *
	 * @return bool True if the provider account creation was successful, false otherwise.
	 */
	private function create_provider_account(): bool {
		$site_id = \Jetpack_Options::get_option( 'id' );
		if ( ! $site_id ) {
			return false;
		}

		$request_body = array(
			'test_mode'     => $this->gateway->testmode,
			'provider_type' => self::TRANSACT_PROVIDER_TYPE,
		);
		$response     = $this->send_transact_api_request(
			'POST',
			sprintf( '/sites/%d/transact/account/%s/onboard', $site_id, self::TRANSACT_PROVIDER_TYPE ),
			$request_body
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		// Provider account response only returns an empty onboarding link,
		// which we do not need.
		return true;
	}

	/**
	 * Update the transact account (merchant or provider) cache.
	 *
	 * @since 10.5.0
	 *
	 * @param string|null $cache_key The cache key to update.
	 * @param array|bool  $account_data The transact account data.
	 *
	 * @return void
	 */
	private function update_transact_account_cache( string $cache_key, $account_data ): void {
		$expires = time() + self::TRANSACT_ACCOUNT_CACHE_EXPIRY;
		update_option(
			$cache_key,
			array(
				'account' => $account_data,
				'expiry'  => $expires,
			)
		);
	}

	/**
	 * Get the transact account (merchant or provider) from the database cache.
	 *
	 * @since 10.5.0
	 *
	 * @param string $cache_key The cache key to get the account.
	 * @return mixed The transact account data, or null if the cache is
	 *                    empty or expired.
	 */
	private function get_transact_account_from_cache( string $cache_key ) {
		$transact_account = get_option( $cache_key, null );

		if ( empty( $transact_account ) || ( isset( $transact_account['expiry'] ) && $transact_account['expiry'] < time() ) ) {
			return null;
		}

		return $transact_account['account'] ?? null;
	}

	/**
	 * Send a request to the Transact platform.
	 *
	 * @since 10.5.0
	 *
	 * @param string $method The HTTP method to use.
	 * @param string $endpoint The endpoint to request.
	 * @param array  $request_body The request body.
	 *
	 * @return array|\WP_Error The API response body, or null if the request fails.
	 */
	private function send_transact_api_request( string $method, string $endpoint, array $request_body ) {
		if ( 'GET' === $method ) {
			$endpoint .= '?' . http_build_query( $request_body );
		}

		$response = Jetpack_Connection_Client::wpcom_json_api_request_as_blog(
			$endpoint,
			(string) self::WPCOM_PROXY_ENDPOINT_API_VERSION,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'method'  => $method,
				'timeout' => PayPalConstants::WPCOM_PROXY_REQUEST_TIMEOUT,
			),
			'GET' === $method ? null : wp_json_encode( $request_body ),
			'wpcom'
		);

		return $response;
	}
}
