<?php
/**
 * Cart Order controller.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers;

defined( 'ABSPATH' ) || exit;

use \WP_Error;
use \WP_REST_Server as RestServer;
use \WP_REST_Controller as RestController;
use \WP_REST_Response as RestResponse;
use \WP_REST_Request as RestRequest;
use \WC_REST_Exception as RestException;
use Automattic\WooCommerce\Blocks\RestApi\StoreApi\Schemas\OrderSchema;
use Automattic\WooCommerce\Blocks\RestApi\StoreApi\Utilities\ReserveStock;

/**
 * Cart Order API.
 *
 * Creates orders based on cart contents.
 */
class CartOrder extends RestController {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/store';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'cart/order';

	/**
	 * Schema class instance.
	 *
	 * @var object
	 */
	protected $schema;

	/**
	 * Draft order details, if applicable.
	 *
	 * @var array
	 */
	protected $draft_order;

	/**
	 * Setup API class.
	 */
	public function __construct() {
		$this->schema = new OrderSchema();
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'  => RestServer::CREATABLE,
					'callback' => array( $this, 'create_item' ),
					'args'     => array_merge(
						$this->get_endpoint_args_for_item_schema( RestServer::CREATABLE ),
						array(
							'shipping_rates' => array(
								'description' => __( 'Selected shipping rates to apply to the order.', 'woo-gutenberg-products-block' ),
								'type'        => 'array',
								'required'    => false,
								'items'       => [
									'type'       => 'object',
									'properties' => [
										'rate_id' => [
											'description' => __( 'ID of the shipping rate.', 'woo-gutenberg-products-block' ),
											'type'        => 'string',
										],
									],
								],
							),
						)
					),
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * Converts the global cart to an order object.
	 *
	 * @todo Since this relies on the cart global so much, why doesn't the core cart class do this?
	 *
	 * Based on WC_Checkout::create_order.
	 *
	 * @param RestRequest $request Full details about the request.
	 * @return WP_Error|RestResponse
	 */
	public function create_item( $request ) {
		try {
			$this->draft_order = WC()->session->get(
				'store_api_draft_order',
				[
					'id'     => 0,
					'hashes' => [
						'line_items' => false,
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

			// Try to reserve stock, if available.
			$this->reserve_stock( $order_object );

			$response = $this->prepare_item_for_response( $order_object, $request );
			$response->set_status( 201 );
			return $response;
		} catch ( RestException $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getCode() );
		} catch ( Exception $e ) {
			return new WP_Error( 'create-order-error', $e->getMessage(), [ 'status' => 500 ] );
		}
	}

	/**
	 * Before creating anything, this method ensures the cart session is up to date and matches the data we're going
	 * to be adding to the order.
	 *
	 * @param RestRequest $request Full details about the request.
	 * @return void
	 */
	protected function update_session( RestRequest $request ) {
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
	}

	/**
	 * Put a temporary hold on stock for this order.
	 *
	 * @throws RestException Exception when stock cannot be reserved.
	 * @param \WC_Order $order Order object.
	 */
	protected function reserve_stock( \WC_Order $order ) {
		$reserve_stock_helper = new ReserveStock();
		$result               = $reserve_stock_helper->reserve_stock_for_order( $order );

		if ( is_wp_error( $result ) ) {
			throw new RestException( $result->get_error_code(), $result->get_error_message(), $result->get_error_data( 'status' ) );
		}
	}

	/**
	 * Create order and set props based on global settings.
	 *
	 * @param RestRequest $request Full details about the request.
	 * @return \WC_Order A new order object.
	 */
	protected function create_order_from_cart( RestRequest $request ) {
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
		$this->select_shipping_rates( $order, $request );

		// Calc totals, taxes, and save.
		$order->calculate_totals();

		remove_filter( 'woocommerce_default_order_status', array( $this, 'default_order_status' ) );

		// Store Order details in session so we can look it up later.
		WC()->session->set(
			'store_api_draft_order',
			[
				'id'     => $order->get_id(),
				'hashes' => $this->get_cart_hashes( $order, $request ),
			]
		);

		return $order;
	}

	/**
	 * Get hashes for items in the current cart. Useful for tracking changes.
	 *
	 * @param \WC_Order   $order Object to prepare for the response.
	 * @param RestRequest $request Full details about the request.
	 * @return array
	 */
	protected function get_cart_hashes( \WC_Order $order, RestRequest $request ) {
		return [
			'line_items' => md5( wp_json_encode( WC()->cart->get_cart() ) ),
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

		if ( $draft_order && $draft_order->has_status( 'draft' ) && 'store-api' === $draft_order->get_created_via() ) {
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
	 * @todo Knowing if items changed between the order and cart can be complex. Line items are ok because there is a
	 * hash, but no hash exists for other line item types. Having a normalised set of data between cart and order, or
	 * additional hashes, would be useful in the future and to help refactor this code. In the meantime, we're relying
	 * on custom hashes in $this->draft_order to track if things changed.
	 *
	 * @param \WC_Order   $order Object to prepare for the response.
	 * @param RestRequest $request Full details about the request.
	 */
	protected function create_line_items_from_cart( \WC_Order $order, RestRequest $request ) {
		$new_hashes = $this->get_cart_hashes( $order, $request );
		$old_hashes = $this->draft_order['hashes'];

		if ( $new_hashes['line_items'] !== $old_hashes['line_items'] ) {
			$order->remove_order_items( 'line_item' );
			WC()->checkout->create_order_line_items( $order, WC()->cart );
		}

		if ( $new_hashes['coupons'] !== $old_hashes['coupons'] ) {
			$order->remove_order_items( 'coupon' );
			WC()->checkout->create_order_coupon_lines( $order, WC()->cart );
		}

		if ( $new_hashes['fees'] !== $old_hashes['fees'] ) {
			$order->remove_order_items( 'fee' );
			WC()->checkout->create_order_fee_lines( $order, WC()->cart );
		}

		if ( $new_hashes['taxes'] !== $old_hashes['taxes'] ) {
			$order->remove_order_items( 'tax' );
			WC()->checkout->create_order_tax_lines( $order, WC()->cart );
		}
	}

	/**
	 * Select shipping rates and store to order as line items.
	 *
	 * @throws RestException Exception when shipping is invalid.
	 * @param \WC_Order   $order Object to prepare for the response.
	 * @param RestRequest $request Full details about the request.
	 */
	protected function select_shipping_rates( \WC_Order $order, RestRequest $request ) {
		$packages       = $this->get_shipping_packages( $order, $request );
		$selected_rates = isset( $request['shipping_rates'] ) ? wp_list_pluck( $request['shipping_rates'], 'rate_id' ) : [];
		$shipping_hash  = md5( wp_json_encode( $packages ) . wp_json_encode( $selected_rates ) );
		$stored_hash    = WC()->session->get( 'store_api_shipping_hash' );

		if ( $shipping_hash === $stored_hash ) {
			return;
		}

		$order->remove_order_items( 'shipping' );

		foreach ( $packages as $package_key => $package ) {
			$rates            = $package['rates'];
			$fallback_rate_id = current( array_keys( $rates ) );
			$selected_rate_id = isset( $selected_rates[ $package_key ] ) ? $selected_rates[ $package_key ] : $fallback_rate_id;
			$selected_rate    = isset( $rates[ $selected_rate_id ] ) ? $rates[ $selected_rate_id ] : false;

			if ( ! $rates ) {
				continue;
			}

			if ( ! $selected_rate ) {
				throw new RestException(
					'invalid-shipping-rate-id',
					sprintf(
						/* translators: 1: Rate ID, 2: list of valid ids */
						__( '%1$s is not a valid shipping rate ID. Select one of the following: %2$s', 'woo-gutenberg-products-block' ),
						$selected_rate_id,
						implode( ', ', array_keys( $rates ) )
					),
					403
				);
			}

			$item = new \WC_Order_Item_Shipping();
			$item->set_props(
				array(
					'method_title' => $selected_rate->label,
					'method_id'    => $selected_rate->method_id,
					'instance_id'  => $selected_rate->instance_id,
					'total'        => wc_format_decimal( $selected_rate->cost ),
					'taxes'        => array(
						'total' => $selected_rate->taxes,
					),
				)
			);

			foreach ( $selected_rate->get_meta_data() as $key => $value ) {
				$item->add_meta_data( $key, $value, true );
			}

			/**
			 * Action hook to adjust item before save.
			 */
			do_action( 'woocommerce_checkout_create_order_shipping_item', $item, $package_key, $package, $order );

			// Add item to order and save.
			$order->add_item( $item );
		}
	}

	/**
	 * Get packages with calculated shipping.
	 *
	 * Based on WC_Cart::get_shipping_packages but allows the destination to be
	 * customised based on the address in the order.
	 *
	 * @param \WC_Order   $order Object to prepare for the response.
	 * @param RestRequest $request Full details about the request.
	 * @return array of packages and shipping rates.
	 */
	protected function get_shipping_packages( \WC_Order $order, RestRequest $request ) {
		if ( ! WC()->cart->needs_shipping() ) {
			return [];
		}

		$packages = WC()->cart->get_shipping_packages();

		foreach ( $packages as $key => $package ) {
			$packages[ $key ]['destination'] = [
				'address_1' => $order->get_shipping_address_1(),
				'address_2' => $order->get_shipping_address_2(),
				'city'      => $order->get_shipping_city(),
				'state'     => $order->get_shipping_state(),
				'postcode'  => $order->get_shipping_postcode(),
				'country'   => $order->get_shipping_country(),
			];
		}

		$packages = WC()->shipping()->calculate_shipping( $packages );

		return $packages;
	}

	/**
	 * Set props from API request.
	 *
	 * @param \WC_Order   $order Object to prepare for the response.
	 * @param RestRequest $request Full details about the request.
	 */
	protected function set_props_from_request( \WC_Order $order, RestRequest $request ) {
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

	/**
	 * Cart item schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return $this->schema->get_item_schema();
	}

	/**
	 * Prepares a single item output for response.
	 *
	 * @param \WC_Order   $object Object to prepare for the response.
	 * @param RestRequest $request Request object.
	 * @return RestResponse Response object.
	 */
	public function prepare_item_for_response( $object, $request ) {
		$response = [];

		if ( $object instanceof \WC_Order ) {
			$response = $this->schema->get_item_response( $object );
		}
		return rest_ensure_response( $response );
	}
}
