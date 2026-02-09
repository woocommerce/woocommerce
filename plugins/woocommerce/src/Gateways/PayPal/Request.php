<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Gateways\PayPal;

use Exception;
use WC_Order;
use Automattic\WooCommerce\Gateways\PayPal\Constants as PayPalConstants;
use Automattic\WooCommerce\Gateways\PayPal\AddressRequirements as PayPalAddressRequirements;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\Jetpack\Connection\Client as Jetpack_Connection_Client;

defined( 'ABSPATH' ) || exit;

/**
 * PayPal Request Class
 *
 * Handles PayPal API requests for creating orders, authorizing/capturing payments,
 * and fetching PayPal order details using the Orders v2 API.
 *
 * @since 10.5.0
 */
class Request {

	/**
	 * The PayPal gateway instance.
	 *
	 * @var \WC_Gateway_Paypal
	 */
	private \WC_Gateway_Paypal $gateway;

	/**
	 * The API version for the proxy endpoint.
	 *
	 * @var string
	 */
	private const WPCOM_PROXY_ENDPOINT_API_VERSION = '2';

	/**
	 * The base for the proxy REST endpoint.
	 *
	 * @var string
	 */
	private const WPCOM_PROXY_REST_BASE = 'transact/paypal_standard/proxy';

	/**
	 * Proxy REST endpoints.
	 *
	 * @var string
	 */
	private const WPCOM_PROXY_ORDER_ENDPOINT                = 'order';
	private const WPCOM_PROXY_PAYMENT_CAPTURE_ENDPOINT      = 'payment/capture';
	private const WPCOM_PROXY_PAYMENT_AUTHORIZE_ENDPOINT    = 'payment/authorize';
	private const WPCOM_PROXY_PAYMENT_CAPTURE_AUTH_ENDPOINT = 'payment/capture_auth';
	private const WPCOM_PROXY_CLIENT_ID_ENDPOINT            = 'client_id';

	/**
	 * Constructor.
	 *
	 * @param \WC_Gateway_Paypal $gateway Paypal gateway object.
	 */
	public function __construct( \WC_Gateway_Paypal $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Create a PayPal order using the Orders v2 API.
	 *
	 * This method creates a PayPal order and returns the order details including
	 * the approval URL where customers will be redirected to complete payment.
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $payment_source The payment source.
	 * @param array    $js_sdk_params Extra parameters for a PayPal JS SDK (Buttons) request.
	 * @return array|null
	 * @throws Exception If the PayPal order creation fails.
	 */
	public function create_paypal_order(
		WC_Order $order,
		string $payment_source = PayPalConstants::PAYMENT_SOURCE_PAYPAL,
		array $js_sdk_params = array()
	): ?array {
		$paypal_debug_id = null;

		// While PayPal JS SDK can return 'paylater' as the payment source in the createOrder callback,
		// Orders v2 API does not accept it. We will use 'paypal' instead.
		// Accepted payment_source values for Orders v2:
		// https://developer.paypal.com/docs/api/orders/v2/#orders_create!ct=application/json&path=payment_source&t=request.
		if ( PayPalConstants::PAYMENT_SOURCE_PAYLATER === $payment_source ) {
			$payment_source = PayPalConstants::PAYMENT_SOURCE_PAYPAL;
		}

		try {
			$request_body = array(
				'test_mode' => $this->gateway->testmode,
				'order'     => $this->get_paypal_create_order_request_params( $order, $payment_source, $js_sdk_params ),
			);
			$response     = $this->send_wpcom_proxy_request( 'POST', self::WPCOM_PROXY_ORDER_ENDPOINT, $request_body );

			if ( is_wp_error( $response ) ) {
				throw new Exception( 'PayPal order creation failed. Response error: ' . $response->get_error_message() );
			}

			if ( ! is_array( $response ) ) {
				throw new Exception( 'PayPal order creation failed. Invalid response type.' );
			}

			$http_code     = wp_remote_retrieve_response_code( $response );
			$body          = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $body, true );

			$response_array = is_array( $response_data ) ? $response_data : array();

			/**
			 * Fires after receiving a response from PayPal order creation.
			 *
			 * This hook allows extensions to react to PayPal API responses, such as
			 * displaying admin notices or logging response data.
			 *
			 * Note: This hook fires on EVERY order creation attempt (success or failure),
			 * and can be called multiple times for the same order if retried. Extensions
			 * hooking this should be idempotent and check order state/meta before taking
			 * action to avoid duplicate processing.
			 *
			 * @since 10.4.0
			 *
			 * @param int|string $http_code     The HTTP status code from the PayPal API response.
			 * @param array      $response_data The decoded response data from the PayPal API
			 * @param WC_Order   $order         The WooCommerce order object.
			 */
			do_action( 'woocommerce_paypal_standard_order_created_response', $http_code, $response_array, $order );

			if ( ! in_array( $http_code, array( 200, 201 ), true ) ) {
				$paypal_debug_id = isset( $response_data['debug_id'] ) ? $response_data['debug_id'] : null;
				throw new Exception( 'PayPal order creation failed. Response status: ' . $http_code . '. Response body: ' . $body );
			}

			$redirect_url = null;
			if ( empty( $js_sdk_params['is_js_sdk_flow'] ) ) {
				// We only need an approve link for the classic, redirect flow.
				$redirect_url = $this->get_approve_link( $http_code, $response_data );
				if ( empty( $redirect_url ) ) {
					throw new Exception( 'PayPal order creation failed. Missing approval link.' );
				}
			}

			// Save the PayPal order ID to the order.
			$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, $response_data['id'] );

			// Save the PayPal order status to the order.
			$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_STATUS, $response_data['status'] );

