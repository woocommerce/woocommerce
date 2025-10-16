<?php
/**
 * Class Request file.
 *
 * @package WooCommerce\Gateways
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Gateways\PayPal;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\Jetpack\Connection\Client as Jetpack_Connection_Client;
use Automattic\WooCommerce\Gateways\PayPal\Constants as PayPalConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates requests to send to PayPal Orders v2 API.
 */
class Request {

	/**
	 * Pointer to gateway making the request.
	 *
	 * @var \WC_Gateway_Paypal
	 */
	protected $gateway;

	/**
	 * The API version for the proxy endpoint.
	 *
	 * @var int
	 */
	private const WPCOM_PROXY_ENDPOINT_API_VERSION = 2;

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
	 * @param \WC_Order $order Order object.
	 * @param string    $payment_source The payment source.
	 * @param array     $js_sdk_params Extra parameters for a PayPal JS SDK (Buttons) request.
	 * @return array|null
	 * @throws \Exception If the PayPal order creation fails.
	 */
	public function create_paypal_order(
		\WC_Order $order,
		string $payment_source = PayPalConstants::PAYMENT_SOURCE_PAYPAL,
		array $js_sdk_params = array()
	) {
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
				throw new \Exception( 'PayPal order creation failed. Response error: ' . $response->get_error_message() );
			}

