<?php
/**
 * Cart remove coupon route.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Routes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\RestApi\StoreApi\Utilities\CartController;

/**
 * CartRemoveCoupon class.
 */
class CartRemoveCoupon extends AbstractRoute {
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
		return '/cart/remove-coupon';
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
					'code' => [
						'description' => __( 'Unique identifier for the coupon within the cart.', 'woo-gutenberg-products-block' ),
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
		if ( ! wc_coupons_enabled() ) {
			throw new RouteException( 'woocommerce_rest_cart_coupon_disabled', __( 'Coupons are disabled.', 'woo-gutenberg-products-block' ), 404 );
		}

		$controller  = new CartController();
		$cart        = $controller->get_cart_instance();
		$coupon_code = wc_format_coupon_code( $request['code'] );

		if ( ! $controller->has_coupon( $coupon_code ) ) {
			throw new RouteException( 'woocommerce_rest_cart_coupon_invalid_code', __( 'Coupon does not exist in the cart.', 'woo-gutenberg-products-block' ), 404 );
		}

		$cart = $controller->get_cart_instance();
		$cart->remove_coupon( $coupon_code );
		$cart->calculate_totals();

		return rest_ensure_response( $this->schema->get_item_response( $cart ) );
	}
}
