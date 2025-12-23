<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

/**
 * Cart class.
 */
class Cart extends AbstractCartRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'cart';

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
		return '/cart';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'context' => $this->get_context_param( [ 'default' => 'view' ] ),
				],
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}

	/**
	 * Handle the request and return a valid response for this endpoint.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$cart_response = $this->schema->get_item_response( $this->cart_controller->get_cart_for_response() );
		/**
		 * Filter the cart response.
		 *
		 * This hook allows the cart response to be changed.
		 *
		 * @param array $cart_response The cart response.
		 * @return array Modified cart response.
		 * @since 10.5.0
		 */
		$cart_response = apply_filters( 'woocommerce_store_api_cart_response', $cart_response );
		return rest_ensure_response( $cart_response );
	}
}
