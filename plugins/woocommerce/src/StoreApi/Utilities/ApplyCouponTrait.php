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

		// Core only throws RouteException, but the coupon hooks apply_coupon()
		// fires (e.g. woocommerce_applied_coupon) are extension surface that can
		// throw WC_REST_Exception; PHPStan can't see through the action, so the
		// catch reads as dead to it.
		try {
			$this->cart_controller->apply_coupon( $coupon_code );
			// @phpstan-ignore-next-line catch.neverThrown
		} catch ( \WC_REST_Exception $e ) {
			// Preserve the extension's own error code and HTTP status instead of
			// letting it fall through to the route's generic 500 handler.
			throw new RouteException( $e->getErrorCode(), $e->getMessage(), $e->getCode() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
	}
}
