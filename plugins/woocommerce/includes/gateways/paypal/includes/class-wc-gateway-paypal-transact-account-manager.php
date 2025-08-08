<?php
/**
 * Class WC_Gateway_Paypal_Request file.
 *
 * @package WooCommerce\Gateways
 */

declare(strict_types=1);

use Automattic\WooCommerce\Utilities\NumberUtil;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\Jetpack\Connection\Client as Jetpack_Connection_Client;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles Transact account management.
 */
class WC_Gateway_Paypal_Transact_Account_Manager {
	/**
	 * The API version for the proxy endpoint.
	 *
	 * @var int
	 */
	private const WPCOM_PROXY_ENDPOINT_API_VERSION = 2;

	/**
	 * Transact provider type, for provider onboarding.
	 *
	 * @var string
	 */
	private const TRANSACT_PROVIDER_TYPE = 'paypal_standard';

	/**
	 * Cache keys for the merchant and provider accounts.
	 *
	 * @var string
	 */
	private const TRANSACT_MERCHANT_ACCOUNT_CACHE_KEY_LIVE = 'woocommerce_paypal_transact_merchant_account_live';
	private const TRANSACT_MERCHANT_ACCOUNT_CACHE_KEY_TEST = 'woocommerce_paypal_transact_merchant_account_test';
	private const TRANSACT_PROVIDER_ACCOUNT_CACHE_KEY_LIVE = 'woocommerce_paypal_transact_provider_account_live';
	private const TRANSACT_PROVIDER_ACCOUNT_CACHE_KEY_TEST = 'woocommerce_paypal_transact_provider_account_test';

	/**
	 * The expiry time for the Transact account cache.
	 *
	 * @var int
	 */
	private const TRANSACT_ACCOUNT_CACHE_EXPIRY = 60 * 60 * 24; // 24 hours.

	/**
	 * Paypal gateway object.
	 *
	 * @var WC_Gateway_Paypal
	 */
	private $gateway;

	/**
	 * Constructor.
	 *
	 * @param WC_Gateway_Paypal $gateway Paypal gateway object.
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Onboard the merchant with the Transact platform.
	 *
	 * @return void
	 */
	public function do_onboarding() {
		// Check that we have a PayPal email -- this is required for Transact onboarding
		// and for payment processing (payee email).
		if ( empty( $this->gateway->email ) ) {
			return;
		}

		// Register with Jetpack if not already connected.
		$jetpack_connection_manager = $this->gateway->get_jetpack_connection_manager();
		if ( ! $jetpack_connection_manager || ! $jetpack_connection_manager->is_connected() ) {
			$result = $jetpack_connection_manager->try_registration();
			if ( is_wp_error( $result ) ) {
				WC_Gateway_Paypal::log( 'Jetpack registration failed: ' . $result->get_error_message(), 'error' );
				return;
			}
		}

		// Fetch (cached) or create the Transact merchant and provider accounts.
		$merchant_account_data = $this->get_merchant_account_data();
		if ( empty( $merchant_account_data ) ) {
			$merchant_account = $this->create_merchant_account();
			if ( empty( $merchant_account ) ) {
				WC_Gateway_Paypal::log( 'Transact merchant onboarding failed.', 'error' );
				return;
			}
		}

		$provider_account_data = $this->get_provider_account_data();
		if ( empty( $provider_account_data ) ) {
			$provider_account = $this->create_provider_account();
			if ( ! $provider_account ) {
				WC_Gateway_Paypal::log( 'Transact provider onboarding failed.', 'error' );
				return;
			}
		}
	}

	/**
	 * Get the Transact merchant account data. Performs a fetch if the account
	 * is not in cache or expired.
	 *
	 * @return array|null Returns null if the merchant account cannot be retrieved.
	 */
	public function get_merchant_account_data() {
		// Get merchant account from cache. If not found, fetch/create it.
		$merchant_account = $this->get_merchant_account_from_cache();
		if ( empty( $merchant_account ) ) {
			$merchant_account = $this->fetch_merchant_account();

			// Fetch failed.
			if ( empty( $merchant_account ) ) {
				return null;
			}

			// Update cache.
			$this->update_merchant_account_cache( $merchant_account );
		}

		return $merchant_account;
	}