			$http_code     = wp_remote_retrieve_response_code( $response );
			$body          = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $body, true );

			if ( ! in_array( $http_code, array( 200, 201 ), true ) ) {
				$paypal_debug_id = isset( $response_data['debug_id'] ) ? $response_data['debug_id'] : null;
				throw new \Exception( 'PayPal order creation failed. Response status: ' . $http_code . '. Response body: ' . $body );
			}

			$redirect_url = null;
			if ( empty( $js_sdk_params['is_js_sdk_flow'] ) ) {
				// We only need an approve link for the classic, redirect flow.
				$redirect_url = $this->get_approve_link( $http_code, $response_data );
				if ( empty( $redirect_url ) ) {
					throw new \Exception( 'PayPal order creation failed. Missing approval link.' );
				}
			}

			// Save the PayPal order ID to the order.
			$order->update_meta_data( '_paypal_order_id', $response_data['id'] );

			// Remember the payment source: payment_source is not patchable.
			// If the payment source is changed, we need to create a new PayPal order.
			$order->update_meta_data( '_paypal_payment_source', $payment_source );
			$order->save();

			return array(
				'id'           => $response_data['id'],
				'redirect_url' => $redirect_url,
			);
		} catch ( \Exception $e ) {
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
	 * @throws \Exception If the PayPal order details request fails.
	 * @throws \Exception If the PayPal order details are not found.
	 */
	public function get_paypal_order_details( string $paypal_order_id ) {
		$request_body = array(
			'test_mode' => $this->gateway->testmode,
		);
		$response     = $this->send_wpcom_proxy_request( 'GET', self::WPCOM_PROXY_ORDER_ENDPOINT . '/' . $paypal_order_id, $request_body );
		if ( is_wp_error( $response ) ) {
			throw new \Exception( 'PayPal order details request failed: ' . esc_html( $response->get_error_message() ) );
		}

		$http_code     = wp_remote_retrieve_response_code( $response );
		$body          = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $body, true );

		if ( 200 !== $http_code ) {
			$debug_id = isset( $response_data['debug_id'] ) ? $response_data['debug_id'] : null;
			$message  = 'PayPal order details request failed. HTTP ' . (int) $http_code . ( $debug_id ? '. Debug ID: ' . $debug_id : '' );
			throw new \Exception( esc_html( $message ) );
		}

		return $response_data;
	}

	/**
	 * Authorize or capture a PayPal payment using the Orders v2 API.
	 *
	 * This method authorizes or captures a PayPal payment and updates the order status.
	 *
	 * @param \WC_Order $order Order object.
	 * @param string    $action_url The URL to authorize or capture the payment.
	 * @param string    $action The action to perform. Either 'authorize' or 'capture'.
	 * @return void
	 * @throws \Exception If the PayPal payment authorization or capture fails.
	 */
	public function authorize_or_capture_payment( \WC_Order $order, string $action_url, string $action = PayPalConstants::PAYMENT_ACTION_CAPTURE ) {
		$paypal_debug_id = null;
		$paypal_order_id = $order->get_meta( '_paypal_order_id' );
		if ( ! $paypal_order_id ) {
			\WC_Gateway_Paypal::log( 'PayPal order ID not found. Cannot ' . $action . ' payment.' );
			return;
		}

		if ( ! $action_url || ! filter_var( $action_url, FILTER_VALIDATE_URL ) ) {
			\WC_Gateway_Paypal::log( 'Invalid or missing action URL. Cannot ' . $action . ' payment.' );
			return;
		}

		// Skip if the payment is already captured.
		if ( PayPalConstants::STATUS_COMPLETED === $order->get_meta( '_paypal_status', true ) ) {
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
				throw new \Exception( 'PayPal ' . $action . ' payment request failed. Response error: ' . $response->get_error_message() );
			}

			$http_code     = wp_remote_retrieve_response_code( $response );
			$body          = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $body, true );

			if ( 200 !== $http_code && 201 !== $http_code ) {
				$paypal_debug_id = isset( $response_data['debug_id'] ) ? $response_data['debug_id'] : null;
				throw new \Exception( 'PayPal ' . $action . ' payment failed. Response status: ' . $http_code . '. Response body: ' . $body );
			}
		} catch ( \Exception $e ) {
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
	 * @param \WC_Order $order Order object.
	 * @return void
	 * @throws \Exception If the PayPal payment capture fails.
	 */
	public function capture_authorized_payment( \WC_Order $order ) {
		if ( ! $order || ! $order->get_transaction_id() ) {
			\WC_Gateway_Paypal::log( 'PayPal authorization ID not found. Cannot capture payment.' );
			return;
		}

		// Skip if the payment is already captured.
		$paypal_status = $order->get_meta( '_paypal_status', true );
		if ( PayPalConstants::STATUS_CAPTURED === $paypal_status || PayPalConstants::STATUS_COMPLETED === $paypal_status ) {
			\WC_Gateway_Paypal::log( 'PayPal payment is already captured. Skipping capture. Order ID: ' . $order->get_id() );
			return;
		}

		$paypal_debug_id = null;

		try {
			$request_body = array(
				'test_mode'        => $this->gateway->testmode,
				'authorization_id' => $order->get_transaction_id(),
			);
			$response     = $this->send_wpcom_proxy_request( 'POST', self::WPCOM_PROXY_PAYMENT_CAPTURE_AUTH_ENDPOINT, $request_body );

			if ( is_wp_error( $response ) ) {
				throw new \Exception( 'PayPal capture payment request failed. Response error: ' . $response->get_error_message() );
			}

			$http_code     = wp_remote_retrieve_response_code( $response );
			$body          = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $body, true );

			if ( 200 !== $http_code && 201 !== $http_code ) {
				$paypal_debug_id = isset( $response_data['debug_id'] ) ? $response_data['debug_id'] : null;
				throw new \Exception( 'PayPal capture payment failed. Response status: ' . $http_code . '. Response body: ' . $body );
			}

			// Set custom status for successful capture response.
			$order->update_meta_data( '_paypal_status', PayPalConstants::STATUS_CAPTURED );
			$order->save();
		} catch ( \Exception $e ) {
			\WC_Gateway_Paypal::log( $e->getMessage() );
			$note_message = __( 'PayPal capture authorized payment failed.', 'woocommerce' );
			if ( $paypal_debug_id ) {
				$note_message .= sprintf(
					/* translators: %s: PayPal debug ID */
					__( '. PayPal debug ID: %s', 'woocommerce' ),
					$paypal_debug_id
				);
			}
			$order->add_order_note( $note_message );
		}
	}

	/**
	 * Get the approve link from the response data.
	 *
	 * @param int   $http_code The HTTP code of the response.
	 * @param array $response_data The response data.
	 * @return string|null
	 */
	private function get_approve_link( int $http_code, array $response_data ) {
		// See https://developer.paypal.com/docs/api/orders/v2/#orders_create.
		if ( isset( $response_data['status'] ) && 'PAYER_ACTION_REQUIRED' === $response_data['status'] ) {
			$rel = 'payer-action';
		} else {
			$rel = 'approve';
		}

		if ( ! isset( $response_data['links'] ) || ! is_array( $response_data['links'] ) ) {
			return null;
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
	 * @param \WC_Order $order Order object.
	 * @param string    $payment_source The payment source.
	 * @param array     $js_sdk_params Extra parameters for a PayPal JS SDK (Buttons) request.
	 * @return array
	 */
	private function get_paypal_create_order_request_params( \WC_Order $order, string $payment_source, array $js_sdk_params ) {
		$payee_email = sanitize_email( (string) $this->gateway->get_option( 'email' ) );

		$params = array(
			'intent'         => $this->get_paypal_order_intent(),
			'payment_source' => array(
				$payment_source => array(
					'experience_context' => array(
						'user_action'                  => PayPalConstants::USER_ACTION_PAY_NOW,
						'shipping_preference'          => $this->get_paypal_shipping_preference( $order ),
						// Customer redirected here on approval.
						'return_url'                   => esc_url_raw( add_query_arg( 'utm_nooverride', '1', $this->gateway->get_return_url( $order ) ) ),
						// Customer redirected here on cancellation.
						'cancel_url'                   => esc_url_raw( $order->get_cancel_order_url_raw() ),
						// Convert WordPress locale format (e.g., 'en_US') to PayPal's expected format (e.g., 'en-US').
						'locale'                       => str_replace( '_', '-', get_locale() ),
						'order_update_callback_config' => array(
							'callback_events' => array( 'SHIPPING_ADDRESS', 'SHIPPING_OPTIONS' ),
							'callback_url'    => esc_url_raw( rest_url( 'wc/v3/paypal-standard/update-shipping' ) ),
						),
						'app_switch_preference'        => array(
							'launch_paypal_app' => true,
						),
					),
				),
			),
			'purchase_units' => array(
				array(
					'custom_id'  => $this->get_paypal_order_custom_id( $order ),
					'amount'     => $this->get_paypal_order_purchase_unit_amount( $order ),
					'invoice_id' => $this->limit_length( $this->gateway->get_option( 'invoice_prefix' ) . $order->get_order_number(), PayPalConstants::PAYPAL_INVOICE_ID_MAX_LENGTH ),
					'items'      => $this->get_paypal_order_items( $order ),
					'payee'      => array(
						'email_address' => $payee_email,
					),
				),
			),
		);

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
				$params['payment_source'][ $payment_source ]['experience_context']['cancel_url'] = esc_url_raw( $cancel_url );
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
	 * @param \WC_Order $order Order object.
	 * @return array
	 */
	public function get_paypal_order_purchase_unit_amount( \WC_Order $order ) {
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
	 * @param \WC_Order $order Order object.
	 * @return string
	 * @throws \Exception If the custom ID is too long.
	 */
	private function get_paypal_order_custom_id( \WC_Order $order ) {
		$custom_id = wp_json_encode(
			array(
				'order_id'  => $order->get_id(),
				'order_key' => $order->get_order_key(),
				// Endpoint for the proxy to forward webhooks to.
				'site_url'  => get_site_url(),
				'site_id'   => class_exists( 'Jetpack_Options' ) ? \Jetpack_Options::get_option( 'id' ) : null,
			)
		);

		if ( strlen( $custom_id ) > 255 ) {
			throw new \Exception( 'PayPal order custom ID is too long. Max length is 255 chars.' );
		}

		return $custom_id;
	}

	/**
	 * Get the order items for the PayPal create-order request.
	 *
	 * @param \WC_Order $order Order object.
	 * @return array
	 */
	private function get_paypal_order_items( \WC_Order $order ) {
		$items = array();

		foreach ( $order->get_items() as $item ) {
			$items[] = array(
				'name'        => $this->limit_length( $item->get_name(), PayPalConstants::PAYPAL_ORDER_ITEM_NAME_MAX_LENGTH ),
				'quantity'    => $item->get_quantity(),
				'unit_amount' => array(
					'currency_code' => $order->get_currency(),
					// Use the subtotal before discounts.
					'value'         => wc_format_decimal(
						$order->get_item_subtotal( $item, $include_tax = false, $rounding_enabled = false ),
						wc_get_price_decimals()
					),
				),
			);
		}

		return $items;
	}

	/**
	 * Get the subtotal for all items, before discounts.
	 *
	 * @param \WC_Order $order Order object.
	 * @return float
	 */
	private function get_paypal_order_items_subtotal( \WC_Order $order ) {
		$total = 0;
		foreach ( $order->get_items() as $item ) {
			$total += (float) $item->get_subtotal();
		}

		return $total;
	}

	/**
	 * Get the value for the intent field in the create-order request.
	 *
	 * @return string
	 */
	private function get_paypal_order_intent() {
		$payment_action = $this->gateway->get_option( 'paymentaction' );
		if ( 'authorization' === $payment_action ) {
			return PayPalConstants::INTENT_AUTHORIZE;
		}

		return PayPalConstants::INTENT_CAPTURE;
	}

	/**
	 * Get the shipping preference for the PayPal create-order request.
	 *
	 * @param \WC_Order $order Order object.
	 * @return string
	 */
	private function get_paypal_shipping_preference( \WC_Order $order ) {
		if ( ! $order->needs_shipping() ) {
			return PayPalConstants::SHIPPING_NO_SHIPPING;
		}

		$address_override = $this->gateway->get_option( 'address_override' ) === 'yes';
		return $address_override ? PayPalConstants::SHIPPING_SET_PROVIDED_ADDRESS : PayPalConstants::SHIPPING_GET_FROM_FILE;
	}

	/**
	 * Get the shipping information for the PayPal create-order request.
	 *
	 * @param \WC_Order $order Order object.
	 * @return array|null Returns null if the shipping is not required,
	 *  or the address is not set, or is incomplete.
	 */
	private function get_paypal_order_shipping( \WC_Order $order ) {
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

		// Postal code is typically required, but not always. The create-order request
		// will fail if it is missing for a country that requires it.
		// As a simple heuristic, if the postal code is not set, but name and address_line_1 are,
		// we will assume that postal code is not required.
		if ( empty( $postcode ) && ( empty( $full_name ) || empty( $address_line_1 ) ) ) {
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
				'country_code'   => $country,
			),
		);
	}

	/**
	 * Fetch the PayPal client-id from the Transact platform.
	 *
	 * @return string|null The PayPal client-id, or null if the request fails.
	 * @throws \Exception If the request fails.
	 */
	public function fetch_paypal_client_id() {
		try {
			$request_body = array(
				'test_mode' => $this->gateway->testmode,
			);

			$response = $this->send_wpcom_proxy_request( 'GET', self::WPCOM_PROXY_CLIENT_ID_ENDPOINT, $request_body );

			if ( is_wp_error( $response ) ) {
				throw new \Exception( 'Failed to fetch the client ID. Response error: ' . $response->get_error_message() );
			}

			$http_code     = wp_remote_retrieve_response_code( $response );
			$body          = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $body, true );

			if ( 200 !== $http_code ) {
				throw new \Exception( 'Failed to fetch the client ID. Response status: ' . $http_code . '. Response body: ' . $body );
			}

			return $response_data['client_id'] ?? null;
		} catch ( \Exception $e ) {
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
	 * @return array|null The API response body, or null if the request fails.
	 * @throws \Exception If the site ID is not found.
	 */
	private function send_wpcom_proxy_request( string $method, string $endpoint, array $request_body ) {
		$site_id = \Jetpack_Options::get_option( 'id' );
		if ( ! $site_id ) {
			\WC_Gateway_Paypal::log( sprintf( 'Site ID not found. Cannot send request to %s.', $endpoint ) );
			throw new \Exception( 'Site ID not found. Cannot send proxy request.' );
		}

		if ( 'GET' === $method ) {
			$endpoint .= '?' . http_build_query( $request_body );
		}

		$response = Jetpack_Connection_Client::wpcom_json_api_request_as_blog(
			sprintf( '/sites/%d/%s/%s', $site_id, self::WPCOM_PROXY_REST_BASE, $endpoint ),
			self::WPCOM_PROXY_ENDPOINT_API_VERSION,
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

	/**
	 * Limit length of an arg.
	 *
	 * @param  string  $arg Argument to limit.
	 * @param  integer $limit Limit size in characters.
	 * @return string
	 */
	protected function limit_length( string $arg, int $limit = 127 ) {
		$str_limit = $limit - 3;
		if ( function_exists( 'mb_strimwidth' ) ) {
			if ( mb_strlen( $arg ) > $limit ) {
				$arg = mb_strimwidth( $arg, 0, $str_limit ) . '...';
			}
		} elseif ( strlen( $arg ) > $limit ) {
				$arg = substr( $arg, 0, $str_limit ) . '...';
		}
		return $arg;
	}
}
