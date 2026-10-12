<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

/**
 * UCP `GET /carts/{id}` and `PUT /carts/{id}` route.
 */
class CartsById extends AbstractCartRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ucp-carts-by-id';

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
		return '/carts/(?P<id>[\w\-\.]+)';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * PUT only, as the cart REST binding specifies. PATCH is deliberately not accepted:
	 * `line_items` is the complete desired state, so a partial payload would delete
	 * every line it left out.
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
					'line_items' => array(
						'description' => __( 'The complete desired contents of the cart.', 'woocommerce' ),
						'type'        => 'array',
					),
				),
			),
			'schema' => array( $this->schema, 'get_public_item_schema' ),
		);
	}

	/**
	 * Read a cart.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		return $this->cart_response( $this->route_cart_id( $request ) );
	}

	/**
	 * Replace the contents of a cart.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_update_response( \WP_REST_Request $request ) {
		$line_items = $request->get_param( 'line_items' );

		// `line_items` is the whole desired state, so omitting it is not the same as
		// sending an empty list: it leaves the cart alone.
		if ( null !== $line_items ) {
			$desired = $this->parse_line_items( $line_items );

			if ( $desired instanceof \WP_REST_Response ) {
				return $desired;
			}

			$this->apply_line_items( $desired );
		}

		return $this->cart_response( $this->route_cart_id( $request ) );
	}
}
