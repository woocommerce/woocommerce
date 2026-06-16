<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Utilities\CustomFeesStore;

/**
 * CartAddFee class.
 *
 * Adds an ad-hoc custom fee to the cart, persisting it in the session via
 * {@see CustomFeesStore} so it survives the per-request fee reset.
 *
 * This route ships in the Store API but is deliberately NOT registered in the
 * public `wc/store/v1` namespace (it is absent from {@see \Automattic\WooCommerce\StoreApi\RoutesController}).
 * Like the agentic routes, it is wired up only by consumers that opt in — POS
 * registers it under `wc/internal/pos/v1`. Consumers must also register
 * {@see CustomFeesStore::apply_to_cart()} on `woocommerce_cart_calculate_fees`
 * so the stored fees are re-applied on every calculation.
 *
 * Negative/zero amounts are rejected: only positive fees are supported.
 *
 * @internal Just for internal use.
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
		return [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'name'    => [
						'description' => __( 'Display name for the fee.', 'woocommerce' ),
						'type'        => 'string',
						'required'    => true,
					],
					'amount'  => [
						'description' => __( 'Fee amount. Must be greater than zero; negative fees are not supported. Re-adding an identical fee is idempotent.', 'woocommerce' ),
						'type'        => 'number',
						'required'    => true,
					],
					'taxable' => [
						'description' => __( 'Whether the fee is taxable. Defaults to false.', 'woocommerce' ),
						'type'        => 'boolean',
						'default'     => false,
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
