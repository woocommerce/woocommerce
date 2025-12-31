<?php
/**
 * Class WC_Gateway_Paypal_Request file.
 *
 * @package WooCommerce\Gateways
 */

declare(strict_types=1);

use Automattic\WooCommerce\Gateways\PayPal\AddressRequirements as PayPalAddressRequirements;
use Automattic\WooCommerce\Gateways\PayPal\Constants as PayPalConstants;
use Automattic\WooCommerce\Gateways\PayPal\Request as PayPalRequest;
use Automattic\WooCommerce\Utilities\NumberUtil;
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
	 * The delegated request instance.
	 *
	 * @var PayPalRequest
	 */
	private $request;

	/**
	 * Constructor.
	 *
	 * @param WC_Gateway_Paypal $gateway Paypal gateway object.
	 */
	public function __construct( $gateway ) {
		$this->gateway    = $gateway;
		$this->notify_url = WC()->api_request_url( 'WC_Gateway_Paypal' );
		$this->request    = new PayPalRequest( $gateway );
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
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Request::create_paypal_order() instead. This method will be removed in 11.0.0.
	 * @param WC_Order $order Order object.
	 * @param string   $payment_source The payment source.
	 * @param array    $js_sdk_params Extra parameters for a PayPal JS SDK (Buttons) request.
	 * @return array|null
	 * @throws Exception If the PayPal order creation fails.
	 */
	public function create_paypal_order(
		$order,
		$payment_source = PayPalConstants::PAYMENT_SOURCE_PAYPAL,
		$js_sdk_params = array()
	) {
		wc_deprecated_function( __METHOD__, '10.5.0', 'Automattic\WooCommerce\Gateways\PayPal\Request::create_paypal_order()' );
		return $this->request->create_paypal_order( $order, $payment_source, $js_sdk_params );
	}

	/**
	 * Get PayPal order details.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Request::get_paypal_order_details() instead. This method will be removed in 11.0.0.
	 * @param string $paypal_order_id The ID of the PayPal order.
	 * @return array
	 * @throws Exception If the PayPal order details request fails.
	 * @throws Exception If the PayPal order details are not found.
	 */
	public function get_paypal_order_details( $paypal_order_id ) {
		wc_deprecated_function( __METHOD__, '10.5.0', 'Automattic\WooCommerce\Gateways\PayPal\Request::get_paypal_order_details()' );
		return $this->request->get_paypal_order_details( $paypal_order_id );
	}

	/**
	 * Authorize or capture a PayPal payment using the Orders v2 API.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Request::authorize_or_capture_payment() instead.
	 * @param WC_Order $order Order object.
	 * @param string   $action_url The URL to authorize or capture the payment.
	 * @param string   $action The action to perform. Either 'authorize' or 'capture'.
	 * @return void
	 * @throws Exception If the PayPal payment authorization or capture fails.
	 */
	public function authorize_or_capture_payment( $order, $action_url, $action = PayPalConstants::PAYMENT_ACTION_CAPTURE ) {
		wc_deprecated_function( __METHOD__, '10.5.0', 'Automattic\WooCommerce\Gateways\PayPal\Request::authorize_or_capture_payment()' );
		$this->request->authorize_or_capture_payment( $order, $action_url, $action );
	}

	/**
	 * Capture a PayPal payment that has been authorized.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Request::capture_authorized_payment() instead. This method will be removed in 11.0.0.
	 * @param WC_Order $order Order object.
	 * @return void
	 * @throws Exception If the PayPal payment capture fails.
	 */
	public function capture_authorized_payment( $order ) {
		wc_deprecated_function( __METHOD__, '10.5.0', 'Automattic\WooCommerce\Gateways\PayPal\Request::capture_authorized_payment()' );
		$this->request->capture_authorized_payment( $order );
	}

	/**
	 * Get the authorization ID for the PayPal payment.
	 *
	 * @internal This method is no longer used since public methods delegate to the new Request class.
	 *           It will be removed when the orders v2 methods are fully deprecated in 11.0.0.
	 *
	 * @param WC_Order $order Order object.
	 * @return string|null
	 */
	private function get_authorization_id_for_capture( $order ) {
		$paypal_order_id  = $order->get_meta( '_paypal_order_id', true );
		$authorization_id = $order->get_meta( '_paypal_authorization_id', true );
		$capture_id       = $order->get_meta( '_paypal_capture_id', true );

		// If the PayPal order ID is not found or the capture ID is already set, return null.
		if ( ! $paypal_order_id || ! empty( $capture_id ) ) {
			return null;
		}

		// If '_paypal_authorization_checked' is set to 'yes', it means we've already made an API call to PayPal
		// and confirmed that no authorization object exists. This flag is set in two scenarios:
		// 1. Capture auth API call returned 404 (authorization object does not exist with the authorization ID).
		// 2. Order details API call returned empty authorization array (authorization object does not exist for this PayPal order).
		// Return null to avoid repeated API calls for orders that have no authorization data.
		if ( 'yes' === $order->get_meta( '_paypal_authorization_checked', true ) ) {
			return null;
		}

		// If the authorization ID is not found, try to retrieve it from the PayPal order details.
		if ( empty( $authorization_id ) ) {
			WC_Gateway_Paypal::log( 'Authorization ID not found, trying to retrieve from PayPal order details as a fallback for backwards compatibility. Order ID: ' . $order->get_id() );

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
					$order->update_meta_data( '_paypal_capture_id', $capture_id );
					$order->update_meta_data( '_paypal_status', $capture_data['status'] ?? PayPalConstants::STATUS_CAPTURED );
					$order->save();
					WC_Gateway_Paypal::log( 'Storing capture ID from Paypal. Order ID: ' . $order->get_id() . '; capture ID: ' . $capture_id );
					return null;
				}

				if ( $authorization_data && isset( $authorization_data['id'], $authorization_data['status'] ) ) {
					// If the payment is already captured, return null as there is no authorization ID that needs to be captured.
					if ( PayPalConstants::STATUS_CAPTURED === $authorization_data['status'] ) {
						$order->update_meta_data( '_paypal_status', PayPalConstants::STATUS_CAPTURED );
						$order->save();
						return null;
					}
					$authorization_id = $authorization_data['id'];
					$order->update_meta_data( '_paypal_authorization_id', $authorization_id );
					$order->update_meta_data( '_paypal_status', PayPalConstants::STATUS_AUTHORIZED );
					WC_Gateway_Paypal::log( 'Storing authorization ID from Paypal. Order ID: ' . $order->get_id() . '; authorization ID: ' . $authorization_id );
					$order->save();
				} else {
					// Scenario 2: Order details API call returned empty authorization array (authorization object does not exist).
					// Store '_paypal_authorization_checked' flag to prevent repeated API calls.
					// This flag indicates that we've made an API call to get PayPal order details and confirmed no authorization object exists.
					WC_Gateway_Paypal::log( 'Authorization ID not found in PayPal order details. Order ID: ' . $order->get_id() );
					$order->update_meta_data( '_paypal_authorization_checked', 'yes' );
					$order->save();
					return null;
				}
			} catch ( Exception $e ) {
				WC_Gateway_Paypal::log( 'Error retrieving authorization ID from PayPal order details. Order ID: ' . $order->get_id() . '. Error: ' . $e->getMessage() );
				return null;
			}
		}

		return $authorization_id;
	}

	/**
	 * Get the latest item from the authorizations or captures array based on update_time.
	 *
	 * @internal This method is no longer used since public methods delegate to the new Request class.
	 *           It will be removed when the orders v2 methods are fully deprecated in 11.0.0.
	 *
	 * @param array $items Array of authorizations or captures.
	 * @return array|null The latest authorization or capture or null if array is empty or no valid update_time found.
	 */
	private function get_latest_transaction_data( $items ) {
		if ( empty( $items ) || ! is_array( $items ) ) {
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
	 * @internal This method is no longer used since public methods delegate to the new Request class.
	 *           It will be removed when the orders v2 methods are fully deprecated in 11.0.0.
	 *
	 * @param int   $http_code The HTTP code of the response.
	 * @param array $response_data The response data.
	 * @return string|null
	 */
	private function get_approve_link( $http_code, $response_data ) {
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
	 * @internal This method is no longer used since public methods delegate to the new Request class.
	 *           It will be removed when the orders v2 methods are fully deprecated in 11.0.0.
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
			throw new Exception( 'Currency is not supported by PayPal. Order ID: ' . esc_html( $order->get_id() ) );
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
			$params['payment_source'][ $payment_source ]['experience_context']['order_update_callback_config'] = array(
				'callback_events' => array( 'SHIPPING_ADDRESS', 'SHIPPING_OPTIONS' ),
				'callback_url'    => $this->normalize_url_for_paypal( rest_url( 'wc/v3/paypal-standard/update-shipping' ) ),
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
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Request::get_paypal_order_purchase_unit_amount() instead. This method will be removed in 11.0.0.
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	public function get_paypal_order_purchase_unit_amount( $order ) {
		wc_deprecated_function( __METHOD__, '10.5.0', 'Automattic\WooCommerce\Gateways\PayPal\Request::get_paypal_order_purchase_unit_amount()' );
		return $this->request->get_paypal_order_purchase_unit_amount( $order );
	}

	/**
	 * Build the custom ID for the PayPal order. The custom ID will be used by the proxy for webhook forwarding,
	 * and by later steps to identify the order.
	 *
	 * @internal This method is no longer used since public methods delegate to the new Request class.
	 *           It will be removed when the orders v2 methods are fully deprecated in 11.0.0.
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
				'site_url'  => home_url(),
				'site_id'   => class_exists( 'Jetpack_Options' ) ? Jetpack_Options::get_option( 'id' ) : null,
				'v'         => WC_VERSION,
			)
		);

		if ( strlen( $custom_id ) > 255 ) {
			throw new Exception( 'PayPal order custom ID is too long. Max length is 255 chars.' );
		}

		return $custom_id;
	}

	/**
	 * Get the order items for the PayPal create-order request.
	 * Returns an empty array if any of the items (amount, quantity) are invalid.
	 *
	 * @internal This method is no longer used since public methods delegate to the new Request class.
	 *           It will be removed when the orders v2 methods are fully deprecated in 11.0.0.
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	private function get_paypal_order_items( $order ) {
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
	 * @internal This method is no longer used since public methods delegate to the new Request class.
	 *           It will be removed when the orders v2 methods are fully deprecated in 11.0.0.
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
	 * @internal This method is no longer used since public methods delegate to the new Request class.
	 *           It will be removed when the orders v2 methods are fully deprecated in 11.0.0.
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
	 * @internal This method is no longer used since public methods delegate to the new Request class.
	 *           It will be removed when the orders v2 methods are fully deprecated in 11.0.0.
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
	 * @internal This method is no longer used since public methods delegate to the new Request class.
	 *           It will be removed when the orders v2 methods are fully deprecated in 11.0.0.
	 *
	 * @param WC_Order $order Order object.
	 * @return string
	 */
	private function get_paypal_shipping_preference( $order ) {
		if ( ! $order->needs_shipping() ) {
			return PayPalConstants::SHIPPING_NO_SHIPPING;
		}

		$address_override = $this->gateway->get_option( 'address_override' ) === 'yes';
		return $address_override ? PayPalConstants::SHIPPING_SET_PROVIDED_ADDRESS : PayPalConstants::SHIPPING_GET_FROM_FILE;
	}

	/**
	 * Get the shipping information for the PayPal create-order request.
	 *
	 * @internal This method is no longer used since public methods delegate to the new Request class.
	 *           It will be removed when the orders v2 methods are fully deprecated in 11.0.0.
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
	 * Normalize PayPal order shipping country code.
	 *
	 * @internal This method is no longer used since public methods delegate to the new Request class.
	 *           It will be removed when the orders v2 methods are fully deprecated in 11.0.0.
	 *
	 * @param string $country_code Country code to normalize.
	 * @return string|null
	 */
	private function normalize_paypal_order_shipping_country_code( $country_code ) {
		// Normalize to uppercase.
		$code = strtoupper( trim( (string) $country_code ) );

		// Check if it's a valid alpha-2 code.
		if ( strlen( $code ) === PayPalConstants::PAYPAL_COUNTRY_CODE_LENGTH ) {
			if ( WC()->countries->country_exists( $code ) ) {
				return $code;
			}

			WC_Gateway_Paypal::log( sprintf( 'Invalid country code: %s', $code ) );
			return null;
		}

		// Log when we get an unexpected country code length.
		WC_Gateway_Paypal::log( sprintf( 'Unexpected country code length (%d) for country: %s', strlen( $code ), $code ) );

		// Truncate to the expected maximum length (3).
		$max_country_code_length = PayPalConstants::PAYPAL_COUNTRY_CODE_LENGTH + 1;
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
	 * Normalize a URL for PayPal. PayPal requires absolute URLs with protocol.
	 *
	 * @internal This method is no longer used since public methods delegate to the new Request class.
	 *           It will be removed when the orders v2 methods are fully deprecated in 11.0.0.
	 *
	 * @param string $url The URL to check.
	 * @return string Normalized URL.
	 */
	private function normalize_url_for_paypal( $url ) {
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
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Request::fetch_paypal_client_id() instead. This method will be removed in 11.0.0.
	 * @return string|null The PayPal client-id, or null if the request fails.
	 * @throws Exception If the request fails.
	 */
	public function fetch_paypal_client_id() {
		wc_deprecated_function( __METHOD__, '10.5.0', 'Automattic\WooCommerce\Gateways\PayPal\Request::fetch_paypal_client_id()' );
		return $this->request->fetch_paypal_client_id();
	}

	/**
	 * Send a request to the API proxy.
	 *
	 * @internal This method is no longer used since public methods delegate to the new Request class.
	 *           It will be removed when the orders v2 methods are fully deprecated in 11.0.0.
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
				'invoice'       => $this->limit_length( $this->gateway->get_option( 'invoice_prefix' ) . $order->get_order_number(), PayPalConstants::PAYPAL_INVOICE_ID_MAX_LENGTH ),
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
