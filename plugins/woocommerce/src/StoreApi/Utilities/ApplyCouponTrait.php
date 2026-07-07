<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * The coupon-application pipeline of the web cart/apply-coupon route,
 * extracted so alternative routes (e.g. the internal POS cart/apply-coupon)
 * compose the identical code path instead of duplicating it.
 * Behaviour-preserving move from Routes\V1\CartApplyCoupon::get_route_post_response();
 * the response shaping stays with each route.
 *
 * Consumers must provide $cart_controller (AbstractCartRoute does).
 *
 * @internal Just for internal use.
 */
trait ApplyCouponTrait {

	/**
	 * Apply the coupon code from the request to the cart.
	 *
	 * @throws RouteException When coupons are disabled or the coupon cannot be applied.
	 * @param \WP_REST_Request $request Request object with a top-level `code` param.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return void
	 */
	protected function apply_coupon_from_request( \WP_REST_Request $request ) {
		if ( ! wc_coupons_enabled() ) {
			throw new RouteException( 'woocommerce_rest_cart_coupon_disabled', esc_html__( 'Coupons are disabled.', 'woocommerce' ), 404 );
		}

		$coupon_code = wc_format_coupon_code( wp_unslash( $request['code'] ) );

		// The web route's WC_REST_Exception catch is not carried over: static
		// analysis proves it dead — apply_coupon() throws RouteException.
		$this->cart_controller->apply_coupon( $coupon_code );
	}
}
