<?php
/**
 * Class WC_Gateway_Paypal_Request file.
 *
 * @package WooCommerce\Gateways
 */

declare(strict_types=1);

use Automattic\WooCommerce\Gateways\PayPal\AddressRequirements as PayPalAddressRequirements;
use Automattic\WooCommerce\Utilities\NumberUtil;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\Jetpack\Connection\Client as Jetpack_Connection_Client;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates requests to send to PayPal.
 */
class WC_Gateway_Paypal_Request {
	/**
	 * Stores line items to send to PayPal.
	 *
	 * @var array
	 */
	protected $line_items = array();

	/**
	 * Pointer to gateway making the request.
	 *
	 * @var WC_Gateway_Paypal
	 */
	protected $gateway;

	/**
	 * Endpoint for requests from PayPal.
	 *
	 * @var string
	 */
	protected $notify_url;

	/**
	 * Endpoint for requests to PayPal.
	 *
	 * @var string
	 */
	protected $endpoint;

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
	 * @param WC_Gateway_Paypal $gateway Paypal gateway object.
	 */
	public function __construct( $gateway ) {
		$this->gateway    = $gateway;
		$this->notify_url = WC()->api_request_url( 'WC_Gateway_Paypal' );
	}

	/**
	 * Get the PayPal request URL for an order.
	 *
	 * @param  WC_Order $order Order object.
	 * @param  bool     $sandbox Whether to use sandbox mode or not.
	 * @return string
	 */
	public function get_request_url( $order, $sandbox = false ) {
		$this->endpoint    = $sandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr?test_ipn=1&' : 'https://www.paypal.com/cgi-bin/webscr?';
		$paypal_args       = $this->get_paypal_args( $order );
		$paypal_args['bn'] = 'WooThemes_Cart'; // Append WooCommerce PayPal Partner Attribution ID. This should not be overridden for this gateway.

		// Mask (remove) PII from the logs.
		$mask = array(
			'first_name'    => '***',
			'last_name'     => '***',
			'address1'      => '***',
			'address2'      => '***',
			'city'          => '***',
			'state'         => '***',
			'zip'           => '***',
			'country'       => '***',
			'email'         => '***@***',
			'night_phone_a' => '***',
			'night_phone_b' => '***',
			'night_phone_c' => '***',
		);

		WC_Gateway_Paypal::log( 'PayPal Request Args for order ' . $order->get_order_number() . ': ' . wc_print_r( array_merge( $paypal_args, array_intersect_key( $mask, $paypal_args ) ), true ) );

		return $this->endpoint . http_build_query( $paypal_args, '', '&' );
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
		$order,
		$payment_source = WC_Gateway_Paypal_Constants::PAYMENT_SOURCE_PAYPAL,
		$js_sdk_params = array()
	) {
		$paypal_debug_id = null;

		// While PayPal JS SDK can return 'paylater' as the payment source in the createOrder callback,
		// Orders v2 API does not accept it. We will use 'paypal' instead.
		// Accepted payment_source values for Orders v2:
		// https://developer.paypal.com/docs/api/orders/v2/#orders_create!ct=application/json&path=payment_source&t=request.
		if ( WC_Gateway_Paypal_Constants::PAYMENT_SOURCE_PAYLATER === $payment_source ) {
			$payment_source = WC_Gateway_Paypal_Constants::PAYMENT_SOURCE_PAYPAL;
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

			$http_code     = wp_remote_retrieve_response_code( $response );
			$body          = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $body, true );

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
			$order->update_meta_data( '_paypal_order_id', $response_data['id'] );

			// Remember the payment source: payment_source is not patchable.
			// If the payment source is changed, we need to create a new PayPal order.
			$order->update_meta_data( '_paypal_payment_source', $payment_source );
			$order->save();

			return array(
				'id'           => $response_data['id'],
				'redirect_url' => $redirect_url,
			);
		} catch ( Exception $e ) {
			WC_Gateway_Paypal::log( $e->getMessage() );
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
	public function get_paypal_order_details( $paypal_order_id ) {
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
	 * @param WC_Order $order Order object.
	 * @param string   $action_url The URL to authorize or capture the payment.
	 * @param string   $action The action to perform. Either 'authorize' or 'capture'.
	 * @return void
	 * @throws Exception If the PayPal payment authorization or capture fails.
	 */
	public function authorize_or_capture_payment( $order, $action_url, $action = WC_Gateway_Paypal_Constants::PAYMENT_ACTION_CAPTURE ) {
		$paypal_debug_id = null;
		$paypal_order_id = $order->get_meta( '_paypal_order_id' );
		if ( ! $paypal_order_id ) {
			WC_Gateway_Paypal::log( 'PayPal order ID not found. Cannot ' . $action . ' payment.' );
			return;
		}

		if ( ! $action_url || ! filter_var( $action_url, FILTER_VALIDATE_URL ) ) {
			WC_Gateway_Paypal::log( 'Invalid or missing action URL. Cannot ' . $action . ' payment.' );
			return;
		}

		// Skip if the payment is already captured.
		if ( WC_Gateway_Paypal_Constants::STATUS_COMPLETED === $order->get_meta( '_paypal_status', true ) ) {
			WC_Gateway_Paypal::log( 'PayPal payment is already captured. Skipping capture. Order ID: ' . $order->get_id() );
			return;
		}

		try {
			if ( WC_Gateway_Paypal_Constants::PAYMENT_ACTION_CAPTURE === $action ) {
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
			WC_Gateway_Paypal::log( $e->getMessage() );
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
	 * @param WC_Order $order Order object.
	 * @return void
	 * @throws Exception If the PayPal payment capture fails.
	 */
	public function capture_authorized_payment( $order ) {
		if ( ! $order || ! $order->get_transaction_id() ) {
			WC_Gateway_Paypal::log( 'PayPal authorization ID not found. Cannot capture payment.' );
			return;
		}

		// Skip if the payment is already captured.
		$paypal_status = $order->get_meta( '_paypal_status', true );
		if ( WC_Gateway_Paypal_Constants::STATUS_CAPTURED === $paypal_status || WC_Gateway_Paypal_Constants::STATUS_COMPLETED === $paypal_status ) {
			WC_Gateway_Paypal::log( 'PayPal payment is already captured. Skipping capture. Order ID: ' . $order->get_id() );
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
				throw new Exception( 'PayPal capture payment request failed. Response error: ' . $response->get_error_message() );
			}

			$http_code     = wp_remote_retrieve_response_code( $response );
			$body          = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $body, true );

			if ( 200 !== $http_code && 201 !== $http_code ) {
				$paypal_debug_id = isset( $response_data['debug_id'] ) ? $response_data['debug_id'] : null;
				throw new Exception( 'PayPal capture payment failed. Response status: ' . $http_code . '. Response body: ' . $body );
			}

			// Set custom status for successful capture response.
			$order->update_meta_data( '_paypal_status', WC_Gateway_Paypal_Constants::STATUS_CAPTURED );
			$order->save();
		} catch ( Exception $e ) {
			WC_Gateway_Paypal::log( $e->getMessage() );
			$note_message = sprintf(
				__( 'PayPal capture authorized payment failed', 'woocommerce' ),
			);
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
	private function get_approve_link( $http_code, $response_data ) {
		// See https://developer.paypal.com/docs/api/orders/v2/#orders_create.
		if ( isset( $response_data['status'] ) && 'PAYER_ACTION_REQUIRED' === $response_data['status'] ) {
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
	private function get_paypal_create_order_request_params( $order, $payment_source, $js_sdk_params ) {
		$payee_email         = sanitize_email( (string) $this->gateway->get_option( 'email' ) );
		$shipping_preference = $this->get_paypal_shipping_preference( $order );

		$src_locale = get_locale();
		// If the locale is longer than PayPal's string limit (10).
		if ( strlen( $src_locale ) > WC_Gateway_Paypal_Constants::PAYPAL_LOCALE_MAX_LENGTH ) {
			// Keep only the main language and region parts.
			$locale_parts = explode( '_', $src_locale );
			if ( count( $locale_parts ) > 2 ) {
				$src_locale = $locale_parts[0] . '_' . $locale_parts[1];
			}
		}

		$order_items = $this->get_paypal_order_items( $order );
		if ( empty( $order_items ) ) {
			// If we cannot build order items (e.g. negative item amounts),
			// we should not proceed with the create-order request.
			throw new Exception( 'Cannot build PayPal order items for order ID: ' . esc_html( $order->get_id() ) );
		}

		$params = array(
			'intent'         => $this->get_paypal_order_intent(),
			'payment_source' => array(
				$payment_source => array(
					'experience_context' => array(
						'user_action'           => WC_Gateway_Paypal_Constants::USER_ACTION_PAY_NOW,
						'shipping_preference'   => $shipping_preference,
						// Customer redirected here on approval.
						'return_url'            => esc_url_raw( add_query_arg( 'utm_nooverride', '1', $this->gateway->get_return_url( $order ) ) ),
						// Customer redirected here on cancellation.
						'cancel_url'            => esc_url_raw( $order->get_cancel_order_url_raw() ),
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
					'amount'     => $this->get_paypal_order_purchase_unit_amount( $order ),
					'invoice_id' => $this->limit_length( $this->gateway->get_option( 'invoice_prefix' ) . $order->get_order_number(), WC_Gateway_Paypal_Constants::PAYPAL_INVOICE_ID_MAX_LENGTH ),
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
				WC_Gateway_Paypal_Constants::SHIPPING_NO_SHIPPING,
				WC_Gateway_Paypal_Constants::SHIPPING_SET_PROVIDED_ADDRESS,
			),
			true
		) ) {
			$params['payment_source'][ $payment_source ]['experience_context']['order_update_callback_config'] = array(
				'callback_events' => array( 'SHIPPING_ADDRESS', 'SHIPPING_OPTIONS' ),
				'callback_url'    => esc_url_raw( rest_url( 'wc/v3/paypal-standard/update-shipping' ) ),
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
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	public function get_paypal_order_purchase_unit_amount( $order ) {
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
	private function get_paypal_order_custom_id( $order ) {
		$custom_id = wp_json_encode(
			array(
				'order_id'  => $order->get_id(),
				'order_key' => $order->get_order_key(),
				// Endpoint for the proxy to forward webhooks to.
				'site_url'  => get_site_url(),
				'site_id'   => class_exists( 'Jetpack_Options' ) ? Jetpack_Options::get_option( 'id' ) : null,
			)
		);

		if ( strlen( $custom_id ) > 255 ) {
			throw new Exception( 'PayPal order custom ID is too long. Max length is 255 chars.' );
		}

		return $custom_id;
	}

	/**
	 * Get the order items for the PayPal create-order request.
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	private function get_paypal_order_items( $order ) {
		$items = array();

		foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item ) {
			$item_amount = $this->get_paypal_order_item_amount( $order, $item );
			if ( 'line_item' === $item->get_type() && $item_amount < 0 ) {
				// PayPal does not accept negative item amounts (for line items).
				WC_Gateway_Paypal::log( sprintf( 'Order item with negative amount for PayPal order items. Order ID: %d, Item ID: %d, Amount: %f', $order->get_id(), $item->get_id(), $item_amount ), 'error' );
				return array();
			}

			$items[] = array(
				'name'        => $this->limit_length( $item->get_name(), WC_Gateway_Paypal_Constants::PAYPAL_ORDER_ITEM_NAME_MAX_LENGTH ),
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
	private function get_paypal_order_items_subtotal( $order ) {
		$total = 0;
		foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item ) {
			$total += wc_add_number_precision( $this->get_paypal_order_item_amount( $order, $item ) * $item->get_quantity(), false );
		}

		return wc_remove_number_precision( $total );
	}

	/**
	 * Get the amount for a specific order item.
	 *
	 * @param WC_Order      $order Order object.
	 * @param WC_Order_Item $item Order item.
	 * @return float
	 */
	private function get_paypal_order_item_amount( $order, $item ) {
		return (float) (
			'fee' === $item->get_type()
				? $item->get_amount()
				: $order->get_item_subtotal( $item, $include_tax = false, $rounding_enabled = false )
		);
	}

	/**
	 * Get the value for the intent field in the create-order request.
	 *
	 * @return string
	 */
	private function get_paypal_order_intent() {
		$payment_action = $this->gateway->get_option( 'paymentaction' );
		if ( 'authorization' === $payment_action ) {
			return WC_Gateway_Paypal_Constants::INTENT_AUTHORIZE;
		}

		return WC_Gateway_Paypal_Constants::INTENT_CAPTURE;
	}

	/**
	 * Get the shipping preference for the PayPal create-order request.
	 *
	 * @param WC_Order $order Order object.
	 * @return string
	 */
	private function get_paypal_shipping_preference( $order ) {
		if ( ! $order->needs_shipping() ) {
			return WC_Gateway_Paypal_Constants::SHIPPING_NO_SHIPPING;
		}

		$address_override = $this->gateway->get_option( 'address_override' ) === 'yes';
		return $address_override ? WC_Gateway_Paypal_Constants::SHIPPING_SET_PROVIDED_ADDRESS : WC_Gateway_Paypal_Constants::SHIPPING_GET_FROM_FILE;
	}

	/**
	 * Get the shipping information for the PayPal create-order request.
	 *
	 * @param WC_Order $order Order object.
	 * @return array|null Returns null if the shipping is not required,
	 *  or the address is not set, or is incomplete.
	 */
	private function get_paypal_order_shipping( $order ) {
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
			WC_Gateway_Paypal::log( sprintf( 'Could not identify a correct country code. Raw value: %s', $raw_country ), 'error' );
			return null;
		}

		// Validate required fields based on country-specific address requirements.
		// phpcs:ignore Generic.Commenting.Todo.TaskFound
		// TODO: The container call can be removed once we migrate this class to the `src` folder.
		$address_requirements = wc_get_container()->get( PayPalAddressRequirements::class )::instance();
		if ( empty( $city ) && $address_requirements->country_requires_city( $country ) ) {
			WC_Gateway_Paypal::log( sprintf( 'City is required for country: %s', $country ), 'error' );
			return null;
		}

		if ( empty( $postcode ) && $address_requirements->country_requires_postal_code( $country ) ) {
			WC_Gateway_Paypal::log( sprintf( 'Postal code is required for country: %s', $country ), 'error' );
			return null;
		}

		return array(
			'name'    => array(
				'full_name' => $full_name,
			),
			'address' => array(
				'address_line_1' => $this->limit_length( $address_line_1, WC_Gateway_Paypal_Constants::PAYPAL_ADDRESS_LINE_MAX_LENGTH ),
				'address_line_2' => $this->limit_length( $address_line_2, WC_Gateway_Paypal_Constants::PAYPAL_ADDRESS_LINE_MAX_LENGTH ),
				'admin_area_1'   => $this->limit_length( $state, WC_Gateway_Paypal_Constants::PAYPAL_STATE_MAX_LENGTH ),
				'admin_area_2'   => $this->limit_length( $city, WC_Gateway_Paypal_Constants::PAYPAL_CITY_MAX_LENGTH ),
				'postal_code'    => $this->limit_length( $postcode, WC_Gateway_Paypal_Constants::PAYPAL_POSTAL_CODE_MAX_LENGTH ),
				'country_code'   => strtoupper( $country ),
			),
		);
	}

	/**
	 * Normalize PayPal order shipping country code.
	 *
	 * @param string $country_code Country code to normalize.
	 * @return string|null
	 */
	private function normalize_paypal_order_shipping_country_code( $country_code ) {
		// Normalize to uppercase.
		$code = strtoupper( trim( (string) $country_code ) );

		// Check if it's a valid alpha-2 code.
		if ( strlen( $code ) === WC_Gateway_Paypal_Constants::PAYPAL_COUNTRY_CODE_LENGTH ) {
			if ( WC()->countries->country_exists( $code ) ) {
				return $code;
			}

			WC_Gateway_Paypal::log( sprintf( 'Invalid country code: %s', $code ) );
			return null;
		}

		// Log when we get an unexpected country code length.
		WC_Gateway_Paypal::log( sprintf( 'Unexpected country code length (%d) for country: %s', strlen( $code ), $code ) );

		// Truncate to the expected maximum length (3).
		$max_country_code_length = WC_Gateway_Paypal_Constants::PAYPAL_COUNTRY_CODE_LENGTH + 1;
		if ( strlen( $code ) > $max_country_code_length ) {
			$code = substr( $code, 0, $max_country_code_length );
		}

		// Check if it's a valid alpha-3 code.
		$alpha2 = wc()->countries->get_country_from_alpha_3_code( $code );
		if ( null === $alpha2 ) {
			WC_Gateway_Paypal::log( sprintf( 'Invalid alpha-3 country code: %s', $code ) );
		}

		return $alpha2;
	}

	/**
	 * Fetch the PayPal client-id from the Transact platform.
	 *
	 * @return string|null The PayPal client-id, or null if the request fails.
	 * @throws Exception If the request fails.
	 */
	public function fetch_paypal_client_id() {
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
			WC_Gateway_Paypal::log( $e->getMessage() );
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
	 * @throws Exception If the site ID is not found.
	 */
	private function send_wpcom_proxy_request( $method, $endpoint, $request_body ) {
		$site_id = \Jetpack_Options::get_option( 'id' );
		if ( ! $site_id ) {
			WC_Gateway_Paypal::log( sprintf( 'Site ID not found. Cannot send request to %s.', $endpoint ) );
			throw new Exception( 'Site ID not found. Cannot send proxy request.' );
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
				'timeout' => WC_Gateway_Paypal_Constants::WPCOM_PROXY_REQUEST_TIMEOUT,
			),
			'GET' === $method ? null : wp_json_encode( $request_body ),
			'wpcom'
		);

		return $response;
	}

	/**
	 * Limit length of an arg.
	 *
	 * @param  string  $string Argument to limit.
	 * @param  integer $limit Limit size in characters.
	 * @return string
	 */
	protected function limit_length( $string, $limit = 127 ) {
		$str_limit = $limit - 3;
		if ( function_exists( 'mb_strimwidth' ) ) {
			if ( mb_strlen( $string ) > $limit ) {
				$string = mb_strimwidth( $string, 0, $str_limit ) . '...';
			}
		} elseif ( strlen( $string ) > $limit ) {
				$string = substr( $string, 0, $str_limit ) . '...';
		}
		return $string;
	}

	/**
	 * Get transaction args for paypal request, except for line item args.
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	protected function get_transaction_args( $order ) {
		return array_merge(
			array(
				'cmd'           => '_cart',
				'business'      => $this->gateway->get_option( 'email' ),
				'no_note'       => 1,
				'currency_code' => get_woocommerce_currency(),
				'charset'       => 'utf-8',
				'rm'            => is_ssl() ? 2 : 1,
				'upload'        => 1,
				'return'        => esc_url_raw( add_query_arg( 'utm_nooverride', '1', $this->gateway->get_return_url( $order ) ) ),
				'cancel_return' => esc_url_raw( $order->get_cancel_order_url_raw() ),
				'image_url'     => esc_url_raw( $this->gateway->get_option( 'image_url' ) ),
				'paymentaction' => $this->gateway->get_option( 'paymentaction' ),
				'invoice'       => $this->limit_length( $this->gateway->get_option( 'invoice_prefix' ) . $order->get_order_number(), WC_Gateway_Paypal_Constants::PAYPAL_INVOICE_ID_MAX_LENGTH ),
				'custom'        => wp_json_encode(
					array(
						'order_id'  => $order->get_id(),
						'order_key' => $order->get_order_key(),
					)
				),
				'notify_url'    => $this->limit_length( $this->notify_url, 255 ),
				'first_name'    => $this->limit_length( $order->get_billing_first_name(), 32 ),
				'last_name'     => $this->limit_length( $order->get_billing_last_name(), 64 ),
				'address1'      => $this->limit_length( $order->get_billing_address_1(), 100 ),
				'address2'      => $this->limit_length( $order->get_billing_address_2(), 100 ),
				'city'          => $this->limit_length( $order->get_billing_city(), 40 ),
				'state'         => $this->get_paypal_state( $order->get_billing_country(), $order->get_billing_state() ),
				'zip'           => $this->limit_length( wc_format_postcode( $order->get_billing_postcode(), $order->get_billing_country() ), 32 ),
				'country'       => $this->limit_length( $order->get_billing_country(), 2 ),
				'email'         => $this->limit_length( $order->get_billing_email() ),
			),
			$this->get_phone_number_args( $order ),
			$this->get_shipping_args( $order )
		);
	}

	/**
	 * If the default request with line items is too long, generate a new one with only one line item.
	 *
	 * If URL is longer than 2,083 chars, ignore line items and send cart to Paypal as a single item.
	 * One item's name can only be 127 characters long, so the URL should not be longer than limit.
	 * URL character limit via:
	 * https://support.microsoft.com/en-us/help/208427/maximum-url-length-is-2-083-characters-in-internet-explorer.
	 *
	 * @param WC_Order $order Order to be sent to Paypal.
	 * @param array    $paypal_args Arguments sent to Paypal in the request.
	 * @return array
	 */
	protected function fix_request_length( $order, $paypal_args ) {
		$max_paypal_length = 2083;
		$query_candidate   = http_build_query( $paypal_args, '', '&' );

		if ( strlen( $this->endpoint . $query_candidate ) <= $max_paypal_length ) {
			return $paypal_args;
		}

		return apply_filters(
			'woocommerce_paypal_args',
			array_merge(
				$this->get_transaction_args( $order ),
				$this->get_line_item_args( $order, true )
			),
			$order
		);
	}

	/**
	 * Get PayPal Args for passing to PP.
	 *
	 * @param  WC_Order $order Order object.
	 * @return array
	 */
	protected function get_paypal_args( $order ) {
		WC_Gateway_Paypal::log( 'Generating payment form for order ' . $order->get_order_number() . '. Notify URL: ' . $this->notify_url );

		$force_one_line_item = apply_filters( 'woocommerce_paypal_force_one_line_item', false, $order );

		if ( ( wc_tax_enabled() && wc_prices_include_tax() ) || ! $this->line_items_valid( $order ) ) {
			$force_one_line_item = true;
		}

		$paypal_args = apply_filters(
			'woocommerce_paypal_args',
			array_merge(
				$this->get_transaction_args( $order ),
				$this->get_line_item_args( $order, $force_one_line_item )
			),
			$order
		);

		return $this->fix_request_length( $order, $paypal_args );
	}

	/**
	 * Get phone number args for paypal request.
	 *
	 * @param  WC_Order $order Order object.
	 * @return array
	 */
	protected function get_phone_number_args( $order ) {
		$phone_number = wc_sanitize_phone_number( $order->get_billing_phone() );

		if ( in_array( $order->get_billing_country(), array( 'US', 'CA' ), true ) ) {
			$phone_number = ltrim( $phone_number, '+1' );
			$phone_args   = array(
				'night_phone_a' => substr( $phone_number, 0, 3 ),
				'night_phone_b' => substr( $phone_number, 3, 3 ),
				'night_phone_c' => substr( $phone_number, 6, 4 ),
			);
		} else {
			$calling_code = WC()->countries->get_country_calling_code( $order->get_billing_country() );
			$calling_code = is_array( $calling_code ) ? $calling_code[0] : $calling_code;

			if ( $calling_code ) {
				$phone_number = str_replace( $calling_code, '', preg_replace( '/^0/', '', $order->get_billing_phone() ) );
			}

			$phone_args = array(
				'night_phone_a' => $calling_code,
				'night_phone_b' => $phone_number,
			);
		}
		return $phone_args;
	}

	/**
	 * Get shipping args for paypal request.
	 *
	 * @param  WC_Order $order Order object.
	 * @return array
	 */
	protected function get_shipping_args( $order ) {
		$shipping_args = array();
		if ( $order->needs_shipping_address() ) {
			$shipping_args['address_override'] = $this->gateway->get_option( 'address_override' ) === 'yes' ? 1 : 0;
			$shipping_args['no_shipping']      = 0;
			if ( 'yes' === $this->gateway->get_option( 'send_shipping' ) ) {
				// If we are sending shipping, send shipping address instead of billing.
				$shipping_args['first_name'] = $this->limit_length( $order->get_shipping_first_name(), 32 );
				$shipping_args['last_name']  = $this->limit_length( $order->get_shipping_last_name(), 64 );
				$shipping_args['address1']   = $this->limit_length( $order->get_shipping_address_1(), 100 );
				$shipping_args['address2']   = $this->limit_length( $order->get_shipping_address_2(), 100 );
				$shipping_args['city']       = $this->limit_length( $order->get_shipping_city(), 40 );
				$shipping_args['state']      = $this->get_paypal_state( $order->get_shipping_country(), $order->get_shipping_state() );
				$shipping_args['country']    = $this->limit_length( $order->get_shipping_country(), 2 );
				$shipping_args['zip']        = $this->limit_length( wc_format_postcode( $order->get_shipping_postcode(), $order->get_shipping_country() ), 32 );
			}
		} else {
			$shipping_args['no_shipping'] = 1;
		}
		return $shipping_args;
	}

	/**
	 * Get shipping cost line item args for paypal request.
	 *
	 * @param  WC_Order $order Order object.
	 * @param  bool     $force_one_line_item Whether one line item was forced by validation or URL length.
	 * @return array
	 */
	protected function get_shipping_cost_line_item( $order, $force_one_line_item ) {
		$line_item_args = array();
		$shipping_total = $order->get_shipping_total();
		if ( $force_one_line_item ) {
			$shipping_total += $order->get_shipping_tax();
		}

		// Add shipping costs. Paypal ignores anything over 5 digits (999.99 is the max).
		// We also check that shipping is not the **only** cost as PayPal won't allow payment
		// if the items have no cost.
		if ( $order->get_shipping_total() > 0 && $order->get_shipping_total() < 999.99 && $this->number_format( $order->get_shipping_total() + $order->get_shipping_tax(), $order ) !== $this->number_format( $order->get_total(), $order ) ) {
			$line_item_args['shipping_1'] = $this->number_format( $shipping_total, $order );
		} elseif ( $order->get_shipping_total() > 0 ) {
			/* translators: %s: Order shipping method */
			$this->add_line_item( sprintf( __( 'Shipping via %s', 'woocommerce' ), $order->get_shipping_method() ), 1, $this->number_format( $shipping_total, $order ) );
		}

		return $line_item_args;
	}

	/**
	 * Get line item args for paypal request as a single line item.
	 *
	 * @param  WC_Order $order Order object.
	 * @return array
	 */
	protected function get_line_item_args_single_item( $order ) {
		$this->delete_line_items();

		$all_items_name = $this->get_order_item_names( $order );
		$this->add_line_item( $all_items_name ? $all_items_name : __( 'Order', 'woocommerce' ), 1, $this->number_format( $order->get_total() - $this->round( $order->get_shipping_total() + $order->get_shipping_tax(), $order ), $order ), $order->get_order_number() );
		$line_item_args = $this->get_shipping_cost_line_item( $order, true );

		return array_merge( $line_item_args, $this->get_line_items() );
	}

	/**
	 * Get line item args for paypal request.
	 *
	 * @param  WC_Order $order Order object.
	 * @param  bool     $force_one_line_item Create only one item for this order.
	 * @return array
	 */
	protected function get_line_item_args( $order, $force_one_line_item = false ) {
		$line_item_args = array();

		if ( $force_one_line_item ) {
			/**
			 * Send order as a single item.
			 *
			 * For shipping, we longer use shipping_1 because paypal ignores it if *any* shipping rules are within paypal, and paypal ignores anything over 5 digits (999.99 is the max).
			 */
			$line_item_args = $this->get_line_item_args_single_item( $order );
		} else {
			/**
			 * Passing a line item per product if supported.
			 */
			$this->prepare_line_items( $order );
			$line_item_args['tax_cart'] = $this->number_format( $order->get_total_tax(), $order );

			if ( $order->get_total_discount() > 0 ) {
				$line_item_args['discount_amount_cart'] = $this->number_format( $this->round( $order->get_total_discount(), $order ), $order );
			}

			$line_item_args = array_merge( $line_item_args, $this->get_shipping_cost_line_item( $order, false ) );
			$line_item_args = array_merge( $line_item_args, $this->get_line_items() );

		}

		return $line_item_args;
	}

	/**
	 * Get order item names as a string.
	 *
	 * @param  WC_Order $order Order object.
	 * @return string
	 */
	protected function get_order_item_names( $order ) {
		$item_names = array();

		foreach ( $order->get_items() as $item ) {
			$item_name = $item->get_name();
			$item_meta = wp_strip_all_tags(
				wc_display_item_meta(
					$item,
					array(
						'before'    => '',
						'separator' => ', ',
						'after'     => '',
						'echo'      => false,
						'autop'     => false,
					)
				)
			);

			if ( $item_meta ) {
				$item_name .= ' (' . $item_meta . ')';
			}

			$item_names[] = $item_name . ' x ' . $item->get_quantity();
		}

		return apply_filters( 'woocommerce_paypal_get_order_item_names', implode( ', ', $item_names ), $order );
	}

	/**
	 * Get order item names as a string.
	 *
	 * @param  WC_Order      $order Order object.
	 * @param  WC_Order_Item $item Order item object.
	 * @return string
	 */
	protected function get_order_item_name( $order, $item ) {
		$item_name = $item->get_name();
		$item_meta = wp_strip_all_tags(
			wc_display_item_meta(
				$item,
				array(
					'before'    => '',
					'separator' => ', ',
					'after'     => '',
					'echo'      => false,
					'autop'     => false,
				)
			)
		);

		if ( $item_meta ) {
			$item_name .= ' (' . $item_meta . ')';
		}

		return apply_filters( 'woocommerce_paypal_get_order_item_name', $item_name, $order, $item );
	}

	/**
	 * Return all line items.
	 */
	protected function get_line_items() {
		return $this->line_items;
	}

	/**
	 * Remove all line items.
	 */
	protected function delete_line_items() {
		$this->line_items = array();
	}

	/**
	 * Check if the order has valid line items to use for PayPal request.
	 *
	 * The line items are invalid in case of mismatch in totals or if any amount < 0.
	 *
	 * @param WC_Order $order Order to be examined.
	 * @return bool
	 */
	protected function line_items_valid( $order ) {
		$negative_item_amount = false;
		$calculated_total     = 0;

		// Products.
		foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item ) {
			if ( 'fee' === $item['type'] ) {
				$item_line_total   = $this->number_format( $item['line_total'], $order );
				$calculated_total += $item_line_total;
			} else {
				$item_line_total   = $this->number_format( $order->get_item_subtotal( $item, false ), $order );
				$calculated_total += $item_line_total * $item->get_quantity();
			}

			if ( $item_line_total < 0 ) {
				$negative_item_amount = true;
			}
		}
		$mismatched_totals = $this->number_format( $calculated_total + $order->get_total_tax() + $this->round( $order->get_shipping_total(), $order ) - $this->round( $order->get_total_discount(), $order ), $order ) !== $this->number_format( $order->get_total(), $order );
		return ! $negative_item_amount && ! $mismatched_totals;
	}

	/**
	 * Get line items to send to paypal.
	 *
	 * @param  WC_Order $order Order object.
	 */
	protected function prepare_line_items( $order ) {
		$this->delete_line_items();

		// Products.
		foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item ) {
			if ( 'fee' === $item['type'] ) {
				$item_line_total = $this->number_format( $item['line_total'], $order );
				$this->add_line_item( $item->get_name(), 1, $item_line_total );
			} else {
				$product         = $item->get_product();
				$sku             = $product ? $product->get_sku() : '';
				$item_line_total = $this->number_format( $order->get_item_subtotal( $item, false ), $order );
				$this->add_line_item( $this->get_order_item_name( $order, $item ), $item->get_quantity(), $item_line_total, $sku );
			}
		}
	}

	/**
	 * Add PayPal Line Item.
	 *
	 * @param  string $item_name Item name.
	 * @param  int    $quantity Item quantity.
	 * @param  float  $amount Amount.
	 * @param  string $item_number Item number.
	 */
	protected function add_line_item( $item_name, $quantity = 1, $amount = 0.0, $item_number = '' ) {
		$index = ( count( $this->line_items ) / 4 ) + 1;

		$item = apply_filters(
			'woocommerce_paypal_line_item',
			array(
				'item_name'   => html_entity_decode( wc_trim_string( $item_name ? wp_strip_all_tags( $item_name ) : __( 'Item', 'woocommerce' ), 127 ), ENT_NOQUOTES, 'UTF-8' ),
				'quantity'    => (int) $quantity,
				'amount'      => wc_float_to_string( (float) $amount ),
				'item_number' => $item_number,
			),
			$item_name,
			$quantity,
			$amount,
			$item_number
		);

		$this->line_items[ 'item_name_' . $index ]   = $this->limit_length( $item['item_name'], 127 );
		$this->line_items[ 'quantity_' . $index ]    = $item['quantity'];
		$this->line_items[ 'amount_' . $index ]      = $item['amount'];
		$this->line_items[ 'item_number_' . $index ] = $this->limit_length( $item['item_number'], 127 );
	}

	/**
	 * Get the state to send to paypal.
	 *
	 * @param  string $cc Country two letter code.
	 * @param  string $state State code.
	 * @return string
	 */
	protected function get_paypal_state( $cc, $state ) {
		if ( 'US' === $cc ) {
			return $state;
		}

		$states = WC()->countries->get_states( $cc );

		if ( isset( $states[ $state ] ) ) {
			return $states[ $state ];
		}

		return $state;
	}

	/**
	 * Check if currency has decimals.
	 *
	 * @param  string $currency Currency to check.
	 * @return bool
	 */
	protected function currency_has_decimals( $currency ) {
		if ( in_array( $currency, array( 'HUF', 'JPY', 'TWD' ), true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Round prices.
	 *
	 * @param  double   $price Price to round.
	 * @param  WC_Order $order Order object.
	 * @return double
	 */
	protected function round( $price, $order ) {
		$precision = 2;

		if ( ! $this->currency_has_decimals( $order->get_currency() ) ) {
			$precision = 0;
		}

		return NumberUtil::round( $price, $precision );
	}

	/**
	 * Format prices.
	 *
	 * @param  float|int $price Price to format.
	 * @param  WC_Order  $order Order object.
	 * @return string
	 */
	protected function number_format( $price, $order ) {
		$decimals = 2;

		if ( ! $this->currency_has_decimals( $order->get_currency() ) ) {
			$decimals = 0;
		}

		return number_format( (float) $price, $decimals, '.', '' );
	}
}
