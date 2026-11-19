<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

use Automattic\WooCommerce\StoreApi\Utilities\Ucp\CheckoutMapper;

/**
 * UCP `POST /checkout-sessions/{id}/cancel` route.
 */
class CheckoutSessionsCancel extends AbstractCheckoutRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ucp-checkout-sessions-cancel';

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
		return '/checkout-sessions/(?P<id>[\w\-\.]+)/cancel';
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
			),
			'schema' => array( $this->schema, 'get_public_item_schema' ),
		);
	}

	/**
	 * Cancel a checkout.
	 *
	 * The cart is kept, so the checkout goes on answering with its canceled payload. An order
	 * handed to the buyer and still unpaid is cancelled too, so it can no longer be paid.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$state = $this->open_checkout( true );

		if ( $state instanceof \WP_REST_Response ) {
			return $state;
		}

		$state = $this->cancel_handed_off_order( $state, __( 'UCP checkout session canceled.', 'woocommerce' ) );

		if ( ! empty( $state['order_id'] ) ) {
			return $this->refuse_kept_order();
		}

		$state['status'] = CheckoutMapper::STATUS_CANCELED;
		$this->save_checkout_state( $state );

		return $this->checkout_response( $this->route_cart_id( $request ), $state );
	}
}