	/**
	 * Get the Transact provider account data. Performs a fetch if the account
	 * is not in cache or expired.
	 *
	 * @return array|null Returns null if the provider account cannot be retrieved.
	 */
	public function get_provider_account_data() {
		// Get provider account from cache. If not found, fetch/create it.
		$provider_account = $this->get_provider_account_from_cache();
		if ( empty( $provider_account ) ) {
			$provider_account = $this->fetch_provider_account();
			// Fetch failed.
			if ( empty( $provider_account ) ) {
				return null;
			}

			// Update cache.
			$this->update_provider_account_cache( $provider_account );
		}

		return $provider_account;
	}

	/**
	 * Fetch the merchant account from the Transact platform.
	 *
	 * @return array|null The API response body, or null if the request fails.
	 */
	private function fetch_merchant_account() {
		$site_id = \Jetpack_Options::get_option( 'id' );
		if ( ! $site_id ) {
			return null;
		}

		$request_body = array(
			'test_mode' => $this->gateway->testmode,
		);

		$response = Jetpack_Connection_Client::wpcom_json_api_request_as_blog(
			sprintf( '/sites/%d/transact/account', $site_id ),
			self::WPCOM_PROXY_ENDPOINT_API_VERSION,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'method'  => 'GET',
				'timeout' => 60,
			),
			$request_body,
			'wpcom'
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $response_data['public_id'] ) ) {
			return null;
		}

		// TODO: Remove me.
		error_log( 'Merchant account fetched: ' . print_r( $response_data, true ) );

		return array(
			'public_id' => $response_data['public_id'],
			'email'     => $response_data['metadata']['email'] ?? '',
		);
	}

	/**
	 * Fetch the provider account from the Transact platform.
	 *
	 * @return bool True if the provider account exists, false otherwise.
	 */
	private function fetch_provider_account() {
		$site_id = \Jetpack_Options::get_option( 'id' );
		if ( ! $site_id ) {
			return false;
		}

		$request_body = array(
			'test_mode'     => $this->gateway->testmode,
			'provider_type' => self::TRANSACT_PROVIDER_TYPE,
		);

		$response = Jetpack_Connection_Client::wpcom_json_api_request_as_blog(
			sprintf( '/sites/%d/transact/account/%s', $site_id, self::TRANSACT_PROVIDER_TYPE ),
			self::WPCOM_PROXY_ENDPOINT_API_VERSION,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'method'  => 'GET',
				'timeout' => 60,
			),
			$request_body,
			'wpcom'
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		// TODO: Remove me.
		error_log( 'Provider account fetched: ' . print_r( $response, true ) );

		// Provider account response only returns an empty onboarding link,
		// which we do not need.
		return true;
	}

	/**
	 * Create the merchant account with the Transact platform.
	 *
	 * @return array|null The API response body, or null if the request fails.
	 */
	private function create_merchant_account() {
		$site_id = \Jetpack_Options::get_option( 'id' );
		if ( ! $site_id ) {
			return null;
		}

		$request_body = array(
			'test_mode' => $this->gateway->testmode,
			'email'     => $this->gateway->email,
		);

		$response = Jetpack_Connection_Client::wpcom_json_api_request_as_blog(
			sprintf( '/sites/%d/transact/account', $site_id ),
			self::WPCOM_PROXY_ENDPOINT_API_VERSION,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'method'  => 'POST',
				'timeout' => 60,
			),
			wp_json_encode( $request_body ),
			'wpcom'
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $response_data['public_id'] ) ) {
			WC_Gateway_Paypal::log( 'Transact merchant account creation failed. Response body: ' . wp_remote_retrieve_body( $response ) );
			return null;
		}

		// TODO: Remove me.
		error_log( 'Merchant account created: ' . print_r( $response_data, true ) );

		return array(
			'public_id' => $response_data['public_id'],
			'email'     => $response_data['metadata']['email'] ?? '',
		);
	}

	/**
	 * Create the provider account with the Transact platform.
	 *
	 * @return bool True if the provider account creation was successful, false otherwise.
	 */
	private function create_provider_account() {
		$site_id = \Jetpack_Options::get_option( 'id' );
		if ( ! $site_id ) {
			return false;
		}

		$request_body = array(
			'test_mode'     => $this->gateway->testmode,
			'provider_type' => self::TRANSACT_PROVIDER_TYPE,
		);
		$response     = Jetpack_Connection_Client::wpcom_json_api_request_as_blog(
			sprintf( '/sites/%d/transact/account/%s/onboard', $site_id, self::TRANSACT_PROVIDER_TYPE ),
			self::WPCOM_PROXY_ENDPOINT_API_VERSION,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'method'  => 'POST',
				'timeout' => 60,
			),
			wp_json_encode( $request_body ),
			'wpcom'
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		// TODO: Remove me.
		error_log( 'Provider account created: ' . print_r( $response, true ) );

		// Provider account response only returns an empty onboarding link,
		// which we do not need.
		return true;
	}

	/**
	 * Update the merchant account cache.
	 *
	 * @param array $merchant_account The merchant account data.
	 */
	private function update_merchant_account_cache( $merchant_account ) {
		$cache_key = $this->gateway->testmode ? self::TRANSACT_MERCHANT_ACCOUNT_CACHE_KEY_TEST : self::TRANSACT_MERCHANT_ACCOUNT_CACHE_KEY_LIVE;
		$expires   = time() + self::TRANSACT_ACCOUNT_CACHE_EXPIRY;
		update_option(
			$cache_key,
			array(
				'account' => $merchant_account,
				'expiry'  => $expires,
			)
		);
	}

	/**
	 * Update the provider account cache.
	 *
	 * @param array $provider_account The provider account data.
	 */
	private function update_provider_account_cache( $provider_account ) {
		$cache_key = $this->gateway->testmode ? self::TRANSACT_PROVIDER_ACCOUNT_CACHE_KEY_TEST : self::TRANSACT_PROVIDER_ACCOUNT_CACHE_KEY_LIVE;
		$expires   = time() + self::TRANSACT_ACCOUNT_CACHE_EXPIRY;
		update_option(
			$cache_key,
			array(
				'account' => $provider_account,
				'expiry'  => $expires,
			)
		);
	}

	/**
	 * Get the merchant account from the database cache.
	 *
	 * @return array|null The merchant account data, or null if the cache is
	 *                    empty or expired.
	 */
	private function get_merchant_account_from_cache() {
		$cache_key        = $this->gateway->testmode ? self::TRANSACT_MERCHANT_ACCOUNT_CACHE_KEY_TEST : self::TRANSACT_MERCHANT_ACCOUNT_CACHE_KEY_LIVE;
		$merchant_account = get_option( $cache_key, null );

		if ( empty( $merchant_account ) || ( isset( $merchant_account['expiry'] ) && $merchant_account['expiry'] < time() ) ) {
			return null;
		}

		// TODO: Remove me.
		error_log( 'Merchant account from cache: ' . print_r( $merchant_account, true ) );

		return $merchant_account;
	}

	/**
	 * Get the provider account from the database cache.
	 *
	 * @return array|null The provider account data, or null if the cache is
	 *                    empty or expired.
	 */
	private function get_provider_account_from_cache() {
		$cache_key        = $this->gateway->testmode ? self::TRANSACT_PROVIDER_ACCOUNT_CACHE_KEY_TEST : self::TRANSACT_PROVIDER_ACCOUNT_CACHE_KEY_LIVE;
		$provider_account = get_option( $cache_key, null );

		if ( empty( $provider_account ) || ( isset( $provider_account['expiry'] ) && $provider_account['expiry'] < time() ) ) {
			return null;
		}

		// TODO: Remove me.
		error_log( 'Provider account from cache: ' . print_r( $provider_account, true ) );

		return $provider_account;
	}
}
