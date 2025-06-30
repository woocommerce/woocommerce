<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * CartRemoveCoupon class.
 */
class CartRemoveCoupon extends AbstractCartRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'cart-remove-coupon';

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
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'code' => [
						'description' => __( 'Unique identifier for the coupon within the cart.', 'woocommerce' ),
						'type'        => 'string',
					],
				],
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
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
			throw new RouteException( 'woocommerce_rest_cart_coupon_disabled', esc_html__( 'Coupons are disabled.', 'woocommerce' ), 404 );
		}

		$cart        = $this->cart_controller->get_cart_instance();
		$coupon_code = wc_format_coupon_code( $request['code'] );
		$coupon      = new \WC_Coupon( $coupon_code );
		$discounts   = new \WC_Discounts( $cart );

		if ( ! wc_is_same_coupon( $coupon->get_code(), $coupon_code ) || is_wp_error( $discounts->is_coupon_valid( $coupon ) ) ) {
			throw new RouteException( 'woocommerce_rest_cart_coupon_error', esc_html__( 'Invalid coupon code.', 'woocommerce' ), 400 );
		}

		if ( ! $this->cart_controller->has_coupon( $coupon_code ) ) {
			throw new RouteException( 'woocommerce_rest_cart_coupon_invalid_code', esc_html__( 'Coupon cannot be removed because it is not already applied to the cart.', 'woocommerce' ), 409 );
		}

		$cart = $this->cart_controller->get_cart_instance();
		$cart->remove_coupon( $coupon_code );

		return rest_ensure_response( $this->schema->get_item_response( $cart ) );
	}
}
