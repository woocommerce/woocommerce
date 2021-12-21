<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Blocks\StoreApi\Utilities\DraftOrderTrait;

/**
 * CartUpdateCustomer class.
 *
 * Updates the customer billing and shipping address and returns an updated cart--things such as taxes may be recalculated.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 */
class CartUpdateCustomer extends AbstractCartRoute {
	use DraftOrderTrait;

	/**
	 * Get the namespace for this route.
	 *
	 * @return string
	 */
	public function get_namespace() {
		return 'wc/store';
	}

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/cart/update-customer';
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
					'billing_address'  => [
						'description'       => __( 'Billing address.', 'woo-gutenberg-products-block' ),
						'type'              => 'object',
						'context'           => [ 'view', 'edit' ],
						'properties'        => $this->schema->billing_address_schema->get_properties(),
						'sanitize_callback' => [ $this->schema->billing_address_schema, 'sanitize_callback' ],
						'validate_callback' => [ $this->schema->billing_address_schema, 'validate_callback' ],
					],
					'shipping_address' => [
						'description'       => __( 'Shipping address.', 'woo-gutenberg-products-block' ),
						'type'              => 'object',
						'context'           => [ 'view', 'edit' ],
						'properties'        => $this->schema->shipping_address_schema->get_properties(),
						'sanitize_callback' => [ $this->schema->shipping_address_schema, 'sanitize_callback' ],
						'validate_callback' => [ $this->schema->shipping_address_schema, 'validate_callback' ],
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
		$cart     = $this->cart_controller->get_cart_instance();
		$billing  = $request['billing_address'] ?? [];
		$shipping = $request['shipping_address'] ?? [];
		$customer = wc()->customer;

		if ( ! $cart->needs_shipping() && ! isset( $request['shipping_address'] ) ) {
			// If the cart does not need shipping, shipping address is forced to match billing address unless defined.
			$shipping = $request['billing_address'] ?? $this->get_customer_billing_address( $customer );
		}

		$customer->set_props(
			array(
				'billing_first_name'  => $billing['first_name'] ?? null,
				'billing_last_name'   => $billing['last_name'] ?? null,
				'billing_company'     => $billing['company'] ?? null,
				'billing_address_1'   => $billing['address_1'] ?? null,
				'billing_address_2'   => $billing['address_2'] ?? null,
				'billing_city'        => $billing['city'] ?? null,
				'billing_state'       => $billing['state'] ?? null,
				'billing_postcode'    => $billing['postcode'] ?? null,
				'billing_country'     => $billing['country'] ?? null,
				'billing_phone'       => $billing['phone'] ?? null,
				'billing_email'       => $billing['email'] ?? null,
				'shipping_first_name' => $shipping['first_name'] ?? null,
				'shipping_last_name'  => $shipping['last_name'] ?? null,
				'shipping_company'    => $shipping['company'] ?? null,
				'shipping_address_1'  => $shipping['address_1'] ?? null,
				'shipping_address_2'  => $shipping['address_2'] ?? null,
				'shipping_city'       => $shipping['city'] ?? null,
				'shipping_state'      => $shipping['state'] ?? null,
				'shipping_postcode'   => $shipping['postcode'] ?? null,
				'shipping_country'    => $shipping['country'] ?? null,
				'shipping_phone'      => $shipping['phone'] ?? null,
			)
		);

		/**
		 * Fires when the Checkout Block/Store API updates a customer from the API request data.
		 *
		 * @param \WC_Customer $customer Customer object.
		 * @param \WP_REST_Request $request Full details about the request.
		 */
		do_action( 'woocommerce_blocks_cart_update_customer_from_request', $customer, $request );

		$customer->save();

		$this->calculate_totals();

		return rest_ensure_response( $this->schema->get_item_response( $cart ) );
	}

	/**
	 * Get full customer billing address.
	 *
	 * @param \WC_Customer $customer Customer object.
	 * @return array
	 */
	protected function get_customer_billing_address( \WC_Customer $customer ) {
		return [
			'first_name' => $customer->get_billing_first_name(),
			'last_name'  => $customer->get_billing_last_name(),
			'company'    => $customer->get_billing_company(),
			'address_1'  => $customer->get_billing_address_1(),
			'address_2'  => $customer->get_billing_address_2(),
			'city'       => $customer->get_billing_city(),
			'state'      => $customer->get_billing_state(),
			'postcode'   => $customer->get_billing_postcode(),
			'country'    => $customer->get_billing_country(),
			'phone'      => $customer->get_billing_phone(),
		];
	}
}
