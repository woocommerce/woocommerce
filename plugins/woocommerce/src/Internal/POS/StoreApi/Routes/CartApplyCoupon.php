<?php
/**
 * CartApplyCoupon class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;

/**
 * POS cart/apply-coupon route.
 *
 * Adapter-style: extends the abstract {@see AbstractCartRoute} rather than the
 * concrete `StoreApi\Routes\V1\CartApplyCoupon`, and owns its own endpoint
 * shape. Coupon validation (usage limits, per-customer limits, product
 * restrictions, …) is delegated to the shared {@see \Automattic\WooCommerce\StoreApi\Utilities\CartController},
 * so it runs unchanged; only the request/response surface and the POS
 * auth/session seams live here.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CartApplyCoupon extends AbstractCartRoute {

	use PosRouteTrait;

	/**
	 * Capability required for any POS request. Override per-route if needed.
	 */
	protected const REQUIRED_CAPABILITY = 'manage_woocommerce';

	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'cart-apply-coupon';

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
				'args'                => array_merge(
					array(
						'code' => array(
							'description' => __( 'Unique identifier for the coupon within the cart.', 'woocommerce' ),
							'type'        => 'string',
						),
					),
					$this->pos_cart_token_arg()
				),
			),
			'schema'      => array( $this->schema, 'get_public_item_schema' ),
			'allow_batch' => array( 'v1' => true ),
		);
	}

	/**
	 * Handle the request and return a valid response for this endpoint.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		if ( ! wc_coupons_enabled() ) {
			throw new RouteException( 'woocommerce_rest_cart_coupon_disabled', esc_html__( 'Coupons are disabled.', 'woocommerce' ), 404 );
		}

		$coupon_code = wc_format_coupon_code( wp_unslash( $request['code'] ) );

		try {
			$this->cart_controller->apply_coupon( $coupon_code );
		} catch ( \WC_REST_Exception $e ) {
			throw new RouteException( $e->getErrorCode(), $e->getMessage(), $e->getCode() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return rest_ensure_response( $this->schema->get_item_response( $this->cart_controller->get_cart_for_response() ) );
	}
}
