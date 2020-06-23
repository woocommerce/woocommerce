<?php
/**
 * Cart add item route.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\StoreApi\Routes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\StoreApi\Utilities\CartController;

/**
 * CartAddItem class.
 */
class CartAddItem extends AbstractCartRoute {
	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/cart/add-item';
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
					'id'        => [
						'description' => __( 'The cart item product or variation ID.', 'woocommerce' ),
						'type'        => 'integer',
						'context'     => [ 'view', 'edit' ],
						'arg_options' => [
							'sanitize_callback' => 'absint',
						],
					],
					'quantity'  => [
						'description' => __( 'Quantity of this item in the cart.', 'woocommerce' ),
						'type'        => 'integer',
						'context'     => [ 'view', 'edit' ],
						'arg_options' => [
							'sanitize_callback' => 'wc_stock_amount',
						],
					],
					'variation' => [
						'description' => __( 'Chosen attributes (for variations).', 'woocommerce' ),
						'type'        => 'array',
						'context'     => [ 'view', 'edit' ],
						'items'       => [
							'type'       => 'object',
							'properties' => [
								'attribute' => [
									'description' => __( 'Variation attribute name.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => [ 'view', 'edit' ],
								],
								'value'     => [
									'description' => __( 'Variation attribute value.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => [ 'view', 'edit' ],
								],
							],
						],
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
		// Do not allow key to be specified during creation.
		if ( ! empty( $request['key'] ) ) {
			throw new RouteException( 'woocommerce_rest_cart_item_exists', __( 'Cannot create an existing cart item.', 'woocommerce' ), 400 );
		}

		$controller = new CartController();
		$cart       = $controller->get_cart_instance();
		$result     = $controller->add_to_cart(
			[
				'id'        => $request['id'],
				'quantity'  => $request['quantity'],
				'variation' => $request['variation'],
			]
		);

		$response = rest_ensure_response( $this->schema->get_item_response( $cart ) );
		$response->set_status( 201 );
		return $response;
	}
}
