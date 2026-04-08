<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

/**
 * CartUpdateItem class.
 */
class CartUpdateItem extends AbstractCartRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'cart-update-item';

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
		return '/cart/update-item';
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
				'permission_callback' => '__return_true',
				'args'                => [
					'key'      => [
						'description' => __( 'Unique identifier (key) for the cart item to update.', 'woocommerce' ),
						'type'        => 'string',
					],
					'quantity' => [
						'description' => __( 'New quantity of the item in the cart.', 'woocommerce' ),
						'type'        => 'number',
						'arg_options' => [
							'sanitize_callback' => 'wc_stock_amount',
						],
					],
				],
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}

	/**
	 * Handle the request and return a valid response for this endpoint.
	 * .
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$cart = $this->cart_controller->get_cart_instance();

		if ( isset( $request['quantity'] ) ) {
			$cart_item    = $cart->get_cart_item( $request['key'] );
			$old_quantity = $cart_item['quantity'] ?? 0;
			$this->cart_controller->set_cart_item_quantity( $request['key'], $request['quantity'] );

			if ( $old_quantity !== (int) $request['quantity'] ) {
				/**
				 * Fires when a cart item quantity is updated from a user request.
				 *
				 * @param string    $cart_item_key Cart item key.
				 * @param int       $quantity      New quantity.
				 * @param int|float $old_quantity  Old quantity.
				 * @param \WC_Cart  $cart          Cart object.
				 *
				 * @since 10.6.0
				 */
				do_action( 'internal_woocommerce_cart_item_updated_from_user_request', $request['key'], (int) $request['quantity'], $old_quantity, $cart );
			}
		}

		return rest_ensure_response( $this->schema->get_item_response( $cart ) );
	}
}
