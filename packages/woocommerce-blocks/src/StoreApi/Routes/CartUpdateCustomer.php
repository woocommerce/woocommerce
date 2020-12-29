<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Blocks\StoreApi\Utilities\CartController;
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
	 * Billing address schema instance.
	 *
	 * @var BillingAddressSchema
	 */
	protected $billing_address_schema;

	/**
	 * Shipping address schema instance.
	 *
	 * @var ShippingAddressSchema
	 */
	protected $shipping_address_schema;

	/**
	 * Constructor.
	 *
	 * @param CartSchema            $schema Schema class for this route.
	 * @param ShippingAddressSchema $shipping_address_schema Billing address schema class for this route.
	 * @param BillingAddressSchema  $billing_address_schema Billing address schema class for this route.
	 */
	public function __construct( CartSchema $schema, ShippingAddressSchema $shipping_address_schema, BillingAddressSchema $billing_address_schema ) {
		$this->schema                  = $schema;
		$this->shipping_address_schema = $shipping_address_schema;
		$this->billing_address_schema  = $billing_address_schema;
	}

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
						'description' => __( 'Billing address.', 'woocommerce' ),
						'type'        => 'object',
						'context'     => [ 'view', 'edit' ],
						'properties'  => $this->billing_address_schema->get_properties(),
					],
					'shipping_address' => [
						'description' => __( 'Shipping address.', 'woocommerce' ),
						'type'        => 'object',
						'context'     => [ 'view', 'edit' ],
						'properties'  => $this->shipping_address_schema->get_properties(),
					],
				],
			],
			'schema' => [ $this->schema, 'get_public_item_schema' ],
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
		$controller = new CartController();
		$cart       = $controller->get_cart_instance();
		$billing    = isset( $request['billing_address'] ) ? $this->prepare_address_fields( $request['billing_address'], wc()->countries->get_allowed_countries() ) : [];
		$shipping   = isset( $request['shipping_address'] ) ? $this->prepare_address_fields( $request['shipping_address'], wc()->countries->get_shipping_countries() ) : [];

		if ( ! $cart->needs_shipping() ) {
			$shipping = $billing;
		}

		wc()->customer->set_props(
			array(
				'billing_first_name'  => isset( $billing['first_name'] ) ? $billing['first_name'] : null,
				'billing_last_name'   => isset( $billing['last_name'] ) ? $billing['last_name'] : null,
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

	/**
	 * Format provided address fields.
	 *
	 * @throws RouteException Thrown on error.
	 * @param array $address Address fields.
	 * @param array $allowed_countries Countries that must be used.
	 * @return array
	 */
	protected function prepare_address_fields( $address, $allowed_countries ) {
		// Addresses require a country, otherwise they are not valid. This returns an empty address. There is no address
		// validation in this case because we do not want to block updates of other data such as email address.
		if ( empty( $address['country'] ) ) {
			return [];
		}

		$address['country'] = wc_strtoupper( $address['country'] );

		if (
			is_array( $allowed_countries ) &&
			count( $allowed_countries ) > 0 &&
			! array_key_exists( $address['country'], $allowed_countries )
		) {
			throw new RouteException(
				'woocommerce_rest_cart_invalid_country',
				sprintf(
					/* translators: 1: valid country codes */
					__( 'Address country is not valid. Please provide one of the following: %s', 'woocommerce' ),
					implode( ', ', array_keys( $allowed_countries ) )
				),
				400
			);
		}

		$address['postcode'] = isset( $address['postcode'] ) ? wc_format_postcode( $address['postcode'], $address['country'] ) : null;

		if ( ! empty( $address['state'] ) ) {
			$valid_states = wc()->countries->get_states( $address['country'] );

			if ( is_array( $valid_states ) && count( $valid_states ) > 0 ) {
				$valid_state_values = array_map( 'wc_strtoupper', array_flip( array_map( 'wc_strtoupper', $valid_states ) ) );
				$address['state']   = wc_strtoupper( $address['state'] );

				if ( isset( $valid_state_values[ $address['state'] ] ) ) {
					// With this part we consider state value to be valid as well,
					// convert it to the state key for the valid_states check below.
					$address['state'] = $valid_state_values[ $address['state'] ];
				}

				if ( ! in_array( $address['state'], $valid_state_values, true ) ) {
					throw new RouteException(
						'woocommerce_rest_cart_invalid_state',
						sprintf(
							/* translators: 1: valid states */
							__( 'Address state is not valid. Please enter one of the following: %s', 'woocommerce' ),
							implode( ', ', $valid_states )
						),
						400
					);
				}
			}
		}

		return $address;
	}
}
