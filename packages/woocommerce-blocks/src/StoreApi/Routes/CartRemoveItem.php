<?php
/**
 * Cart remove item route.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\StoreApi\Routes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\StoreApi\Utilities\CartController;

/**
 * CartRemoveItem class.
 */
class CartRemoveItem extends AbstractCartRoute {
	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/cart/remove-item';
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
					'key' => [
						'description' => __( 'Unique identifier (key) for the cart item.', 'woocommerce' ),
						'type'        => 'string',
					],
				],
			],
			'schema' => [ $this->schema, 'get_public_item_schema' ],
		];
	}

	/**
	 * Handle the request and return a valid response for this endpoint.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$controller = new CartController();
		$cart       = $controller->get_cart_instance();
		$cart_item  = $controller->get_cart_item( $request['key'] );

		if ( ! $cart_item ) {
			throw new RouteException( 'woocommerce_rest_cart_invalid_key', __( 'Cart item no longer exists or is invalid.', 'woocommerce' ), 409 );
		}

		$cart->remove_cart_item( $request['key'] );

		return rest_ensure_response( $this->schema->get_item_response( $cart ) );
	}
}
