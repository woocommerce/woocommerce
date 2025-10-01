<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * CheckoutSessions class.
 *
 * Handles the Agentic Checkout API checkout sessions endpoint.
 * This endpoint allows AI agents to create and manage checkout sessions.
 */
class CheckoutSessions extends AbstractCartRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'agentic-checkout-sessions';

	/**
	 * The route's schema type.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'agentic-checkout-session';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/checkout_sessions';
	}

	/**
	 * Get the path regex for this REST route.
	 *
	 * @return string
	 */
	public static function get_path_regex() {
		return '/checkout_sessions';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => [ $this, 'is_authorized' ],
				'args'                => $this->get_create_params(),
			],
			'schema' => [ $this->schema, 'get_public_item_schema' ],
		];
	}

	/**
	 * Get the parameters for creating a checkout session.
	 *
	 * @return array Parameters array.
	 */
	protected function get_create_params() {
		return [
			'items'                 => [
				'description' => __( 'Line items to add to the cart.', 'woocommerce' ),
				'type'        => 'array',
				'required'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'id'       => [
							'description' => __( 'Product ID.', 'woocommerce' ),
							'type'        => 'string',
							'required'    => true,
						],
						'quantity' => [
							'description' => __( 'Quantity.', 'woocommerce' ),
							'type'        => 'integer',
							'required'    => true,
							'minimum'     => 1,
						],
					],
				],
			],
			'buyer'                 => [
				'description' => __( 'Buyer information.', 'woocommerce' ),
				'type'        => 'object',
				'properties'  => [
					'first_name'   => [
						'description' => __( 'First name.', 'woocommerce' ),
						'type'        => 'string',
					],
					'last_name'    => [
						'description' => __( 'Last name.', 'woocommerce' ),
						'type'        => 'string',
					],
					'email'        => [
						'description' => __( 'Email address.', 'woocommerce' ),
						'type'        => 'string',
					],
					'phone_number' => [
						'description' => __( 'Phone number.', 'woocommerce' ),
						'type'        => 'string',
					],
				],
			],
			'fulfillment_address'   => [
				'description' => __( 'Fulfillment/shipping address.', 'woocommerce' ),
				'type'        => 'object',
				'properties'  => [
					'name'        => [
						'description' => __( 'Full name.', 'woocommerce' ),
						'type'        => 'string',
					],
					'line_one'    => [
						'description' => __( 'Address line 1.', 'woocommerce' ),
						'type'        => 'string',
						'required'    => true,
					],
					'line_two'    => [
						'description' => __( 'Address line 2.', 'woocommerce' ),
						'type'        => 'string',
					],
					'city'        => [
						'description' => __( 'City.', 'woocommerce' ),
						'type'        => 'string',
						'required'    => true,
					],
					'state'       => [
						'description' => __( 'State/province.', 'woocommerce' ),
						'type'        => 'string',
					],
					'country'     => [
						'description' => __( 'Country code (ISO 3166-1 alpha-2).', 'woocommerce' ),
						'type'        => 'string',
						'required'    => true,
					],
					'postal_code' => [
						'description' => __( 'Postal/ZIP code.', 'woocommerce' ),
						'type'        => 'string',
						'required'    => true,
					],
				],
			],
			'fulfillment_option_id' => [
				'description' => __( 'Selected fulfillment option ID.', 'woocommerce' ),
				'type'        => 'string',
			],
		];
	}

	/**
	 * Check if the request is authorized.
	 *
	 * V1 implementation: Return true for now (skip auth check).
	 * Future: Implement Bearer token authentication.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool True if authorized.
	 */
	public function is_authorized( \WP_REST_Request $request ) {
		// Check if feature is enabled.
		$features_controller = wc_get_container()->get( FeaturesController::class );
		if ( ! $features_controller->feature_is_enabled( 'agentic_checkout' ) ) {
			return new \WP_Error(
				'woocommerce_rest_agentic_checkout_disabled',
				__( 'Agentic Checkout API is not enabled.', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

		// V1: Allow all requests (implement proper auth in future).
		return true;
	}

	/**
	 * Check if a nonce is required for the route.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool False, Bearer token auth used instead.
	 */
	protected function requires_nonce( \WP_REST_Request $request ) {
		// Should use `is_authorized` to validate Bearer token authentication.
		return false;
	}

	/**
	 * Handle the request and return a valid response for this endpoint.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		// Clear existing cart.
		WC()->cart->empty_cart();

		// Add items to cart.
		$items = $request->get_param( 'items' );
		foreach ( $items as $item ) {
			$product_id = (int) $item['id'];
			$quantity   = (int) $item['quantity'];

			// Get product.
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				return new \WP_REST_Response(
					[
						'type'    => 'invalid_request',
						'code'    => 'invalid_product',
						'message' => sprintf(
							/* translators: %s: product ID */
							__( 'Product with ID %s not found.', 'woocommerce' ),
							$product_id
						),
						'param'   => '$.items[' . array_search( $item, $items, true ) . '].id',
					],
					400
				);
			}

			// Check stock.
			if ( ! $product->is_in_stock() || ! $product->has_enough_stock( $quantity ) ) {
				return new \WP_REST_Response(
					[
						'type'    => 'invalid_request',
						'code'    => 'out_of_stock',
						'message' => sprintf(
							/* translators: %s: product name */
							__( 'Product "%s" is out of stock.', 'woocommerce' ),
							$product->get_name()
						),
						'param'   => '$.items[' . array_search( $item, $items, true ) . ']',
					],
					400
				);
			}

			// Add to cart.
			WC()->cart->add_to_cart( $product_id, $quantity );
		}

		// Set buyer information.
		$buyer = $request->get_param( 'buyer' );
		if ( $buyer ) {
			$this->set_buyer_data( $buyer );
		}

		// Set fulfillment address.
		$address = $request->get_param( 'fulfillment_address' );
		if ( $address ) {
			$this->set_fulfillment_address( $address );
		} else {
			// Clear address when not provided (POST creates fresh session).
			$this->clear_fulfillment_address();
		}

		// Set selected shipping method if provided.
		$fulfillment_option_id = $request->get_param( 'fulfillment_option_id' );
		if ( $fulfillment_option_id ) {
			WC()->session->set( 'chosen_shipping_methods', array( $fulfillment_option_id ) );
		}

		// Calculate totals after shipping method is set.
		WC()->cart->calculate_totals();

		// Create/update draft order.
		$draft_order = $this->create_or_update_draft_order();

		// Store draft order ID in session.
		WC()->session->set( 'agentic_draft_order_id', $draft_order->get_id() );

		// Build response.
		$response = $this->schema->get_item_response( WC()->cart );

		return rest_ensure_response( $response );
	}

	/**
	 * Set buyer data on customer.
	 *
	 * @param array $buyer Buyer data.
	 */
	protected function set_buyer_data( $buyer ) {
		$customer = WC()->customer;

		if ( isset( $buyer['first_name'] ) ) {
			$customer->set_billing_first_name( $buyer['first_name'] );
			$customer->set_shipping_first_name( $buyer['first_name'] );
		}

		if ( isset( $buyer['last_name'] ) ) {
			$customer->set_billing_last_name( $buyer['last_name'] );
			$customer->set_shipping_last_name( $buyer['last_name'] );
		}

		if ( isset( $buyer['email'] ) ) {
			$customer->set_billing_email( $buyer['email'] );
		}

		if ( isset( $buyer['phone_number'] ) ) {
			$customer->set_billing_phone( $buyer['phone_number'] );
		}

		$customer->save();
	}

	/**
	 * Set fulfillment address on customer.
	 *
	 * @param array $address Address data.
	 */
	protected function set_fulfillment_address( $address ) {
		$customer = WC()->customer;

		// Parse name into first and last.
		$name_parts = isset( $address['name'] ) ? explode( ' ', $address['name'], 2 ) : [ '', '' ];
		$first_name = $name_parts[0];
		$last_name  = isset( $name_parts[1] ) ? $name_parts[1] : '';

		// Set shipping address.
		$customer->set_shipping_first_name( $first_name );
		$customer->set_shipping_last_name( $last_name );
		$customer->set_shipping_address_1( $address['line_one'] );
		$customer->set_shipping_address_2( $address['line_two'] ?? '' );
		$customer->set_shipping_city( $address['city'] );
		$customer->set_shipping_state( $address['state'] ?? '' );
		$customer->set_shipping_postcode( $address['postal_code'] );
		$customer->set_shipping_country( $address['country'] );

		// Also set as billing address if not already set.
		if ( ! $customer->get_billing_address_1() ) {
			$customer->set_billing_first_name( $first_name );
			$customer->set_billing_last_name( $last_name );
			$customer->set_billing_address_1( $address['line_one'] );
			$customer->set_billing_address_2( $address['line_two'] ?? '' );
			$customer->set_billing_city( $address['city'] );
			$customer->set_billing_state( $address['state'] ?? '' );
			$customer->set_billing_postcode( $address['postal_code'] );
			$customer->set_billing_country( $address['country'] );
		}

		$customer->save();

		// Recalculate shipping.
		WC()->cart->calculate_shipping();
	}

	/**
	 * Clear fulfillment address from customer.
	 */
	protected function clear_fulfillment_address() {
		$customer = WC()->customer;

		// Clear shipping address.
		$customer->set_shipping_first_name( '' );
		$customer->set_shipping_last_name( '' );
		$customer->set_shipping_address_1( '' );
		$customer->set_shipping_address_2( '' );
		$customer->set_shipping_city( '' );
		$customer->set_shipping_state( '' );
		$customer->set_shipping_postcode( '' );
		$customer->set_shipping_country( '' );

		$customer->save();

		// Recalculate shipping.
		WC()->cart->calculate_shipping();
	}

	/**
	 * Create or update draft order.
	 *
	 * @return \WC_Order Draft order.
	 */
	protected function create_or_update_draft_order() {
		// Check if we already have a draft order in session.
		$session_id = WC()->session->get( 'agentic_draft_order_id' );
		$order      = $session_id ? wc_get_order( $session_id ) : null;

		// Create new draft order if none exists.
		if ( ! $order ) {
			$order = wc_create_order(
				[
					'status'      => 'checkout-draft',
					'customer_id' => WC()->customer->get_id(),
				]
			);
		}

		// Update order from cart.
		$this->update_order_from_cart( $order, WC()->cart );

		// Save and return.
		$order->save();

		return $order;
	}

	/**
	 * Update order from cart data.
	 *
	 * @param \WC_Order $order Order object.
	 * @param \WC_Cart  $cart  Cart object.
	 */
	protected function update_order_from_cart( $order, $cart ) {
		// Remove existing items.
		foreach ( $order->get_items() as $item_id => $item ) {
			$order->remove_item( $item_id );
		}

		// Add cart items to order.
		foreach ( $cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];
			$item    = new \WC_Order_Item_Product();
			$item->set_props(
				[
					'quantity' => $cart_item['quantity'],
					'subtotal' => $cart_item['line_subtotal'],
					'total'    => $cart_item['line_total'],
				]
			);
			$item->set_product( $product );
			$order->add_item( $item );
		}

		// Set addresses.
		$customer = WC()->customer;
		$order->set_address( $customer->get_billing(), 'billing' );
		$order->set_address( $customer->get_shipping(), 'shipping' );

		// Set shipping.
		if ( ! empty( $cart->get_shipping_total() ) ) {
			$shipping_item = new \WC_Order_Item_Shipping();
			$shipping_item->set_method_title( __( 'Shipping', 'woocommerce' ) );
			$shipping_item->set_total( $cart->get_shipping_total() );
			$order->add_item( $shipping_item );
		}

		// Calculate totals.
		$order->calculate_totals();
	}
}
