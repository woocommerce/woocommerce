<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\BillingAddressSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\ShippingAddressSchema;

/**
 * CartUpdateCustomer class.
 *
 * Updates the customer billing and shipping address and returns an updated cart--things such as taxes may be recalculated.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 */
class CartUpdateCustomer extends AbstractCartRoute {
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
		$billing  = isset( $request['billing_address'] ) ? $request['billing_address'] : [];
		$shipping = isset( $request['shipping_address'] ) ? $request['shipping_address'] : [];

		// If the cart does not need shipping, shipping address is forced to match billing address unless defined.
		if ( ! $cart->needs_shipping() && ! isset( $request['shipping_address'] ) ) {
			$shipping = isset( $request['billing_address'] ) ? $request['billing_address'] : [
				'first_name' => wc()->customer->get_billing_first_name(),
				'last_name'  => wc()->customer->get_billing_last_name(),
				'company'    => wc()->customer->get_billing_company(),
				'address_1'  => wc()->customer->get_billing_address_1(),
				'address_2'  => wc()->customer->get_billing_address_2(),
				'city'       => wc()->customer->get_billing_city(),
				'state'      => wc()->customer->get_billing_state(),
				'postcode'   => wc()->customer->get_billing_postcode(),
				'country'    => wc()->customer->get_billing_country(),
			];
		}

		wc()->customer->set_props(
			array(
				'billing_first_name'  => isset( $billing['first_name'] ) ? $billing['first_name'] : null,
				'billing_last_name'   => isset( $billing['last_name'] ) ? $billing['last_name'] : null,
				'billing_company'     => isset( $billing['company'] ) ? $billing['company'] : null,
				'billing_address_1'   => isset( $billing['address_1'] ) ? $billing['address_1'] : null,
				'billing_address_2'   => isset( $billing['address_2'] ) ? $billing['address_2'] : null,
				'billing_city'        => isset( $billing['city'] ) ? $billing['city'] : null,
				'billing_state'       => isset( $billing['state'] ) ? $billing['state'] : null,
				'billing_postcode'    => isset( $billing['postcode'] ) ? $billing['postcode'] : null,
				'billing_country'     => isset( $billing['country'] ) ? $billing['country'] : null,
				'billing_email'       => isset( $request['billing_address'], $request['billing_address']['email'] ) ? $request['billing_address']['email'] : null,
				'billing_phone'       => isset( $request['billing_address'], $request['billing_address']['phone'] ) ? $request['billing_address']['phone'] : null,
				'shipping_first_name' => isset( $shipping['first_name'] ) ? $shipping['first_name'] : null,
				'shipping_last_name'  => isset( $shipping['last_name'] ) ? $shipping['last_name'] : null,
				'shipping_company'    => isset( $shipping['company'] ) ? $shipping['company'] : null,
				'shipping_address_1'  => isset( $shipping['address_1'] ) ? $shipping['address_1'] : null,
				'shipping_address_2'  => isset( $shipping['address_2'] ) ? $shipping['address_2'] : null,
				'shipping_city'       => isset( $shipping['city'] ) ? $shipping['city'] : null,
				'shipping_state'      => isset( $shipping['state'] ) ? $shipping['state'] : null,
				'shipping_postcode'   => isset( $shipping['postcode'] ) ? $shipping['postcode'] : null,
				'shipping_country'    => isset( $shipping['country'] ) ? $shipping['country'] : null,
			)
		);
		wc()->customer->save();

		$cart->calculate_shipping();
		$cart->calculate_totals();

		return rest_ensure_response( $this->schema->get_item_response( $cart ) );
	}
}
