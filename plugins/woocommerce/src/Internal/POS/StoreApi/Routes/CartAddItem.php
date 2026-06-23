<?php
/**
 * CartAddItem class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;

/**
 * POS cart/add-item route.
 *
 * Adapter-style: extends the abstract {@see AbstractCartRoute} — the designed
 * extension point, the same one agentic commerce builds on — rather than the
 * concrete `StoreApi\Routes\V1\CartAddItem`, and owns its own endpoint shape.
 * The add-to-cart work is delegated to the shared {@see \Automattic\WooCommerce\StoreApi\Utilities\CartController},
 * so the cart pipeline (and every extension hook it fires) is reused unchanged;
 * only the request/response surface and the POS auth/session seams live here.
 *
 * Because POS owns this endpoint it is free to diverge from the web shape — e.g.
 * accepting a batch of items in a single request (see the marker in
 * {@see self::get_route_post_response()}). Kept single-item here for parity with
 * the web route while the routing approaches are compared.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CartAddItem extends AbstractCartRoute {

	use PosRouteTrait;

	/**
	 * Capability required for any POS request. Override per-route if needed.
	 */
	protected const REQUIRED_CAPABILITY = 'manage_woocommerce';

	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'cart-add-item';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return self::get_path_regex();
	}

	/**
	 * Get the path of this rest route.
	 *
	 * @return string
	 */
	public static function get_path_regex() {
		return '/cart/add-item';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return array(
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array_merge(
					array(
						'id'        => array(
							'description'       => __( 'The cart item product or variation ID.', 'woocommerce' ),
							'type'              => 'integer',
							'context'           => array( 'view', 'edit' ),
							'sanitize_callback' => 'absint',
						),
						'quantity'  => array(
							'description' => __( 'Quantity of this item to add to the cart.', 'woocommerce' ),
							'type'        => 'number',
							'context'     => array( 'view', 'edit' ),
							'arg_options' => array(
								'sanitize_callback' => 'wc_stock_amount',
							),
						),
						'variation' => array(
							'description' => __( 'Chosen attributes (for variations).', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'attribute' => array(
										'description' => __( 'Variation attribute name.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'value'     => array(
										'description' => __( 'Variation attribute value.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
								),
							),
						),
					),
					$this->pos_cart_token_arg()
				),
			),
			'schema'      => array( $this->schema, 'get_public_item_schema' ),
			'allow_batch' => array( 'v1' => true ),
		);
	}

	/**
	 * Handle the request and return a valid response for this endpoint.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		// POS divergence point: because this route owns its shape, a future
		// iteration can accept an array of items here and loop add_to_cart()
		// before returning the cart once — something the single-item web route
		// can't express. Kept single-item for parity during the approach comparison.

		// Do not allow key to be specified during creation.
		if ( ! empty( $request['key'] ) ) {
			throw new RouteException( 'woocommerce_rest_cart_item_exists', esc_html__( 'Cannot create an existing cart item.', 'woocommerce' ), 400 );
		}

		/**
		 * Filters cart item data sent via the API before it is passed to the cart controller.
		 *
		 * This hook filters cart items. It allows the request data to be changed, for example, quantity, or
		 * supplemental cart item data, before it is passed into CartController::add_to_cart and stored to session.
		 *
		 * CartController::add_to_cart only expects the keys id, quantity, variation, and cart_item_data, so other values
		 * may be ignored. CartController::add_to_cart (and core) do already have a filter hook called
		 * woocommerce_add_cart_item, but this does not have access to the original Store API request like this hook does.
		 *
		 * @since 8.8.0
		 *
		 * @param array $add_to_cart_data An array of cart item data.
		 * @return array
		 */
		$add_to_cart_data = apply_filters(
			'woocommerce_store_api_add_to_cart_data',
			array(
				'id'             => $request['id'],
				'quantity'       => $request['quantity'],
				'variation'      => $request['variation'],
				'cart_item_data' => array(),
			),
			$request
		);

		$item_id   = $this->cart_controller->add_to_cart( $add_to_cart_data );
		$cart      = $this->cart_controller->get_cart_instance();
		$cart_item = $cart->get_cart_item( $item_id );

		if ( ! empty( $cart_item ) ) {
			$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$quantity   = $add_to_cart_data['quantity'] ?? $cart_item['quantity'];

			/**
			 * Fires when an item is added to the cart from a user request.
			 *
			 * @param int       $product_id Product ID (variation ID for variable products).
			 * @param int|float $quantity   Quantity added to the cart.
			 *
			 * @since 10.6.0
			 */
			do_action( 'internal_woocommerce_cart_item_added_from_user_request', $product_id, $quantity );
		}

		$response = rest_ensure_response( $this->schema->get_item_response( $this->cart_controller->get_cart_for_response() ) );
		$response->set_status( 201 );
		return $response;
	}
}
