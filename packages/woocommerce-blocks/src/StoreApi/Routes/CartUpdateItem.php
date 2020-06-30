<?php
/**
 * Cart update item route.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\StoreApi\Routes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\StoreApi\Utilities\CartController;

/**
 * CartUpdateItem class.
 */
class CartUpdateItem extends AbstractCartRoute {
	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
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
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'get_response' ],
				'args'     => [
					'key'      => [
						'description' => __( 'Unique identifier (key) for the cart item to update.', 'woocommerce' ),
						'type'        => 'string',
					],
					'quantity' => [
						'description' => __( 'New quantity of the item in the cart.', 'woocommerce' ),
						'type'        => 'integer',
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

		if ( isset( $request['quantity'] ) ) {
			$controller->set_cart_item_quantity( $request['key'], $request['quantity'] );
		}

		return rest_ensure_response( $this->schema->get_item_response( $cart ) );
	}
}
