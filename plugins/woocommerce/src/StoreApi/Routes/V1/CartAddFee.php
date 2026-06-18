<?php
declare( strict_types=1 );
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Utilities\CustomFeesStore;

/**
 * CartAddFee class.
 *
 * Adds an ad-hoc custom fee to the cart, persisting it in the session via
 * {@see CustomFeesStore} so it survives WooCommerce's per-request fee reset. POS
 * lets an operator charge an arbitrary amount (e.g. for an off-catalogue item);
 * unlike items, coupons and checkout there is no existing Store API fee-write
 * endpoint to reuse — fees normally only enter a cart server-side via the
 * `woocommerce_cart_calculate_fees` hook — so this is the one route the
 * shared-routes POS approach adds rather than reuses.
 *
 * It lives in the Store API namespace but is registered only when the
 * `point_of_sale` feature is enabled (see
 * {@see \Automattic\WooCommerce\StoreApi\RoutesController::register_all_routes})
 * and is gated to `manage_woocommerce`, so it is never exposed to the public
 * storefront. {@see \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CustomFeesPolicy}
 * re-applies the stored fees on every cart calculation.
 *
 * Negative/zero amounts are rejected: only positive fees are supported.
 *
 * @since 10.9.0
 */
class CartAddFee extends AbstractCartRoute {

	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'cart-add-fee';

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
		return '/cart/add-fee';
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
				'permission_callback' => array( $this, 'check_manager_permission' ),
				'args'                => array(
					'name'    => array(
						'description' => __( 'Display name for the fee.', 'woocommerce' ),
						'type'        => 'string',
						'required'    => true,
					),
					'amount'  => array(
						'description' => __( 'Fee amount. Must be greater than zero; negative fees are not supported. Re-adding an identical fee is idempotent.', 'woocommerce' ),
						'type'        => 'number',
						'required'    => true,
					),
					'taxable' => array(
						'description' => __( 'Whether the fee is taxable. Defaults to false.', 'woocommerce' ),
						'type'        => 'boolean',
						'default'     => false,
					),
				),
			),
			'schema'      => array( $this->schema, 'get_public_item_schema' ),
			'allow_batch' => array( 'v1' => true ),
		);
	}

	/**
	 * Only store managers may add ad-hoc fees. This keeps the route effectively
	 * POS-only even though it sits in the shared Store API namespace.
	 *
	 * @return bool|\WP_Error
	 */
	public function check_manager_permission() {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		return new \WP_Error(
			'woocommerce_rest_cart_add_fee_forbidden',
			__( 'Sorry, you are not allowed to add fees to the cart.', 'woocommerce' ),
			array( 'status' => rest_authorization_required_code() )
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
		$name = trim( (string) $request['name'] );

		if ( '' === $name ) {
			throw new RouteException( 'woocommerce_rest_cart_fee_invalid_name', esc_html__( 'A fee name is required.', 'woocommerce' ), 400 );
		}

		$amount = (float) $request['amount'];

		if ( $amount <= 0 ) {
			throw new RouteException( 'woocommerce_rest_cart_fee_invalid_amount', esc_html__( 'The fee amount must be greater than zero.', 'woocommerce' ), 400 );
		}

		( new CustomFeesStore( WC()->session ) )->add( $name, $amount, (bool) $request['taxable'] );

		// Recalculate so the calculate_fees callback re-applies the stored fees
		// (including the one just added) before the cart response is built.
		$this->cart_controller->calculate_totals();

		return rest_ensure_response( $this->schema->get_item_response( $this->cart_controller->get_cart_for_response() ) );
	}
}