			// Remember the payment source: payment_source is not patchable.
			// If the payment source is changed, we need to create a new PayPal order.
			$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_PAYMENT_SOURCE, $payment_source );
			$order->save();

			return array(
				'id'           => $response_data['id'],
				'redirect_url' => $redirect_url,
			);
		} catch ( Exception $e ) {
			\WC_Gateway_Paypal::log( $e->getMessage() );
			if ( $paypal_debug_id ) {
				$order->add_order_note(
					sprintf(
						/* translators: %1$s: PayPal debug ID */
						__( 'PayPal order creation failed. PayPal debug ID: %1$s', 'woocommerce' ),
						$paypal_debug_id
					)
				);
			}
			return null;
		}
	}

	/**
	 * Get PayPal order details.
	 *
	 * @param string $paypal_order_id The ID of the PayPal order.
	 * @return array
	 * @throws Exception If the PayPal order details request fails.
	 * @throws Exception If the PayPal order details are not found.
	 */
	public function get_paypal_order_details( string $paypal_order_id ): array {
		$request_body = array(
			'test_mode' => $this->gateway->testmode,
		);
		$response     = $this->send_wpcom_proxy_request( 'GET', self::WPCOM_PROXY_ORDER_ENDPOINT . '/' . $paypal_order_id, $request_body );
		if ( is_wp_error( $response ) ) {
			throw new Exception( 'PayPal order details request failed: ' . esc_html( $response->get_error_message() ) );
		}

		$http_code     = wp_remote_retrieve_response_code( $response );
		$body          = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $body, true );

		if ( 200 !== $http_code ) {
			$debug_id = isset( $response_data['debug_id'] ) ? $response_data['debug_id'] : null;
			$message  = 'PayPal order details request failed. HTTP ' . (int) $http_code . ( $debug_id ? '. Debug ID: ' . $debug_id : '' );
			throw new Exception( esc_html( $message ) );
		}

		return $response_data;
	}

	/**
	 * Authorize or capture a PayPal payment using the Orders v2 API.
	 *
	 * This method authorizes or captures a PayPal payment and updates the order status.
	 *
	 * @param WC_Order|null $order Order object.
	 * @param string|null   $action_url The URL to authorize or capture the payment.
	 * @param string        $action The action to perform. Either 'authorize' or 'capture'.
	 * @return void
	 * @throws Exception If the PayPal payment authorization or capture fails.
	 */
	public function authorize_or_capture_payment( ?WC_Order $order, ?string $action_url, string $action = PayPalConstants::PAYMENT_ACTION_CAPTURE ): void {
		if ( ! $order ) {
			\WC_Gateway_Paypal::log( 'Order not found to authorize or capture payment.' );
			return;
		}

		$paypal_debug_id = null;
		$paypal_order_id = $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID );
		if ( ! $paypal_order_id ) {
			\WC_Gateway_Paypal::log( 'PayPal order ID not found. Cannot ' . $action . ' payment.' );
			return;
		}

		if ( ! $action_url || ! filter_var( $action_url, FILTER_VALIDATE_URL ) ) {
			\WC_Gateway_Paypal::log( 'Invalid or missing action URL. Cannot ' . $action . ' payment.' );
			return;
		}

		// Skip if the payment is already captured.
		if ( PayPalConstants::STATUS_COMPLETED === $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_STATUS, true ) ) {
			\WC_Gateway_Paypal::log( 'PayPal payment is already captured. Skipping capture. Order ID: ' . $order->get_id() );
			return;
		}

		try {
			if ( PayPalConstants::PAYMENT_ACTION_CAPTURE === $action ) {
				$endpoint     = self::WPCOM_PROXY_PAYMENT_CAPTURE_ENDPOINT;
				$request_body = array(
					'capture_url'     => $action_url,
					'paypal_order_id' => $paypal_order_id,
					'test_mode'       => $this->gateway->testmode,
				);
			} else {
				$endpoint     = self::WPCOM_PROXY_PAYMENT_AUTHORIZE_ENDPOINT;
				$request_body = array(
					'authorize_url'   => $action_url,
					'paypal_order_id' => $paypal_order_id,
					'test_mode'       => $this->gateway->testmode,
				);
			}

			$response = $this->send_wpcom_proxy_request( 'POST', $endpoint, $request_body );

			if ( is_wp_error( $response ) ) {
				throw new Exception( 'PayPal ' . $action . ' payment request failed. Response error: ' . $response->get_error_message() );
			}

			$http_code     = wp_remote_retrieve_response_code( $response );
			$body          = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $body, true );

			if ( 200 !== $http_code && 201 !== $http_code ) {
				$paypal_debug_id = isset( $response_data['debug_id'] ) ? $response_data['debug_id'] : null;
				throw new Exception( 'PayPal ' . $action . ' payment failed. Response status: ' . $http_code . '. Response body: ' . $body );
			}
		} catch ( Exception $e ) {
			\WC_Gateway_Paypal::log( $e->getMessage() );
			$note_message = sprintf(
				/* translators: %1$s: Action, %2$s: PayPal order ID */
				__( 'PayPal %1$s payment failed. PayPal Order ID: %2$s', 'woocommerce' ),
				$action,
				$paypal_order_id
			);

			// Add debug ID to the note if available.
			if ( $paypal_debug_id ) {
				$note_message .= sprintf(
					/* translators: %s: PayPal debug ID */
					__( '. PayPal debug ID: %s', 'woocommerce' ),
					$paypal_debug_id
				);
			}

			$order->add_order_note( $note_message );
			$order->update_status( OrderStatus::FAILED );
			$order->save();
		}
	}

	/**
	 * Capture a PayPal payment that has been authorized.
	 *
	 * @param WC_Order|null $order Order object.
	 * @return void
	 * @throws Exception If the PayPal payment capture fails.
	 */
	public function capture_authorized_payment( ?WC_Order $order ): void {
		if ( ! $order ) {
			\WC_Gateway_Paypal::log( 'Order not found to capture authorized payment.' );
			return;
		}

		$paypal_order_id = $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, true );
		// Skip if the PayPal Order ID is not found. This means the order was not created via the Orders v2 API.
		if ( ! $paypal_order_id ) {
			\WC_Gateway_Paypal::log( 'PayPal Order ID not found to capture authorized payment. Order ID: ' . $order->get_id() );
			return;
		}

		$capture_id = $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_CAPTURE_ID, true );
		// Skip if the payment is already captured.
		if ( $capture_id ) {
			\WC_Gateway_Paypal::log( 'PayPal payment is already captured. PayPal capture ID: ' . $capture_id . '. Order ID: ' . $order->get_id() );
			return;
		}

		$paypal_status = $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_STATUS, true );

		// Skip if the payment is already captured.
		if ( PayPalConstants::STATUS_CAPTURED === $paypal_status || PayPalConstants::STATUS_COMPLETED === $paypal_status ) {
			\WC_Gateway_Paypal::log( 'PayPal payment is already captured. Skipping capture. Order ID: ' . $order->get_id() );
			return;
		}

		// Skip if the payment requires payer action.
		if ( PayPalConstants::STATUS_PAYER_ACTION_REQUIRED === $paypal_status ) {
			\WC_Gateway_Paypal::log( 'PayPal payment requires payer action. Skipping capture. Order ID: ' . $order->get_id() );
			return;
		}

		// Skip if the payment is voided.
		if ( PayPalConstants::VOIDED === $paypal_status ) {
			\WC_Gateway_Paypal::log( 'PayPal payment voided. Skipping capture. Order ID: ' . $order->get_id() );
			return;
		}

		$authorization_id = $this->get_authorization_id_for_capture( $order );
		if ( ! $authorization_id ) {
			\WC_Gateway_Paypal::log( 'Authorization ID not found to capture authorized payment. Order ID: ' . $order->get_id() );
			return;
		}

		$paypal_debug_id = null;
		$http_code       = null;

		try {
			$request_body = array(
				'test_mode'        => $this->gateway->testmode,
				'authorization_id' => $authorization_id,
				'paypal_order_id'  => $paypal_order_id,
			);
			$response     = $this->send_wpcom_proxy_request( 'POST', self::WPCOM_PROXY_PAYMENT_CAPTURE_AUTH_ENDPOINT, $request_body );

			if ( is_wp_error( $response ) ) {
				throw new Exception( 'PayPal capture payment request failed. Response error: ' . $response->get_error_message() );
			}

			$http_code             = wp_remote_retrieve_response_code( $response );
			$body                  = wp_remote_retrieve_body( $response );
			$response_data         = json_decode( $body, true );
			$issue                 = isset( $response_data['details'][0]['issue'] ) ? $response_data['details'][0]['issue'] : '';
			$auth_already_captured = 422 === $http_code && PayPalConstants::PAYPAL_ISSUE_AUTHORIZATION_ALREADY_CAPTURED === $issue;

			if ( 200 !== $http_code && 201 !== $http_code && ! $auth_already_captured ) {
				$paypal_debug_id = isset( $response_data['debug_id'] ) ? $response_data['debug_id'] : null;
				throw new Exception( 'PayPal capture payment failed. Response status: ' . $http_code . '. Response body: ' . $body );
			}

			// Set custom status for successful capture response, or if the authorization was already captured.
			$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_STATUS, PayPalConstants::STATUS_CAPTURED );
			$order->save();
		} catch ( Exception $e ) {
			\WC_Gateway_Paypal::log( $e->getMessage() );

			$note_message = sprintf(
				__( 'PayPal capture authorized payment failed', 'woocommerce' ),
			);

			// Scenario 1: Capture auth API call returned 404 (authorization object does not exist).
			// If the authorization ID is not found (404 response), set the '_paypal_authorization_checked' flag.
			// This flag indicates that we've made an API call to capture PayPal payment and no authorization object was found with this authorization ID.
			// This prevents repeated API calls for orders that have no authorization data.
			if ( 404 === $http_code ) {
				$paypal_dashboard_url = $this->gateway->testmode
					? 'https://www.sandbox.paypal.com/unifiedtransactions'
					: 'https://www.paypal.com/unifiedtransactions';

				$note_message .= sprintf(
					/* translators: %1$s: Authorization ID, %2$s: open link tag, %3$s: close link tag */
					__( '. Authorization ID: %1$s not found. Please log into your %2$sPayPal account%3$s to capture the payment', 'woocommerce' ),
					esc_html( $authorization_id ),
					'<a href="' . esc_url( $paypal_dashboard_url ) . '" target="_blank">',
					'</a>'
				);
				$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_AUTHORIZATION_CHECKED, 'yes' );
			}

			if ( $paypal_debug_id ) {
				$note_message .= sprintf(
					/* translators: %s: PayPal debug ID */
					__( '. PayPal debug ID: %s', 'woocommerce' ),
					$paypal_debug_id
				);
			}

			$order->add_order_note( $note_message );
			$order->save();
		}
	}

	/**
	 * Get the authorization ID for the PayPal payment.
	 *
	 * @param WC_Order $order Order object.
	 * @return string|null
	 */
	private function get_authorization_id_for_capture( WC_Order $order ): ?string {
		$paypal_order_id  = $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, true );
		$authorization_id = $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_AUTHORIZATION_ID, true );
		$capture_id       = $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_CAPTURE_ID, true );

		// If the PayPal order ID is not found or the capture ID is already set, return null.
		if ( ! $paypal_order_id || ! empty( $capture_id ) ) {
			return null;
		}

		// If '_paypal_authorization_checked' is set to 'yes', it means we've already made an API call to PayPal
		// and confirmed that no authorization object exists. This flag is set in two scenarios:
		// 1. Capture auth API call returned 404 (authorization object does not exist with the authorization ID).
		// 2. Order details API call returned empty authorization array (authorization object does not exist for this PayPal order).
		// Return null to avoid repeated API calls for orders that have no authorization data.
		if ( 'yes' === $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_AUTHORIZATION_CHECKED, true ) ) {
			return null;
		}

		// If the authorization ID is not found, try to retrieve it from the PayPal order details.
		if ( empty( $authorization_id ) ) {
			\WC_Gateway_Paypal::log( 'Authorization ID not found, trying to retrieve from PayPal order details as a fallback for backwards compatibility. Order ID: ' . $order->get_id() );

			try {
				$order_data         = $this->get_paypal_order_details( $paypal_order_id );
				$authorization_data = $this->get_latest_transaction_data(
					$order_data['purchase_units'][0]['payments']['authorizations'] ?? array()
				);

				$capture_data = $this->get_latest_transaction_data(
					$order_data['purchase_units'][0]['payments']['captures'] ?? array()
				);

				// If the payment is already captured, store the capture ID and status, and return null as there is no authorization ID that needs to be captured.
				if ( $capture_data && isset( $capture_data['id'] ) ) {
					$capture_id = $capture_data['id'];
					$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_CAPTURE_ID, $capture_id );
					$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_STATUS, $capture_data['status'] ?? PayPalConstants::STATUS_CAPTURED );
					$order->save();
					\WC_Gateway_Paypal::log( 'Storing capture ID from Paypal. Order ID: ' . $order->get_id() . '; capture ID: ' . $capture_id );
					return null;
				}

				if ( $authorization_data && isset( $authorization_data['id'], $authorization_data['status'] ) ) {
					// If the payment is already captured, return null as there is no authorization ID that needs to be captured.
					if ( PayPalConstants::STATUS_CAPTURED === $authorization_data['status'] ) {
						$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_STATUS, PayPalConstants::STATUS_CAPTURED );
						$order->save();
						return null;
					}
					$authorization_id = $authorization_data['id'];
					$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_AUTHORIZATION_ID, $authorization_id );
					$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_STATUS, PayPalConstants::STATUS_AUTHORIZED );
					\WC_Gateway_Paypal::log( 'Storing authorization ID from Paypal. Order ID: ' . $order->get_id() . '; authorization ID: ' . $authorization_id );
					$order->save();
				} else {
					// Scenario 2: Order details API call returned empty authorization array (authorization object does not exist).
					// Store '_paypal_authorization_checked' flag to prevent repeated API calls.
					// This flag indicates that we've made an API call to get PayPal order details and confirmed no authorization object exists.
					\WC_Gateway_Paypal::log( 'Authorization ID not found in PayPal order details. Order ID: ' . $order->get_id() );
					$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_AUTHORIZATION_CHECKED, 'yes' );
					$order->save();
					return null;
				}
			} catch ( Exception $e ) {
				\WC_Gateway_Paypal::log( 'Error retrieving authorization ID from PayPal order details. Order ID: ' . $order->get_id() . '. Error: ' . $e->getMessage() );
				return null;
			}
		}

		return $authorization_id;
	}

	/**
	 * Get the latest item from the authorizations or captures array based on update_time.
	 *
	 * @param array $items Array of authorizations or captures.
	 * @return array|null The latest authorization or capture or null if array is empty or no valid update_time found.
	 */
	private function get_latest_transaction_data( array $items ): ?array {
		if ( empty( $items ) ) {
			return null;
		}

		$latest_item = null;
		$latest_time = null;

		foreach ( $items as $item ) {
			if ( empty( $item['update_time'] ) ) {
				continue;
			}

			if ( null === $latest_time || $item['update_time'] > $latest_time ) {
				$latest_time = $item['update_time'];
				$latest_item = $item;
			}
		}

		return $latest_item;
	}

	/**
	 * Get the approve link from the response data.
	 *
	 * @param int|string $http_code The HTTP code of the response.
	 * @param array      $response_data The response data.
	 * @return string|null
	 */
	private function get_approve_link( $http_code, array $response_data ): ?string {
		// See https://developer.paypal.com/docs/api/orders/v2/#orders_create.
		if ( isset( $response_data['status'] ) && PayPalConstants::STATUS_PAYER_ACTION_REQUIRED === $response_data['status'] ) {
			$rel = 'payer-action';
		} else {
			$rel = 'approve';
		}

		foreach ( $response_data['links'] as $link ) {
			if ( $rel === $link['rel'] && 'GET' === $link['method'] && filter_var( $link['href'], FILTER_VALIDATE_URL ) ) {
				return esc_url_raw( $link['href'] );
			}
		}

		return null;
	}

	/**
	 * Build the request parameters for the PayPal create-order request.
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $payment_source The payment source.
	 * @param array    $js_sdk_params Extra parameters for a PayPal JS SDK (Buttons) request.
	 * @return array
	 *
	 * @throws Exception If the order items cannot be built.
	 */
	private function get_paypal_create_order_request_params( WC_Order $order, string $payment_source, array $js_sdk_params ): array {
		$payee_email         = sanitize_email( (string) $this->gateway->get_option( 'email' ) );
		$shipping_preference = $this->get_paypal_shipping_preference( $order );

		/**
		 * Filter the supported currencies for PayPal.
		 *
		 * @since 2.0.0
		 *
		 * @param array $supported_currencies Array of supported currency codes.
		 * @return array
		 */
		$supported_currencies = apply_filters(
			'woocommerce_paypal_supported_currencies',
			PayPalConstants::SUPPORTED_CURRENCIES
		);
		if ( ! in_array( strtoupper( $order->get_currency() ), $supported_currencies, true ) ) {
			throw new Exception( 'Currency is not supported by PayPal. Order ID: ' . esc_html( (string) $order->get_id() ) );
		}

		$purchase_unit_amount = $this->get_paypal_order_purchase_unit_amount( $order );
		if ( $purchase_unit_amount['value'] <= 0 ) {
			// If we cannot build purchase unit amount (e.g. negative or zero order total),
			// we should not proceed with the create-order request.
			throw new Exception( 'Cannot build PayPal order purchase unit amount. Order total is not valid. Order ID: ' . esc_html( (string) $order->get_id() ) . ', Total: ' . esc_html( (string) $purchase_unit_amount['value'] ) );
		}

		$order_items = $this->get_paypal_order_items( $order );

		$src_locale = get_locale();
		// If the locale is longer than PayPal's string limit (10).
		if ( strlen( $src_locale ) > PayPalConstants::PAYPAL_LOCALE_MAX_LENGTH ) {
			// Keep only the main language and region parts.
			$locale_parts = explode( '_', $src_locale );
			if ( count( $locale_parts ) > 2 ) {
				$src_locale = $locale_parts[0] . '_' . $locale_parts[1];
			}
		}

		$params = array(
			'intent'         => $this->get_paypal_order_intent(),
			'payment_source' => array(
				$payment_source => array(
					'experience_context' => array(
						'user_action'           => PayPalConstants::USER_ACTION_PAY_NOW,
						'shipping_preference'   => $shipping_preference,
						// Customer redirected here on approval.
						'return_url'            => $this->normalize_url_for_paypal( add_query_arg( 'utm_nooverride', '1', $this->gateway->get_return_url( $order ) ) ),
						// Customer redirected here on cancellation.
						'cancel_url'            => $this->normalize_url_for_paypal( $order->get_cancel_order_url_raw() ),
						// Convert WordPress locale format (e.g., 'en_US') to PayPal's expected format (e.g., 'en-US').
						'locale'                => str_replace( '_', '-', $src_locale ),
						'app_switch_preference' => array(
							'launch_paypal_app' => true,
						),
					),
				),
			),
			'purchase_units' => array(
				array(
					'custom_id'  => $this->get_paypal_order_custom_id( $order ),
					'amount'     => $purchase_unit_amount,
					'invoice_id' => $this->limit_length( $this->gateway->get_option( 'invoice_prefix' ) . $order->get_order_number(), PayPalConstants::PAYPAL_INVOICE_ID_MAX_LENGTH ),
					'items'      => $order_items,
					'payee'      => array(
						'email_address' => $payee_email,
					),
				),
			),
		);

		if ( ! in_array(
			$shipping_preference,
			array(
				PayPalConstants::SHIPPING_NO_SHIPPING,
				PayPalConstants::SHIPPING_SET_PROVIDED_ADDRESS,
			),
			true
		) ) {
			$shipping_callback_token = $this->generate_shipping_callback_token( $order );
			$callback_url            = add_query_arg(
				'token',
				$shipping_callback_token,
				rest_url( 'wc/v3/paypal-standard/update-shipping' )
			);

			$params['payment_source'][ $payment_source ]['experience_context']['order_update_callback_config'] = array(
				'callback_events' => array( 'SHIPPING_ADDRESS', 'SHIPPING_OPTIONS' ),
				'callback_url'    => $this->normalize_url_for_paypal( $callback_url ),
			);
		}

		// If the request is from PayPal JS SDK (Buttons), we need a cancel URL that is compatible with App Switch.
		if ( ! empty( $js_sdk_params['is_js_sdk_flow'] ) && ! empty( $js_sdk_params['app_switch_request_origin'] ) ) {
			// App Switch may open a new tab, so we cannot rely on client-side data.
			// We need to pass the order ID manually.
			// See https://developer.paypal.com/docs/checkout/standard/customize/app-switch/#resume-flow.

			$request_origin = $js_sdk_params['app_switch_request_origin'];

			// Check if $request_origin is a valid URL, and matches the current site.
			$origin_parts       = wp_parse_url( $request_origin );
			$site_parts         = wp_parse_url( get_site_url() );
			$is_valid_url       = filter_var( $request_origin, FILTER_VALIDATE_URL );
			$is_expected_scheme = isset( $origin_parts['scheme'], $site_parts['scheme'] ) && strcasecmp( $origin_parts['scheme'], $site_parts['scheme'] ) === 0;
			$is_expected_host   = isset( $origin_parts['host'], $site_parts['host'] ) && strcasecmp( $origin_parts['host'], $site_parts['host'] ) === 0;
			if ( $is_valid_url && $is_expected_scheme && $is_expected_host ) {
				$cancel_url = add_query_arg(
					array(
						'order_id' => $order->get_id(),
					),
					$request_origin
				);
				$params['payment_source'][ $payment_source ]['experience_context']['cancel_url'] = $this->normalize_url_for_paypal( $cancel_url );
			}
		}

		$shipping = $this->get_paypal_order_shipping( $order );
		if ( $shipping ) {
			$params['purchase_units'][0]['shipping'] = $shipping;
		}

		return $params;
	}

	/**
	 * Get the amount data  for the PayPal order purchase unit field.
	 *
	 * @param WC_Order|null $order Order object.
	 * @return array
	 */
	public function get_paypal_order_purchase_unit_amount( ?WC_Order $order ): array {
		if ( ! $order ) {
			return array();
		}

		$currency = $order->get_currency();

		return array(
			'currency_code' => $currency,
			'value'         => wc_format_decimal( $order->get_total(), wc_get_price_decimals() ),
			'breakdown'     => array(
				'item_total' => array(
					'currency_code' => $currency,
					'value'         => wc_format_decimal( $this->get_paypal_order_items_subtotal( $order ), wc_get_price_decimals() ),
				),
				'shipping'   => array(
					'currency_code' => $currency,
					'value'         => wc_format_decimal( $order->get_shipping_total(), wc_get_price_decimals() ),
				),
				'tax_total'  => array(
					'currency_code' => $currency,
					'value'         => wc_format_decimal( $order->get_total_tax(), wc_get_price_decimals() ),
				),
				'discount'   => array(
					'currency_code' => $currency,
					'value'         => wc_format_decimal( $order->get_discount_total(), wc_get_price_decimals() ),
				),
			),
		);
	}

	/**
	 * Build the custom ID for the PayPal order. The custom ID will be used by the proxy for webhook forwarding,
	 * and by later steps to identify the order.
	 *
	 * @param WC_Order $order Order object.
	 * @return string
	 * @throws Exception If the custom ID is too long.
	 */
	private function get_paypal_order_custom_id( WC_Order $order ): string {
		$custom_id = wp_json_encode(
			array(
				'order_id'  => $order->get_id(),
				'order_key' => $order->get_order_key(),
				// Endpoint for the proxy to forward webhooks to.
				'site_url'  => home_url(),
				'site_id'   => class_exists( '\Jetpack_Options' ) ? \Jetpack_Options::get_option( 'id' ) : null,
				'v'         => defined( 'WC_VERSION' ) ? WC_VERSION : WC()->version,
			)
		);

		if ( false === $custom_id ) {
			throw new Exception( 'Failed to encode custom ID.' );
		}

		if ( strlen( $custom_id ) > 255 ) {
			throw new Exception( 'PayPal order custom ID is too long. Max length is 255 chars.' );
		}

		return $custom_id ? $custom_id : '';
	}

	/**
	 * Get the order items for the PayPal create-order request.
	 * Returns an empty array if any of the items (amount, quantity) are invalid.
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	private function get_paypal_order_items( WC_Order $order ): array {
		$items = array();

		foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item ) {
			$item_amount = $this->get_paypal_order_item_amount( $order, $item );
			if ( $item_amount < 0 ) {
				// PayPal does not accept negative item amounts in the items breakdown, so we return an empty list.
				return array();
			}

			$quantity = $item->get_quantity();
			// PayPal does not accept zero or fractional quantities.
			if ( ! is_numeric( $quantity ) || $quantity <= 0 || floor( $quantity ) != $quantity ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
				return array();
			}

			$items[] = array(
				'name'        => $this->limit_length( $item->get_name(), PayPalConstants::PAYPAL_ORDER_ITEM_NAME_MAX_LENGTH ),
				'quantity'    => $item->get_quantity(),
				'unit_amount' => array(
					'currency_code' => $order->get_currency(),
					// Use the subtotal before discounts.
					'value'         => wc_format_decimal( $item_amount, wc_get_price_decimals() ),
				),
			);
		}

		return $items;
	}

	/**
	 * Get the subtotal for all items, before discounts.
	 *
	 * @param WC_Order $order Order object.
	 * @return float
	 */
	private function get_paypal_order_items_subtotal( WC_Order $order ): float {
		$total = 0;
		foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item ) {
			$total += wc_add_number_precision( $this->get_paypal_order_item_amount( $order, $item ) * $item->get_quantity(), false );
		}

		return wc_remove_number_precision( $total );
	}

	/**
	 * Get the amount for a specific order item.
	 *
	 * @param WC_Order       $order Order object.
	 * @param \WC_Order_Item $item Order item.
	 * @return float
	 */
	private function get_paypal_order_item_amount( WC_Order $order, \WC_Order_Item $item ): float {
		if ( 'fee' === $item->get_type() && $item instanceof \WC_Order_Item_Fee ) {
			return (float) $item->get_amount();
		}
		return (float) $order->get_item_subtotal( $item, $include_tax = false, $rounding_enabled = false );
	}

	/**
	 * Get the value for the intent field in the create-order request.
	 *
	 * @return string
	 */
	private function get_paypal_order_intent(): string {
		$payment_action = $this->gateway->get_option( 'paymentaction' );
		if ( 'authorization' === $payment_action ) {
			return PayPalConstants::INTENT_AUTHORIZE;
		}

		return PayPalConstants::INTENT_CAPTURE;
	}

	/**
	 * Get the shipping preference for the PayPal create-order request.
	 *
	 * @param WC_Order $order Order object.
	 * @return string
	 */
	private function get_paypal_shipping_preference( WC_Order $order ): string {
		if ( ! $order->needs_shipping() ) {
			return PayPalConstants::SHIPPING_NO_SHIPPING;
		}

		$address_override = $this->gateway->get_option( 'address_override' ) === 'yes';
		return $address_override ? PayPalConstants::SHIPPING_SET_PROVIDED_ADDRESS : PayPalConstants::SHIPPING_GET_FROM_FILE;
	}

	/**
	 * Get the shipping information for the PayPal create-order request.
	 *
	 * @param WC_Order $order Order object.
	 * @return array|null Returns null if the shipping is not required,
	 *  or the address is not set, or is incomplete.
	 */
	private function get_paypal_order_shipping( WC_Order $order ): ?array {
		if ( ! $order->needs_shipping() ) {
			return null;
		}

		$address_type = 'yes' === $this->gateway->get_option( 'send_shipping' ) ? 'shipping' : 'billing';

		$full_name      = trim( $order->{"get_formatted_{$address_type}_full_name"}() );
		$address_line_1 = trim( $order->{"get_{$address_type}_address_1"}() );
		$address_line_2 = trim( $order->{"get_{$address_type}_address_2"}() );
		$state          = trim( $order->{"get_{$address_type}_state"}() );
		$city           = trim( $order->{"get_{$address_type}_city"}() );
		$postcode       = trim( $order->{"get_{$address_type}_postcode"}() );
		$country        = trim( $order->{"get_{$address_type}_country"}() );

		// If we do not have the complete address,
		// e.g. PayPal Buttons on product pages, we should not set the 'shipping' param
		// for the create-order request, otherwise it will fail.
		// Shipping information will be updated by the shipping callback handlers.

		// Country is a required field.
		if ( empty( $country ) ) {
			return null;
		}

		// Make sure the country code is in the correct format.
		$raw_country = $country;
		$country     = $this->normalize_paypal_order_shipping_country_code( $raw_country );
		if ( ! $country ) {
			\WC_Gateway_Paypal::log( sprintf( 'Could not identify a correct country code. Raw value: %s', $raw_country ), 'error' );
			return null;
		}

		// Validate required fields based on country-specific address requirements.
		// phpcs:ignore Generic.Commenting.Todo.TaskFound
		// TODO: The container call can be removed once we migrate this class to the `src` folder.
		$address_requirements = wc_get_container()->get( PayPalAddressRequirements::class )::instance();
		if ( empty( $city ) && $address_requirements->country_requires_city( $country ) ) {
			\WC_Gateway_Paypal::log( sprintf( 'City is required for country: %s', $country ), 'error' );
			return null;
		}

		if ( empty( $postcode ) && $address_requirements->country_requires_postal_code( $country ) ) {
			\WC_Gateway_Paypal::log( sprintf( 'Postal code is required for country: %s', $country ), 'error' );
			return null;
		}

		return array(
			'name'    => array(
				'full_name' => $full_name,
			),
			'address' => array(
				'address_line_1' => $this->limit_length( $address_line_1, PayPalConstants::PAYPAL_ADDRESS_LINE_MAX_LENGTH ),
				'address_line_2' => $this->limit_length( $address_line_2, PayPalConstants::PAYPAL_ADDRESS_LINE_MAX_LENGTH ),
				'admin_area_1'   => $this->limit_length( $state, PayPalConstants::PAYPAL_STATE_MAX_LENGTH ),
				'admin_area_2'   => $this->limit_length( $city, PayPalConstants::PAYPAL_CITY_MAX_LENGTH ),
				'postal_code'    => $this->limit_length( $postcode, PayPalConstants::PAYPAL_POSTAL_CODE_MAX_LENGTH ),
				'country_code'   => strtoupper( $country ),
			),
		);
	}

	/**
	 * Generate and store a shipping callback token for the order.
	 * The token is stored in the database cache and can be validated later.
	 *
	 * @param WC_Order $order The order object.
	 * @return string The generated token.
	 */
	private function generate_shipping_callback_token( WC_Order $order ): string {
		$token = bin2hex( random_bytes( 32 ) );

		// Store the token in order meta for validation.
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN, $token );
		$order->save();

		return $token;
	}

	/**
	 * Normalize PayPal order shipping country code.
	 *
	 * @param string $country_code Country code to normalize.
	 * @return string|null
	 */
	private function normalize_paypal_order_shipping_country_code( string $country_code ): ?string {
		// Normalize to uppercase.
		$code = strtoupper( trim( (string) $country_code ) );

		// Check if it's a valid alpha-2 code.
		if ( strlen( $code ) === PayPalConstants::PAYPAL_COUNTRY_CODE_LENGTH ) {
			if ( WC()->countries->country_exists( $code ) ) {
				return $code;
			}

			\WC_Gateway_Paypal::log( sprintf( 'Invalid country code: %s', $code ) );
			return null;
		}

		// Log when we get an unexpected country code length.
		\WC_Gateway_Paypal::log( sprintf( 'Unexpected country code length (%d) for country: %s', strlen( $code ), $code ) );

		// Truncate to the expected maximum length (3).
		$max_country_code_length = PayPalConstants::PAYPAL_COUNTRY_CODE_LENGTH + 1;
		if ( strlen( $code ) > $max_country_code_length ) {
			$code = substr( $code, 0, $max_country_code_length );
		}

		// Check if it's a valid alpha-3 code.
		$alpha2 = WC()->countries->get_country_from_alpha_3_code( $code );
		if ( null === $alpha2 ) {
			\WC_Gateway_Paypal::log( sprintf( 'Invalid alpha-3 country code: %s', $code ) );
		}

		return $alpha2;
	}

	/**
	 * Normalize a URL for PayPal. PayPal requires absolute URLs with protocol.
	 *
	 * @param string $url The URL to check.
	 * @return string Normalized URL.
	 */
	private function normalize_url_for_paypal( string $url ): string {
		// Replace encoded ampersand with actual ampersand.
		// In some cases, the URL may contain encoded ampersand but PayPal expects the actual ampersand.
		// PayPal request fails if the URL contains encoded ampersand.
		$url = str_replace( '&#038;', '&', $url );

		// If the URL is already the home URL, return it.
		if ( strpos( $url, home_url() ) === 0 ) {
			return esc_url_raw( $url );
		}

		// Return the URL if it is already absolute (contains ://).
		if ( strpos( $url, '://' ) !== false ) {
			return esc_url_raw( $url );
		}

		$home_url = untrailingslashit( home_url() );

		// If the URL is relative (starts with /), prepend the home URL.
		if ( strpos( $url, '/' ) === 0 ) {
			return esc_url_raw( $home_url . $url );
		}

		// Prepend home URL with a slash.
		return esc_url_raw( $home_url . '/' . $url );
	}

	/**
	 * Fetch the PayPal client-id from the Transact platform.
	 *
	 * @return string|null The PayPal client-id, or null if the request fails.
	 * @throws Exception If the request fails.
	 */
	public function fetch_paypal_client_id(): ?string {
		try {
			$request_body = array(
				'test_mode' => $this->gateway->testmode,
			);

			$response = $this->send_wpcom_proxy_request( 'GET', self::WPCOM_PROXY_CLIENT_ID_ENDPOINT, $request_body );

			if ( is_wp_error( $response ) ) {
				throw new Exception( 'Failed to fetch the client ID. Response error: ' . $response->get_error_message() );
			}

			$http_code     = wp_remote_retrieve_response_code( $response );
			$body          = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $body, true );

			if ( 200 !== $http_code ) {
				throw new Exception( 'Failed to fetch the client ID. Response status: ' . $http_code . '. Response body: ' . $body );
			}

			return $response_data['client_id'] ?? null;
		} catch ( Exception $e ) {
			\WC_Gateway_Paypal::log( $e->getMessage() );
			return null;
		}
	}

	/**
	 * Send a request to the API proxy.
	 *
	 * @param string $method The HTTP method to use.
	 * @param string $endpoint The endpoint to request.
	 * @param array  $request_body The request body.
	 *
	 * @return array|\WP_Error The API response body, or WP_Error if the request fails.
	 * @throws Exception If the site ID is not found.
	 */
	private function send_wpcom_proxy_request( string $method, string $endpoint, array $request_body ) {
		$site_id = \Jetpack_Options::get_option( 'id' );
		if ( ! $site_id ) {
			\WC_Gateway_Paypal::log( sprintf( 'Site ID not found. Cannot send request to %s.', $endpoint ) );
			throw new Exception( 'Site ID not found. Cannot send proxy request.' );
		}

		if ( 'GET' === $method ) {
			$endpoint .= '?' . http_build_query( $request_body );
		}

		$response = Jetpack_Connection_Client::wpcom_json_api_request_as_blog(
			sprintf( '/sites/%d/%s/%s', $site_id, self::WPCOM_PROXY_REST_BASE, $endpoint ),
			self::WPCOM_PROXY_ENDPOINT_API_VERSION,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
					'User-Agent'   => 'TransactGateway/woocommerce/' . WC()->version,
				),
				'method'  => $method,
				'timeout' => PayPalConstants::WPCOM_PROXY_REQUEST_TIMEOUT,
			),
			'GET' === $method ? null : wp_json_encode( $request_body ),
			'wpcom'
		);

		return $response;
	}

	/**
	 * Limit length of an arg.
	 *
	 * @param  string  $text Text to limit.
	 * @param  integer $limit Limit size in characters.
	 * @return string
	 */
	private function limit_length( string $text, int $limit = 127 ): string {
		$str_limit = $limit - 3;
		if ( function_exists( 'mb_strimwidth' ) ) {
			if ( mb_strlen( $text ) > $limit ) {
				$text = mb_strimwidth( $text, 0, $str_limit ) . '...';
			}
		} elseif ( strlen( $text ) > $limit ) {
			$text = substr( $text, 0, $str_limit ) . '...';
		}
		return $text;
	}
}
