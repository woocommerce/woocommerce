<?php
/**
 * Cart API controller.
 *
 * Handles customer Carts.
 *
 * @internal This API is used internally by the block post editor--it is still in flux. It should not be used outside of wc-blocks.
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\Controllers;

defined( 'ABSPATH' ) || exit;

use \WP_Error;
use \WP_REST_Server;
use \WP_REST_Controller;

/**
 * Cart API.
 */
class Cart extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/blocks';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'cart';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		// Collection of cart items.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'  => WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_items' ],
					'args'     => [
						'context' => $this->get_context_param( [ 'default' => 'view' ] ),
					],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			],
			true
		);

		// Individual cart items.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\s]+)',
			[
				'args'   => [
					'id' => [
						'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
						'type'        => 'string',
					],
				],
				[
					'methods'  => WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_item' ],
					'args'     => [
						'context' => $this->get_context_param( [ 'default' => 'view' ] ),
					],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		// Adding to cart.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/add',
			[
				[
					'methods'  => WP_REST_Server::CREATABLE,
					'callback' => [ $this, 'add_item' ],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			],
			true
		);
	}

	/**
	 * Cart item schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'cart_item',
			'type'       => 'object',
			'properties' => array(
				'id'           => array(
					'description' => __( 'Unique identifier for the cart item in this cart.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'quantity'     => array(
					'description' => __( 'Quantity of this item in the cart.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'product_id'   => array(
					'description' => __( 'ID of the product this cart item represents.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'variation_id' => array(
					'description' => __( 'ID of the variation this cart item represents, if applicable.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'variation'    => array(
					'description' => __( 'If this cart item represents a variation, chosen attributes are shown here.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'attribute' => array(
								'description' => __( 'Variation attribute.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'value'     => array(
								'description' => __( 'Attribute value.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Get a collection of cart items.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {
		$cart  = wc()->cart->get_cart();
		$items = array_filter( array_map( [ $this, 'get_object_for_response' ], array_values( $cart ) ) );

		return $items;
	}

	/**
	 * Get a single cart items.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_item( $request ) {
		$cart_item = wc()->cart->get_cart_item( $request['id'] );

		if ( ! $cart_item ) {
			return new WP_Error( 'woocommerce_rest_cart_invalid_id', __( 'Invalid cart item ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$object   = $this->get_object_for_response( $cart_item );
		$response = rest_ensure_response( $object );

		return $response;
	}

	/**
	 * Adds an item to the cart.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function add_item( $request ) {
		$product_id   = absint( $request['product_id'] );
		$variation_id = absint( $request['variation_id'] );
		$quantity     = wc_stock_amount( $request['quantity'] );

		if ( ! $product_id ) {
			return new WP_Error( 'woocommerce_rest_cart_error', __( 'Missing product ID.', 'woocommerce' ) );
		}

		if ( ! $quantity ) {
			return new WP_Error( 'woocommerce_rest_cart_error', __( 'Quantity cannot be empty.', 'woocommerce' ) );
		}

		// @todo handle variations
		$result = $this->add_to_cart( $product_id, $quantity, $variation_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$cart_item = wc()->cart->get_cart_item( $result );

		if ( ! $cart_item ) {
			return new WP_Error( 'woocommerce_rest_cart_invalid_id', __( 'Invalid cart item ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$object   = $this->get_object_for_response( $cart_item );
		$response = rest_ensure_response( $object );

		return $response;
	}

	/**
	 * Convert a woo cart item to JSON schema.
	 *
	 * @param array $cart_item Cart item array.
	 * @return array
	 */
	protected function get_object_for_response( $cart_item ) {
		$item = [
			'id'           => $cart_item['key'],
			'quantity'     => wc_stock_amount( $cart_item['quantity'] ),
			'product_id'   => absint( $cart_item['product_id'] ),
			'variation_id' => absint( $cart_item['variation_id'] ),
			'variation'    => [],
		];

		if ( $cart_item['variation'] ) {
			foreach ( $cart_item['variation'] as $key => $value ) {
				$item['variation'][] = [
					'attribute' => $key,
					'value'     => $value,
				];
			}
		}

		return $item;
	}

	/**
	 * Based on the core cart class but returns errors rather than rendering notices directly.
	 *
	 * @param int   $product_id contains the id of the product to add to the cart.
	 * @param int   $quantity contains the quantity of the item to add.
	 * @param int   $variation_id ID of the variation being added to the cart.
	 * @param array $variation attribute values.
	 * @param array $cart_item_data extra cart item data we want to pass into the item.
	 * @return string|\WP_Error
	 */
	protected function add_to_cart( $product_id = 0, $quantity = 1, $variation_id = 0, $variation = array(), $cart_item_data = array() ) {
		$product_object   = wc_get_product( $variation_id ? $variation_id : $product_id );
		$cart_item_data   = (array) apply_filters( 'woocommerce_add_cart_item_data', $cart_item_data, $product_id, $variation_id, $quantity );
		$cart_id          = wc()->cart->generate_cart_id( $product_id, $variation_id, $variation, $cart_item_data );
		$existing_cart_id = wc()->cart->find_product_in_cart( $cart_id );

		if ( ! $product_object || 'trash' === $product_object->get_status() ) {
			return new WP_Error( 'woocommerce_rest_cart_invalid_product', __( 'This product cannot be added to the cart.', 'woocommerce' ) );
		}

		if ( ! $product_object->is_purchasable() ) {
			return new WP_Error( 'woocommerce_rest_cart_product_is_not_purchasable', __( 'This product cannot be purchased.', 'woocommerce' ) );
		}

		if ( ! $product_object->is_in_stock() ) {
			/* translators: %s: product name */
			return new WP_Error( 'woocommerce_rest_cart_product_no_stock', sprintf( __( 'You cannot add &quot;%s&quot; to the cart because the product is out of stock.', 'woocommerce' ), $product_object->get_name() ) );
		}

		if ( $product_object->is_sold_individually() ) {
			$quantity = apply_filters( 'woocommerce_add_to_cart_sold_individually_quantity', 1, $quantity, $product_id, $variation_id, $cart_item_data );

			if ( $existing_cart_id ) {
				/* translators: %s: product name */
				return new WP_Error( 'woocommerce_rest_cart_product_sold_individually', sprintf( __( 'You cannot add another "%s" to your cart.', 'woocommerce' ), $product_object->get_name() ) );
			}
		}

		if ( $product_object->managing_stock() ) {
			$cart_product_quantities   = wc()->cart->get_cart_item_quantities();
			$stock_controller_id       = $product_object->get_stock_managed_by_id();
			$stock_controller_quantity = isset( $cart_product_quantities[ $stock_controller_id ] ) ? $cart_product_quantities[ $stock_controller_id ] : 0;

			if ( ! $product_object->has_enough_stock( $stock_controller_quantity + $quantity ) ) {
				return new WP_Error(
					'woocommerce_rest_cart_product_no_stock',
					/* translators: 1: product name 2: quantity in stock */
					sprintf( __( 'You cannot add that amount of &quot;%1$s&quot; to the cart because there is not enough stock (%2$s remaining).', 'woocommerce' ), $product_object->get_name(), wc_format_stock_quantity_for_display( $product_object->get_stock_quantity(), $product_object ) )
				);
			}
		}

		if ( $existing_cart_id ) {
			wc()->cart->set_quantity( $existing_cart_id, $quantity + wc()->cart->cart_contents[ $existing_cart_id ]['quantity'], true );

			return $existing_cart_id;
		}

		wc()->cart->cart_contents[ $cart_id ] = apply_filters(
			'woocommerce_add_cart_item',
			array_merge(
				$cart_item_data,
				array(
					'key'          => $cart_id,
					'product_id'   => $product_id,
					'variation_id' => $variation_id,
					'variation'    => $variation,
					'quantity'     => $quantity,
					'data'         => $product_object,
					'data_hash'    => wc_get_cart_item_data_hash( $product_object ),
				)
			),
			$cart_id
		);

		wc()->cart->cart_contents = apply_filters( 'woocommerce_cart_contents_changed', wc()->cart->cart_contents );

		do_action( 'woocommerce_add_to_cart', $cart_id, $product_id, $quantity, $variation_id, $variation, $cart_item_data );

		return $cart_id;
	}
}
