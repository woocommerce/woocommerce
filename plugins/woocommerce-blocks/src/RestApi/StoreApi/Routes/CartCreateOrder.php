<?php
/**
 * Cart order route.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Routes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\RestApi\StoreApi\Utilities\CartController;

/**
 * CartCreateOrder class.
 */
class CartCreateOrder extends AbstractRoute {
	/**
	 * Get the namespace for this route.
	 *
	 * @return string
	 */
	public function get_namespace() {
		return 'wc/store';
	}

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/cart/create-order';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'get_response' ],
				'args'     => $this->schema->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
			],
			'schema' => [ $this->schema, 'get_public_item_schema' ],
		];
	}

	/**
	 * Convert the cart into a new draft order, or update an existing draft order, and return an updated cart response.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$this->draft_order = WC()->session->get(
			'store_api_draft_order',
			[
				'id'     => 0,
				'hashes' => [
					'line_items' => false,
					'shipping'   => false,
					'fees'       => false,
					'coupons'    => false,
					'taxes'      => false,
				],
			]
		);

		// Update session based on posted data.
		$this->update_session( $request );

		// Create or retrieve the draft order for the current cart.
		$order_object = $this->create_order_from_cart( $request );

		// Try to reserve stock for 10 mins, if available.
		// @todo Remove once min support for WC reaches 4.0.0.
		if ( \class_exists( '\Automattic\WooCommerce\Checkout\Helpers\ReserveStock' ) ) {
			$reserve_stock = new \Automattic\WooCommerce\Checkout\Helpers\ReserveStock();
		} else {
			$reserve_stock = new \Automattic\WooCommerce\Blocks\RestApi\StoreApi\Utilities\ReserveStock();
		}

		$reserve_stock->reserve_stock_for_order( $order_object, 10 );
		$response = $this->prepare_item_for_response( $order_object, $request );
		$response->set_status( 201 );
		return $response;
	}

	/**
	 * Before creating anything, this method ensures the cart session is up to date and matches the data we're going
	 * to be adding to the order.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return void
	 */
	protected function update_session( \WP_REST_Request $request ) {
		$schema = $this->get_item_schema();

		if ( isset( $request['billing_address'] ) ) {
			$allowed_billing_values = array_intersect_key( $request['billing_address'], $schema['properties']['billing_address']['properties'] );
			foreach ( $allowed_billing_values as $key => $value ) {
				WC()->customer->{"set_billing_$key"}( $value );
			}
		}

		if ( isset( $request['shipping_address'] ) ) {
			$allowed_shipping_values = array_intersect_key( $request['shipping_address'], $schema['properties']['shipping_address']['properties'] );
			foreach ( $allowed_shipping_values as $key => $value ) {
				WC()->customer->{"set_shipping_$key"}( $value );
			}
		}

		WC()->customer->save();
		WC()->cart->calculate_shipping();
		WC()->cart->calculate_totals();
	}

	/**
	 * Create order and set props based on global settings.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WC_Order A new order object.
	 */
	protected function create_order_from_cart( \WP_REST_Request $request ) {
		add_filter( 'woocommerce_default_order_status', array( $this, 'default_order_status' ) );

		$order = $this->get_order_object();
		$order->set_status( 'checkout-draft' );
		$order->set_created_via( 'store-api' );
		$order->set_currency( get_woocommerce_currency() );
		$order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
		$order->set_customer_id( get_current_user_id() );
		$order->set_customer_ip_address( \WC_Geolocation::get_ip_address() );
		$order->set_customer_user_agent( wc_get_user_agent() );
		$order->set_cart_hash( WC()->cart->get_cart_hash() );
		$order->update_meta_data( 'is_vat_exempt', WC()->cart->get_customer()->get_is_vat_exempt() ? 'yes' : 'no' );

		$this->set_props_from_request( $order, $request );
		$this->create_line_items_from_cart( $order, $request );

		// Calc totals, taxes, and save.
		$order->calculate_totals();

		remove_filter( 'woocommerce_default_order_status', array( $this, 'default_order_status' ) );

		// Store Order details in session so we can look it up later.
		WC()->session->set(
			'store_api_draft_order',
			[
				'id'     => $order->get_id(),
				'hashes' => $this->get_cart_hashes(),
			]
		);

		return $order;
	}

	/**
	 * Get hashes for items in the current cart. Useful for tracking changes.
	 *
	 * @return array
	 */
	protected function get_cart_hashes() {
		return [
			'line_items' => md5( wp_json_encode( WC()->cart->get_cart() ) ),
			'shipping'   => md5( wp_json_encode( WC()->cart->shipping_methods ) ),
			'fees'       => md5( wp_json_encode( WC()->cart->get_fees() ) ),
			'coupons'    => md5( wp_json_encode( WC()->cart->get_applied_coupons() ) ),
			'taxes'      => md5( wp_json_encode( WC()->cart->get_taxes() ) ),
		];
	}

	/**
	 * Get an order object, either using a current draft order, or returning a new one.
	 *
	 * @return \WC_Order A new order object.
	 */
	protected function get_order_object() {
		$draft_order = $this->draft_order['id'] ? wc_get_order( $this->draft_order['id'] ) : false;

		if ( $draft_order && $draft_order->has_status( 'checkout-draft' ) && 'store-api' === $draft_order->get_created_via() ) {
			return $draft_order;
		}

		return new \WC_Order();
	}

	/**
	 * Changes default order status to draft for orders created via this API.
	 *
	 * @return string
	 */
	public function default_order_status() {
		return 'checkout-draft';
	}

	/**
	 * Create order line items.
	 *
	 * @internal Knowing if items changed between the order and cart can be complex. Line items are ok because there is a
	 * hash, but no hash exists for other line item types. Having a normalized set of data between cart and order, or
	 * additional hashes, would be useful in the future and to help refactor this code. In the meantime, we're relying
	 * on custom hashes in $this->draft_order to track if things changed.
	 *
	 * @param \WC_Order        $order Object to prepare for the response.
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	protected function create_line_items_from_cart( \WC_Order $order, \WP_REST_Request $request ) {
		$new_hashes = $this->get_cart_hashes();
		$old_hashes = $this->draft_order['hashes'];
		$force      = $this->draft_order['id'] !== $order->get_id();

		if ( $force || $new_hashes['line_items'] !== $old_hashes['line_items'] ) {
			$order->remove_order_items( 'line_item' );
			WC()->checkout->create_order_line_items( $order, WC()->cart );
		}

		if ( $force || $new_hashes['shipping'] !== $old_hashes['shipping'] ) {
			$order->remove_order_items( 'shipping' );
			WC()->checkout->create_order_shipping_lines( $order, WC()->session->get( 'chosen_shipping_methods' ), WC()->shipping()->get_packages() );
		}

		if ( $force || $new_hashes['coupons'] !== $old_hashes['coupons'] ) {
			$order->remove_order_items( 'coupon' );
			WC()->checkout->create_order_coupon_lines( $order, WC()->cart );
		}

		if ( $force || $new_hashes['fees'] !== $old_hashes['fees'] ) {
			$order->remove_order_items( 'fee' );
			WC()->checkout->create_order_fee_lines( $order, WC()->cart );
		}

		if ( $force || $new_hashes['taxes'] !== $old_hashes['taxes'] ) {
			$order->remove_order_items( 'tax' );
			WC()->checkout->create_order_tax_lines( $order, WC()->cart );
		}
	}

	/**
	 * Set props from API request.
	 *
	 * @param \WC_Order        $order Object to prepare for the response.
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	protected function set_props_from_request( \WC_Order $order, \WP_REST_Request $request ) {
		$schema = $this->get_item_schema();

		if ( isset( $request['billing_address'] ) ) {
			$allowed_billing_values = array_intersect_key( $request['billing_address'], $schema['properties']['billing_address']['properties'] );
			foreach ( $allowed_billing_values as $key => $value ) {
				$order->{"set_billing_$key"}( $value );
			}
		}

		if ( isset( $request['shipping_address'] ) ) {
			$allowed_shipping_values = array_intersect_key( $request['shipping_address'], $schema['properties']['shipping_address']['properties'] );
			foreach ( $allowed_shipping_values as $key => $value ) {
				$order->{"set_shipping_$key"}( $value );
			}
		}

		if ( isset( $request['customer_note'] ) ) {
			$order->set_customer_note( $request['customer_note'] );
		}
	}
}
