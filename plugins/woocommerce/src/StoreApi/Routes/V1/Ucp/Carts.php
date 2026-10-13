<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

/**
 * UCP `POST /carts` route.
 */
class Carts extends AbstractCartRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ucp-carts';

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
		return '/carts';
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
					'line_items' => array(
						'description' => __( 'Items to put in the new cart.', 'woocommerce' ),
						'type'        => 'array',
					),
				),
			),
			'schema' => array( $this->schema, 'get_public_item_schema' ),
		);
	}

	/**
	 * Create a cart.
	 *
	 * The id is minted from the session the cart was loaded into, so it is the token
	 * that addresses this cart from now on — and, inside `continue_url`, the token
	 * that lets the buyer's browser pick the same cart up.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$this->release_cancelled_session();

		$line_items = $request->get_param( 'line_items' );

		if ( null !== $line_items ) {
			$desired = $this->parse_line_items( $line_items );

			if ( $desired instanceof \WP_REST_Response ) {
				return $desired;
			}

			$error = $this->apply_line_items( $desired );

			if ( null !== $error ) {
				return $error;
			}
		}

		$cart_id = $this->current_cart_id();

		if ( '' === $cart_id ) {
			return $this->ucp_error(
				'TRANSPORT_ERROR',
				__( 'The cart session could not be created.', 'woocommerce' ),
				'recoverable',
				500
			);
		}

		return $this->cart_response( $cart_id );
	}
}
