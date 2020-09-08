<?php
/**
 * Cart update shipping route.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\StoreApi\Routes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\StoreApi\Utilities\CartController;

/**
 * CartUpdateShipping class.
 */
class CartUpdateShipping extends AbstractCartRoute {
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
		return '/cart/update-shipping';
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
					'first_name' => [
						'description'       => __( 'Customer first name.', 'woocommerce' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'wc_clean',
						'validate_callback' => 'rest_validate_request_arg',

					],
					'last_name'  => [
						'description'       => __( 'Customer last name.', 'woocommerce' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'wc_clean',
						'validate_callback' => 'rest_validate_request_arg',

					],
					'address_1'  => [
						'description'       => __( 'First line of the address being shipped to.', 'woocommerce' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'wc_clean',
						'validate_callback' => 'rest_validate_request_arg',
					],
					'address_2'  => [
						'description'       => __( 'Second line of the address being shipped to.', 'woocommerce' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'wc_clean',
						'validate_callback' => 'rest_validate_request_arg',
					],
					'city'       => [
						'description'       => __( 'City of the address being shipped to.', 'woocommerce' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'wc_clean',
						'validate_callback' => 'rest_validate_request_arg',
					],
					'state'      => [
						'description'       => __( 'ISO code, or name, for the state, province, or district of the address being shipped to.', 'woocommerce' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'wc_clean',
						'validate_callback' => 'rest_validate_request_arg',
					],
					'postcode'   => [
						'description'       => __( 'Zip or Postcode of the address being shipped to.', 'woocommerce' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'wc_clean',
						'validate_callback' => 'rest_validate_request_arg',
					],
					'country'    => [
						'description'       => __( 'ISO code for the country of the address being shipped to.', 'woocommerce' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'wc_clean',
						'validate_callback' => 'rest_validate_request_arg',
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
		if ( ! wc_shipping_enabled() ) {
			throw new RouteException( 'woocommerce_rest_shipping_disabled', __( 'Shipping is disabled.', 'woocommerce' ), 404 );
		}

		$controller = new CartController();
		$cart       = $controller->get_cart_instance();
		$request    = $this->validate_shipping_address( $request );

		// Update customer session.
		wc()->customer->set_props(
			array(
				'shipping_first_name' => isset( $request['first_name'] ) ? $request['first_name'] : null,
				'shipping_last_name'  => isset( $request['last_name'] ) ? $request['last_name'] : null,
				'shipping_country'    => isset( $request['country'] ) ? $request['country'] : null,
				'shipping_state'      => isset( $request['state'] ) ? $request['state'] : null,
				'shipping_postcode'   => isset( $request['postcode'] ) ? $request['postcode'] : null,
				'shipping_city'       => isset( $request['city'] ) ? $request['city'] : null,
				'shipping_address_1'  => isset( $request['address_1'] ) ? $request['address_1'] : null,
				'shipping_address_2'  => isset( $request['address_2'] ) ? $request['address_2'] : null,
			)
		);
		wc()->customer->save();

		$cart->calculate_shipping();
		$cart->calculate_totals();

		return rest_ensure_response( $this->schema->get_item_response( $cart ) );
	}

	/**
	 * Format the request address.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response
	 */
	public function validate_shipping_address( $request ) {
		$valid_countries = wc()->countries->get_shipping_countries();

		if ( empty( $request['country'] ) ) {
			throw new RouteException(
				'woocommerce_rest_cart_shipping_rates_missing_country',
				sprintf(
					/* translators: 1: valid country codes */
					__( 'No destination country code was given. Please provide one of the following: %s', 'woocommerce' ),
					implode( ', ', array_keys( $valid_countries ) )
				),
				400
			);
		}

		$request['country'] = wc_strtoupper( $request['country'] );

		if (
			is_array( $valid_countries ) &&
			count( $valid_countries ) > 0 &&
			! array_key_exists( $request['country'], $valid_countries )
		) {
			throw new RouteException(
				'woocommerce_rest_cart_shipping_rates_invalid_country',
				sprintf(
					/* translators: 1: valid country codes */
					__( 'Destination country code is not valid. Please enter one of the following: %s', 'woocommerce' ),
					implode( ', ', array_keys( $valid_countries ) )
				),
				400
			);
		}

		$request['postcode'] = $request['postcode'] ? wc_format_postcode( $request['postcode'], $request['country'] ) : null;

		if ( ! empty( $request['state'] ) ) {
			$valid_states = wc()->countries->get_states( $request['country'] );

			if ( is_array( $valid_states ) && count( $valid_states ) > 0 ) {
				$valid_state_values = array_map( 'wc_strtoupper', array_flip( array_map( 'wc_strtoupper', $valid_states ) ) );
				$request['state']   = wc_strtoupper( $request['state'] );

				if ( isset( $valid_state_values[ $request['state'] ] ) ) {
					// With this part we consider state value to be valid as well,
					// convert it to the state key for the valid_states check below.
					$request['state'] = $valid_state_values[ $request['state'] ];
				}

				if ( ! in_array( $request['state'], $valid_state_values, true ) ) {
					throw new RouteException(
						'woocommerce_rest_cart_shipping_rates_invalid_state',
						sprintf(
							/* translators: 1: valid states */
							__( 'Destination state is not valid. Please enter one of the following: %s', 'woocommerce' ),
							implode( ', ', $valid_states )
						),
						400
					);
				}
			}
		}

		return $request;
	}
}
