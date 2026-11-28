<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils;

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
					'discounts'  => array(
						'description' => __( 'Discount codes to apply, replacing any applied before.', 'woocommerce' ),
						'type'        => 'object',
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
	 * that addresses this cart from now on. `continue_url` carries a link token for the
	 * same session instead, which only lets the buyer's browser copy the cart.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$codes = $this->parse_discount_codes( $request );

		if ( $codes instanceof \WP_REST_Response ) {
			return $codes;
		}

		$line_items = Utils::json_param( $request, 'line_items' );

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

		return $this->cart_response( $cart_id, $this->apply_discount_codes( $codes ) );
	}
}
