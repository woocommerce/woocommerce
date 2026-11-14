<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils;

/**
 * UCP `POST /checkout-sessions` route.
 */
class CheckoutSessions extends AbstractCheckoutRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ucp-checkout-sessions';

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
		return '/checkout-sessions';
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
					'cart_id'          => array(
						'description' => __( 'Cart to promote to a checkout. The checkout keeps its id.', 'woocommerce' ),
						'type'        => 'string',
					),
					'line_items'       => array(
						'description' => __( 'Items to check out, required when no cart_id is given.', 'woocommerce' ),
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
	 * Read the id of the cart to promote from the JSON body.
	 *
	 * Creation has no path id; the body's `cart_id` binds the session instead. Safe for the same
	 * reason the path is: the id is a signed token, so it only reaches a cart this store handed out.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return string The cart id, or '' to start from a fresh session.
	 */
	protected function route_cart_id( \WP_REST_Request $request ): string {
		$cart_id = Utils::json_param( $request, 'cart_id' );

		return is_string( $cart_id ) ? $cart_id : '';
	}

	/**
	 * Create a checkout, from an existing cart or from line items.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$checkout_id = $this->route_cart_id( $request );

		if ( '' !== $checkout_id ) {
			// The cart is the source of truth, so `line_items` is ignored; an update can change it.
			$state = $this->checkout_state();

			if ( null !== $state ) {
				// UCP's idempotent conversion: a cart that already is a checkout is returned as it stands, the body ignored.
				return $this->is_terminal( $state ) ? $this->ucp_conflict() : $this->existing_checkout_response( $request, $checkout_id, $state );
			}

			if ( $this->cart_controller->get_cart_instance()->is_empty() ) {
				return $this->ucp_not_found();
			}
		} else {
			$line_items = Utils::json_param( $request, 'line_items' );

			if ( ! is_array( $line_items ) || empty( $line_items ) ) {
				return $this->ucp_error( 'VALIDATION_ERROR', __( 'line_items is required when no cart_id is given.', 'woocommerce' ), 'recoverable', 400, '$.line_items' );
			}

			$error = $this->update_line_items( $request );

			if ( null !== $error ) {
				return $error;
			}

			$checkout_id = $this->current_cart_id();

			if ( '' === $checkout_id ) {
				return $this->ucp_error( 'TRANSPORT_ERROR', __( 'The checkout session could not be created.', 'woocommerce' ), 'recoverable', 500 );
			}
		}

		$error = $this->update_customer( $request );

		if ( null !== $error ) {
			return $error;
		}

		$state = array( 'created_at' => time() );
		$this->save_checkout_state( $state );

		return $this->checkout_response( $checkout_id, $state, 201 );
	}

	/**
	 * Return a checkout that already exists, warning when the request tried to change it.
	 *
	 * @param \WP_REST_Request     $request Request object.
	 * @param string               $checkout_id Checkout identifier.
	 * @param array<string, mixed> $state Checkout state.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	private function existing_checkout_response( \WP_REST_Request $request, string $checkout_id, array $state ): \WP_REST_Response {
		$response = $this->checkout_response( $checkout_id, $state );

		if ( ! empty( array_diff_key( (array) $request->get_json_params(), array( 'cart_id' => true ) ) ) ) {
			$data               = (array) $response->get_data();
			$data['messages'][] = array(
				'type'         => 'warning',
				'code'         => 'FIELDS_IGNORED',
				'content'      => __( 'This cart already is a checkout session, so it was returned unchanged and the other fields were ignored. Update the checkout session to change it.', 'woocommerce' ),
				'path'         => '$',
				'content_type' => 'plain',
			);
			$response->set_data( $data );
		}

		return $response;
	}
}
