<?php
/**
 * CartRemoveFee class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Automattic\WooCommerce\StoreApi\Utilities\CustomFeesStore;

/**
 * POS cart/remove-fee route.
 *
 * Removes a custom fee previously added via {@see CartAddFee}, identified by the
 * `key` returned in the cart's `fees` response. POS-owned and extends
 * {@see AbstractCartRoute} directly (there is no public Store API fee route to
 * subclass); the reusable persistence lives in the shared {@see CustomFeesStore}.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CartRemoveFee extends AbstractCartRoute {

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
	const IDENTIFIER = 'cart-remove-fee';

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
		return '/cart/remove-fee';
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
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'description' => __( 'The id of the fee to remove, as returned in the cart fees response (the `key` field).', 'woocommerce' ),
						'type'        => 'string',
						'required'    => true,
					),
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
		$removed = ( new CustomFeesStore( WC()->session ) )->remove( (string) $request['id'] );

		if ( ! $removed ) {
			throw new RouteException( 'woocommerce_rest_cart_fee_not_found', esc_html__( 'No matching fee was found on the cart.', 'woocommerce' ), 409 );
		}

		// Recalculate so the removed fee no longer appears in the cart response.
		$this->cart_controller->calculate_totals();

		return rest_ensure_response( $this->schema->get_item_response( $this->cart_controller->get_cart_for_response() ) );
	}
}
