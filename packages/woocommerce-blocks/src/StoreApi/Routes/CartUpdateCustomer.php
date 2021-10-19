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
						'description'       => __( 'Billing address.', 'woocommerce' ),
						'type'              => 'object',
						'context'           => [ 'view', 'edit' ],
						'properties'        => $this->schema->billing_address_schema->get_properties(),
						'sanitize_callback' => [ $this->schema->billing_address_schema, 'sanitize_callback' ],
						'validate_callback' => [ $this->schema->billing_address_schema, 'validate_callback' ],
					],
					'shipping_address' => [
						'description'       => __( 'Shipping address.', 'woocommerce' ),
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
				'phone'      => wc()->customer->get_billing_phone(),
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
				'billing_phone'       => isset( $billing['phone'] ) ? $billing['phone'] : null,
				'billing_email'       => isset( $request['billing_address'], $request['billing_address']['email'] ) ? $request['billing_address']['email'] : null,
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

		$shipping_phone_value = isset( $shipping['phone'] ) ? $shipping['phone'] : null;

		// @todo Remove custom shipping_phone handling (requires WC 5.6+)
		if ( is_callable( [ wc()->customer, 'set_shipping_phone' ] ) ) {
			wc()->customer->set_shipping_phone( $shipping_phone_value );
		} else {
			wc()->customer->update_meta_data( 'shipping_phone', $shipping_phone_value );
		}

		wc()->customer->save();

		$this->calculate_totals();
		$this->maybe_update_order();

		return rest_ensure_response( $this->schema->get_item_response( $cart ) );
	}

	/**
	 * If there is a draft order, update customer data there also.
	 *
	 * @return void
	 */
	protected function maybe_update_order() {
		$draft_order_id = wc()->session->get( 'store_api_draft_order', 0 );
		$draft_order    = $draft_order_id ? wc_get_order( $draft_order_id ) : false;

		if ( ! $draft_order ) {
			return;
		}

		$draft_order->set_props(
			[
				'billing_first_name'  => wc()->customer->get_billing_first_name(),
				'billing_last_name'   => wc()->customer->get_billing_last_name(),
				'billing_company'     => wc()->customer->get_billing_company(),
				'billing_address_1'   => wc()->customer->get_billing_address_1(),
				'billing_address_2'   => wc()->customer->get_billing_address_2(),
				'billing_city'        => wc()->customer->get_billing_city(),
				'billing_state'       => wc()->customer->get_billing_state(),
				'billing_postcode'    => wc()->customer->get_billing_postcode(),
				'billing_country'     => wc()->customer->get_billing_country(),
				'billing_email'       => wc()->customer->get_billing_email(),
				'billing_phone'       => wc()->customer->get_billing_phone(),
				'shipping_first_name' => wc()->customer->get_shipping_first_name(),
				'shipping_last_name'  => wc()->customer->get_shipping_last_name(),
				'shipping_company'    => wc()->customer->get_shipping_company(),
				'shipping_address_1'  => wc()->customer->get_shipping_address_1(),
				'shipping_address_2'  => wc()->customer->get_shipping_address_2(),
				'shipping_city'       => wc()->customer->get_shipping_city(),
				'shipping_state'      => wc()->customer->get_shipping_state(),
				'shipping_postcode'   => wc()->customer->get_shipping_postcode(),
				'shipping_country'    => wc()->customer->get_shipping_country(),
			]
		);

		$shipping_phone_value = is_callable( [ wc()->customer, 'get_shipping_phone' ] ) ? wc()->customer->get_shipping_phone() : wc()->customer->get_meta( 'shipping_phone', true );

		if ( is_callable( [ $draft_order, 'set_shipping_phone' ] ) ) {
			$draft_order->set_shipping_phone( $shipping_phone_value );
		} else {
			$draft_order->update_meta_data( '_shipping_phone', $shipping_phone_value );
		}

		$draft_order->save();
	}
}
