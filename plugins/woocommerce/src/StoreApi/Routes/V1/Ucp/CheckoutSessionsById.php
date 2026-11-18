<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

/**
 * UCP `GET /checkout-sessions/{id}` and `PUT /checkout-sessions/{id}` route.
 */
class CheckoutSessionsById extends AbstractCheckoutRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ucp-checkout-sessions-by-id';

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
		return '/checkout-sessions/(?P<id>[\w\-\.]+)';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * PUT only, as for carts: `line_items` is the complete desired state.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'line_items'       => array(
						'description' => __( 'The complete desired contents of the checkout.', 'woocommerce' ),
						'type'        => 'array',
					),
					'buyer'            => array(
						'description' => __( 'Buyer contact details.', 'woocommerce' ),
						'type'        => 'object',
					),
					'billing_address'  => array(
						'description' => __( 'Billing postal address.', 'woocommerce' ),
						'type'        => 'object',
					),
					'shipping_address' => array(
						'description' => __( 'Shipping postal address.', 'woocommerce' ),
						'type'        => 'object',
					),
				),
			),
			'schema' => array( $this->schema, 'get_public_item_schema' ),
		);
	}

	/**
	 * Read a checkout.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$state = $this->open_checkout( false );

		return $state instanceof \WP_REST_Response ? $state : $this->checkout_response( $this->route_cart_id( $request ), $state );
	}

	/**
	 * Update a checkout.
	 *
	 * A change drops an earlier escalation and cancels its unpaid order, which no longer
	 * matches the cart, so the status is derived again and `complete` hands off afresh.
	 * That holds for a request refused part-way too, while one that changes nothing keeps the hand-off.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_update_response( \WP_REST_Request $request ) {
		$state = $this->open_checkout( true );

		if ( $state instanceof \WP_REST_Response ) {
			return $state;
		}

		$error = $this->update_line_items( $request ) ?? $this->update_customer( $request );

		if ( ( $state['fingerprint'] ?? '' ) !== $this->hand_off_fingerprint() ) {
			$state = $this->cancel_handed_off_order( $state, __( 'UCP checkout session changed after payment was handed off.', 'woocommerce' ) );
		}

		// Read again, so an order paid while this request ran is reported as it now stands.
		return $error ?? $this->checkout_response( $this->route_cart_id( $request ), $this->checkout_state() ?? $state );
	}
}
