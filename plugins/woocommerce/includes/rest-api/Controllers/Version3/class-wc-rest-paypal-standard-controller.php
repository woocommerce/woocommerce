<?php
/**
 *
 * REST API PayPal Standard controller
 *
 * Handles requests to the /paypal-standard endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   10.3.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Gateways\PayPal\Helper as PayPalHelper;
use Automattic\WooCommerce\Gateways\PayPal\Request as PayPalRequest;
use Automattic\WooCommerce\Gateways\PayPal\Constants as PayPalConstants;
use Automattic\WooCommerce\Enums\OrderStatus;

if ( ! class_exists( 'WC_Gateway_Paypal' ) ) {
	require_once WC_ABSPATH . 'includes/gateways/paypal/class-wc-gateway-paypal.php';
}

// Require the deprecated classes for backward compatibility.
// This will be removed in 11.0.0.
if ( ! class_exists( 'WC_Gateway_Paypal_Request' ) ) {
	require_once WC_ABSPATH . 'includes/gateways/paypal/includes/class-wc-gateway-paypal-request.php';
}

/**
 * REST API PayPal Standard controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Controller
 */
class WC_REST_Paypal_Standard_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'paypal-standard';

	/**
	 * Register the routes for PayPal Standard REST API requests.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/update-shipping',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'process_shipping_callback' ),
				'permission_callback' => array( $this, 'validate_shipping_callback_request' ),
			)
		);
	}

	/**
	 * Validate the shipping callback request.
	 *
	 * @since 10.6.0
	 * @param WP_REST_Request<array<string, mixed>> $request The request object.
	 * @return bool True if the request is valid, false otherwise.
	 */
	public function validate_shipping_callback_request( WP_REST_Request $request ) { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
		$token = $request->get_param( 'token' );
		if ( empty( $token ) ) {
			return false;
		}

		$purchase_units = $request->get_param( 'purchase_units' );
		if ( empty( $purchase_units ) || empty( $purchase_units[0]['custom_id'] ) ) {
			return false;
		}

		$order = PayPalHelper::get_wc_order_from_paypal_custom_id( $purchase_units[0]['custom_id'] );
		if ( ! $order ) {
			return false;
		}

		// If shipping callback token is not stored in order meta, return true for this order as the token is not generated for the original order.
		// We will not validate the token if the order did not generate a token in the create order request.
		// This is done to prevent orders created before the shipping callback token feature was introduced from being blocked from updating their shipping details.
		if ( ! $order->meta_exists( PayPalConstants::PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN ) ) {
			return true;
		}

		$shipping_callback_token = $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN, true );

		if ( empty( $shipping_callback_token ) || ! hash_equals( $token, $shipping_callback_token ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Callback for when the customer updates their shipping details in PayPal.
	 * https://developer.paypal.com/docs/checkout/standard/customize/shipping-module/#server-side-shipping-callbacks
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function process_shipping_callback( WP_REST_Request $request ) {
		$paypal_order_id  = $request->get_param( 'id' );
		$shipping_address = $request->get_param( 'shipping_address' );
		$shipping_option  = $request->get_param( 'shipping_option' );
		$purchase_units   = $request->get_param( 'purchase_units' );

		// Note: shipping_option may or may not be present.
		if ( empty( $paypal_order_id ) || empty( $shipping_address ) || empty( $purchase_units ) ) {
			$response = $this->get_update_shipping_error_response();
			return new WP_REST_Response( $response, 422 );
		}

		// Get the WC order.
		$order = PayPalHelper::get_wc_order_from_paypal_custom_id( $purchase_units[0]['custom_id'] ?? '{}' );
		if ( ! $order ) {
			$custom_id = isset( $purchase_units[0]['custom_id'] ) ? $purchase_units[0]['custom_id'] : '{}';
			WC_Gateway_Paypal::log( 'Unable to determine WooCommerce order from PayPal custom ID: ' . $custom_id );
			$response = $this->get_update_shipping_error_response();
			return new WP_REST_Response( $response, 422 );
		}

		// Compare PayPal order IDs.
		$paypal_order_id_from_order_meta = $order->get_meta( '_paypal_order_id', true );
		if ( empty( $paypal_order_id_from_order_meta ) || $paypal_order_id !== $paypal_order_id_from_order_meta ) {
			WC_Gateway_Paypal::log(
				'PayPal order ID mismatch. Order ID: ' . $order->get_id() .
				'. PayPal order ID (request): ' . $paypal_order_id .
				'. PayPal order ID (order meta): ' . $paypal_order_id_from_order_meta
			);
			$response = $this->get_update_shipping_error_response();
			return new WP_REST_Response( $response, 422 );
		}

		// Validate that the order is in a valid state for shipping updates.
		// Only draft or pending orders should accept shipping updates.
		if ( ! in_array( $order->get_status(), array( OrderStatus::CHECKOUT_DRAFT, OrderStatus::PENDING ), true ) ) {
			WC_Gateway_Paypal::log(
				'Order is not in a valid state for shipping updates. Order ID: ' . $order->get_id() .
				'. Order status: ' . $order->get_status()
			);
			$response = $this->get_update_shipping_error_response();
			return new WP_REST_Response( $response, 422 );
		}

		// If the order has a PayPal transaction ID, a charge has already occurred, so we shouldn't change the shipping address.
		$transaction_id = $order->get_transaction_id();
		if ( ! empty( $transaction_id ) ) {
			WC_Gateway_Paypal::log(
				'Order already has a transaction ID, cannot update shipping. Order ID: ' . $order->get_id() .
				'. Transaction ID: ' . $transaction_id
			);
			$response = $this->get_update_shipping_error_response();
			return new WP_REST_Response( $response, 422 );
		}

		if ( ! WC()->session ) {
			WC()->session = new WC_Session_Handler();
		}
		WC()->session->init();

		// Update the shipping address before we do anything else.
		$this->update_order_shipping_address( $order, $shipping_address );

		// We need to rebuild the cart from the order, as we do not have session cart data
		// for REST API requests.
		$this->rebuild_cart_from_order( $order );

		// Get the new shipping options, which depend on the new shipping address.
		$updated_shipping_options = $this->get_updated_shipping_options( $order, $shipping_option );
		if ( empty( $updated_shipping_options ) ) {
			WC_Gateway_Paypal::log(
				'No shipping options found for address. Order ID: ' . $order->get_id() .
				'. Address: ' . wp_json_encode( $shipping_address )
			);
			$response = $this->get_update_shipping_error_response();
			return new WP_REST_Response( $response, 422 );
		}

		// Set the chosen shipping method in the session.
		if ( ! empty( $shipping_option ) ) {
			WC()->session->set( 'chosen_shipping_methods', array( $shipping_option['id'] ) );
		}

		// Recompute fees after everything has been updated.
		$this->recompute_fees( $order );

		$paypal_request = new PayPalRequest( WC_Gateway_Paypal::get_instance() );
		$updated_amount = $paypal_request->get_paypal_order_purchase_unit_amount( $order );

		$response = array(
			'id'             => $paypal_order_id,
			'purchase_units' => array(
				array(
					'reference_id'     => isset( $purchase_units[0]['reference_id'] ) ? $purchase_units[0]['reference_id'] : '', // No change.
					'amount'           => $updated_amount,
					'shipping_options' => $updated_shipping_options,
				),
			),
		);

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Rebuild the session cart.
	 *
	 * @param WC_Order $order The order object.
	 * @return void
	 */
	private function rebuild_cart_from_order( $order ) {
		wc_load_cart();
		WC()->cart->empty_cart();
		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			$product    = $item->get_product();

			if ( ! $product ) {
				continue;
			}

			if ( $product->is_type( 'variation' ) ) {
				$variation_id = $item->get_variation_id();
				WC()->cart->add_to_cart( $product_id, $item->get_quantity(), $variation_id );
				continue;
			}

			WC()->cart->add_to_cart( $product_id, $item->get_quantity() );
		}

		// Re-apply coupons present on the order so discounts/totals are accurate.
		if ( method_exists( $order, 'get_coupon_codes' ) ) {
			foreach ( (array) $order->get_coupon_codes() as $code ) {
				if ( $code ) {
					WC()->cart->apply_coupon( $code );
				}
			}
		}

		// Re-apply shipping methods present on the order so totals are accurate.
		$order_shipping_rate_id = $this->get_order_shipping_rate_id( $order );
		if ( ! empty( $order_shipping_rate_id ) ) {
			WC()->session->set( 'chosen_shipping_methods', array( $order_shipping_rate_id ) );
		}
	}

	/**
	 * Recompute the fees for the order.
	 *
	 * @param WC_Order $order The order object.
	 * @return void
	 */
	private function recompute_fees( $order ) {
		WC()->cart->calculate_fees();
		WC()->cart->calculate_shipping();
		WC()->cart->calculate_totals();

		$order->remove_order_items();
		WC()->checkout->set_data_from_cart( $order );
		$order->save();
	}

	/**
	 * Update the WooCommerce order with the new shipping address.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $shipping_address The shipping address.
	 * @return void
	 */
	private function update_order_shipping_address( $order, $shipping_address ) {
		$country  = $shipping_address['country_code'] ?? '';
		$postcode = $shipping_address['postal_code'] ?? '';
		$state    = $shipping_address['admin_area_1'] ?? '';
		$city     = $shipping_address['admin_area_2'] ?? '';

		$order->set_shipping_country( $country );
		$order->set_shipping_postcode( $postcode );
		$order->set_shipping_state( $state );
		$order->set_shipping_city( $city );

		// We do not have the address line 1 and 2 -- we are clearing them here to avoid
		// showing stale data. The final address will be updated when the
		// customer approves the order, via 'woocommerce_thankyou_paypal' hook.
		$order->set_shipping_address_1( '' );
		$order->set_shipping_address_2( '' );
		$order->save();

		// Get customer from order and update their shipping location.
		$customer = new WC_Customer();
		$customer->set_location( $country, $state, $postcode, $city );
		$customer->set_shipping_location( $country, $state, $postcode, $city );
		$customer->set_calculated_shipping( true );
		WC()->customer = $customer;
	}

	/**
	 * Get the shipping options for the order.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $selected_shipping_option The selected shipping option.
	 * @return array The shipping options.
	 */
	private function get_updated_shipping_options( $order, $selected_shipping_option ) {
		WC()->cart->calculate_shipping();
		$packages               = WC()->shipping()->get_packages();
		$order_shipping_rate_id = $this->get_order_shipping_rate_id( $order );

		$has_selected_shipping_option = false;
		$options                      = array();
		foreach ( $packages as $package ) {
			$rates = $package['rates'] ?? array();
			foreach ( $rates as $rate ) {
				if ( ! $rate instanceof \WC_Shipping_Rate ) {
					continue;
				}

				$shipping_option_id = $rate->get_id();
				// If a selected shipping option is sent in the request, check if it matches the shipping option id.
				// Otherwise, if the order has a shipping method, check if the rate id matches the shipping option id.
				if ( isset( $selected_shipping_option['id'] ) ) {
					$is_selected = $shipping_option_id === $selected_shipping_option['id'];
				} else {
					$is_selected = $shipping_option_id === $order_shipping_rate_id;
				}

				if ( $is_selected ) {
					$has_selected_shipping_option = true;
				}
				$options[] = array(
					'id'       => $shipping_option_id,
					'type'     => 'SHIPPING',
					'amount'   => array(
						'currency_code' => $order->get_currency(),
						'value'         => wc_format_decimal( (float) $rate->get_cost(), wc_get_price_decimals() ),
					),
					'label'    => $rate->get_label(),
					'selected' => $is_selected,
				);
			}
		}

		// Set first option as selected if no option is selected.
		if ( ! empty( $options ) && ! $has_selected_shipping_option ) {
			$options[0]['selected'] = true;
		}

		return $options;
	}

	/**
	 * Get the shipping rate id from the order.
	 *
	 * @param WC_Order $order The order object.
	 * @return string The shipping rate id.
	 */
	private function get_order_shipping_rate_id( $order ) {
		$order_shipping_item = current( $order->get_items( 'shipping' ) ) ?? null;

		if ( $order_shipping_item ) {
			$method_id   = $order_shipping_item->get_method_id();
			$instance_id = $order_shipping_item->get_instance_id();
			$rate_id     = ( '' === $instance_id || null === $instance_id ) ? $method_id : "{$method_id}:{$instance_id}";

			return $rate_id;
		}

		return '';
	}

	/**
	 * Get the error response for the update shipping request.
	 *
	 * @param string $issue The issue with the shipping address.
	 * @return array The error response.
	 */
	private function get_update_shipping_error_response( $issue = 'ADDRESS_ERROR' ) {
		// See https://developer.paypal.com/docs/checkout/standard/customize/shipping-module/#merchant-decline-response.
		return array(
			'name'    => 'UNPROCESSABLE_ENTITY',
			'details' => array(
				array( 'issue' => $issue ),
			),
		);
	}
}
