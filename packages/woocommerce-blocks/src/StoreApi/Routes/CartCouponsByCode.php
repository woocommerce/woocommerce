<?php
/**
 * Cart Coupons route.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\StoreApi\Routes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\StoreApi\Utilities\CartController;

/**
 * CartCouponsByCode class.
 */
class CartCouponsByCode extends AbstractRoute {
	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/cart/coupons/(?P<code>[\w-]+)';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			'args'   => [
				'code' => [
					'description' => __( 'Unique identifier for the coupon within the cart.', 'woocommerce' ),
					'type'        => 'string',
				],
			],
			[
				'methods'  => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_response' ],
				'args'     => [
					'context' => $this->get_context_param( [ 'default' => 'view' ] ),
				],
			],
			[
				'methods'  => \WP_REST_Server::DELETABLE,
				'callback' => [ $this, 'get_response' ],
			],
			'schema' => [ $this->schema, 'get_public_item_schema' ],
		];
	}

	/**
	 * Get a single cart coupon.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$controller = new CartController();

		if ( ! $controller->has_coupon( $request['code'] ) ) {
			throw new RouteException( 'woocommerce_rest_cart_coupon_invalid_code', __( 'Coupon does not exist in the cart.', 'woocommerce' ), 404 );
		}

		return $this->prepare_item_for_response( $request['code'], $request );
	}

	/**
	 * Delete a single cart coupon.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_delete_response( \WP_REST_Request $request ) {
		$controller = new CartController();

		if ( ! $controller->has_coupon( $request['code'] ) ) {
			throw new RouteException( 'woocommerce_rest_cart_coupon_invalid_code', __( 'Coupon does not exist in the cart.', 'woocommerce' ), 404 );
		}

		$cart = $controller->get_cart_instance();
		$cart->remove_coupon( $request['code'] );
		$cart->calculate_totals();

		return new \WP_REST_Response( null, 204 );
	}
}
