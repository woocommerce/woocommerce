<?php
/**
 * CartApplyCoupon class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;

/**
 * POS cart/apply-coupon route.
 *
 * A pure passthrough to the shared coupon machinery: the same
 * CartController::apply_coupon() pipeline (and every validation rule and
 * extension hook in it) as the web route, with only the POS seams — the
 * capability check and nonce opt-out from {@see PosRouteTrait} — differing.
 * No POS-specific coupon behaviour: validation, usage limits and restrictions
 * resolve exactly as they would for a web guest. The response is the standard
 * Store API cart, recalculated with the coupon applied.
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class CartApplyCoupon extends AbstractCartRoute {

	use PosRouteTrait;

	/**
	 * Capability required to call this route.
	 */
	protected const REQUIRED_CAPABILITY = 'manage_woocommerce';

	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'pos-cart-apply-coupon';

	/**
	 * The route's schema — the shared Store API cart schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = CartSchema::IDENTIFIER;

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
		return '/cart/apply-coupon';
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
				'args'                => array(
					'code' => array(
						'description' => __( 'The coupon code to apply to the cart.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'required'    => true,
					),
				),
			),
			'schema' => array( $this->schema, 'get_public_item_schema' ),
		);
	}

	/**
	 * Handle the request and return a valid response for this endpoint.
	 *
	 * Mirrors the web cart/apply-coupon handler.
	 *
	 * @throws RouteException When coupons are disabled or the coupon cannot be applied.
	 * @param \WP_REST_Request $request Request object.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		if ( ! wc_coupons_enabled() ) {
			throw new RouteException( 'woocommerce_rest_cart_coupon_disabled', esc_html__( 'Coupons are disabled.', 'woocommerce' ), 404 );
		}

		$coupon_code = wc_format_coupon_code( wp_unslash( $request['code'] ) );

		// apply_coupon() throws RouteException on failure, which the route
		// dispatch converts to the error response (the web route's extra
		// WC_REST_Exception catch is dead code per static analysis).
		$this->cart_controller->apply_coupon( $coupon_code );

		$response = rest_ensure_response( $this->schema->get_item_response( $this->cart_controller->get_cart_for_response() ) );
		$response->set_status( 201 );

		return $response;
	}
}
