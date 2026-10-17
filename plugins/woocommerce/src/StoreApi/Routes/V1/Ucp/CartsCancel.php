<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

/**
 * UCP `POST /carts/{id}/cancel` route.
 */
class CartsCancel extends AbstractCartRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ucp-carts-cancel';

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
		return '/carts/(?P<id>[\w\-\.]+)/cancel';
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
	 * Cancel a cart.
	 *
	 * The response carries the cart as it stood before the cancellation, which the
	 * spec requires; the cart itself is emptied and the id stops resolving.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$cart_id = $this->route_cart_id( $request );

		$response = $this->cart_response(
			$cart_id,
			array(
				'messages' => array(
					array(
						'type'         => 'info',
						'code'         => 'CANCELED',
						'content'      => __( 'Cart canceled.', 'woocommerce' ),
						'path'         => '$',
						'content_type' => 'plain',
					),
				),
			)
		);

		$this->cart_controller->empty_cart();
		WC()->session->set( self::CANCELLED_SESSION_KEY, true );

		return $response;
	}
}
