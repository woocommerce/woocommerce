<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Utilities\CustomFeesStore;

/**
 * CartRemoveFee class.
 *
 * Removes a custom fee previously added via {@see CartAddFee}, identified by the
 * `id` returned in the cart's `fees` response. Like its sibling, this route
 * ships in the Store API but is registered only by opt-in consumers (POS, under
 * `wc/internal/pos/v1`), never in the public `wc/store/v1` namespace.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CartRemoveFee extends AbstractCartRoute {

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
		return [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'description' => __( 'The id of the fee to remove, as returned in the cart fees response.', 'woocommerce' ),
						'type'        => 'string',
						'required'    => true,
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
		$removed = ( new CustomFeesStore( WC()->session ) )->remove( (string) $request['id'] );

		if ( ! $removed ) {
			throw new RouteException( 'woocommerce_rest_cart_fee_not_found', esc_html__( 'No matching fee was found on the cart.', 'woocommerce' ), 409 );
		}

		// Recalculate so the removed fee no longer appears in the cart response.
		$this->cart_controller->calculate_totals();

		return rest_ensure_response( $this->schema->get_item_response( $this->cart_controller->get_cart_for_response() ) );
	}
}
