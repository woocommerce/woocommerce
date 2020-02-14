<?php
/**
 * Cart shipping rates controller.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers;

defined( 'ABSPATH' ) || exit;

use \WP_Error as RestError;
use \WP_REST_Server as RestServer;
use \WP_REST_Controller as RestController;
use Automattic\WooCommerce\Blocks\RestApi\StoreApi\Schemas\CartShippingRateSchema;
use Automattic\WooCommerce\Blocks\RestApi\StoreApi\Utilities\CartController;

/**
 * Cart Shipping Rates API.
 */
class CartShippingRates extends RestController {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/store';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'cart/shipping-rates';

	/**
	 * Schema class instance.
	 *
	 * @var object
	 */
	protected $schema;

	/**
	 * Setup API class.
	 */
	public function __construct() {
		$this->schema = new CartShippingRateSchema();
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'  => RestServer::READABLE,
					'callback' => [ $this, 'get_items' ],
					'args'     => [
						'context' => $this->get_context_param( [ 'default' => 'view' ] ),
					],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * Get shipping rates for the cart.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {
		$controller = new CartController();
		$cart       = $controller->get_cart_instance();

		if ( ! $cart || ! $cart instanceof \WC_Cart ) {
			return new RestError( 'woocommerce_rest_cart_error', __( 'Unable to retrieve cart.', 'woo-gutenberg-products-block' ), array( 'status' => 500 ) );
		}

		if ( ! empty( $request['country'] ) ) {
			$valid_countries = WC()->countries->get_shipping_countries();

			if (
				is_array( $valid_countries ) &&
				count( $valid_countries ) > 0 &&
				! array_key_exists( $request['country'], $valid_countries )
			) {
				return new RestError(
					'woocommerce_rest_cart_shipping_rates_invalid_country',
					sprintf(
						/* translators: 1: valid country codes */
						__( 'Destination country code is not valid. Please enter one of the following: %s', 'woo-gutenberg-products-block' ),
						implode( ', ', array_keys( $valid_countries ) )
					),
					[ 'status' => 400 ]
				);
			}
		}

		$request = $this->validate_shipping_address( $request );

		if ( is_wp_error( $request ) ) {
			return $request;
		}

		$cart_items = $controller->get_cart_items(
			function( $item ) {
				return ! empty( $item['data'] ) && $item['data']->needs_shipping();
			}
		);

		if ( empty( $cart_items ) ) {
			return rest_ensure_response( [] );
		}

		$packages = $this->get_shipping_packages( $request );
		$response = [];

		foreach ( $packages as $package ) {
			$response[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( $package, $request ) );
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Format the request address.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	protected function validate_shipping_address( $request ) {
		$request['country']  = wc_strtoupper( $request['country'] );
		$request['postcode'] = $request['postcode'] ? wc_format_postcode( $request['postcode'], $request['country'] ) : null;

		if ( ! empty( $request['state'] ) ) {
			$valid_states = WC()->countries->get_states( $request['country'] );

			if ( is_array( $valid_states ) && count( $valid_states ) > 0 ) {
				$valid_state_values = array_map( 'wc_strtoupper', array_flip( array_map( 'wc_strtoupper', $valid_states ) ) );
				$request['state']   = wc_strtoupper( $request['state'] );

				if ( isset( $valid_state_values[ $request['state'] ] ) ) {
					// With this part we consider state value to be valid as well,
					// convert it to the state key for the valid_states check below.
					$request['state'] = $valid_state_values[ $request['state'] ];
				}

				if ( ! in_array( $request['state'], $valid_state_values, true ) ) {
					return new RestError(
						'woocommerce_rest_cart_shipping_rates_invalid_state',
						sprintf(
							/* translators: 1: valid states */
							__( 'Destination state is not valid. Please enter one of the following: %s', 'woo-gutenberg-products-block' ),
							implode( ', ', $valid_states )
						),
						[ 'status' => 400 ]
					);
				}
			}
		}

		return $request;
	}

	/**
	 * Cart item schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return $this->schema->get_item_schema();
	}

	/**
	 * Prepares a single item output for response.
	 *
	 * @param array            $package Shipping package complete with rates from WooCommerce.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $package, $request ) {
		return rest_ensure_response( $this->schema->get_item_response( $package ) );
	}

	/**
	 * Get the query params available for this endpoint.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = array();
		$params['context']            = $this->get_context_param();
		$params['context']['default'] = 'view';

		$params['address_1'] = array(
			'description'       => __( 'First line of the address being shipped to.', 'woo-gutenberg-products-block' ),
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'wc_clean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['address_2'] = array(
			'description'       => __( 'Second line of the address being shipped to.', 'woo-gutenberg-products-block' ),
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'wc_clean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['city'] = array(
			'description'       => __( 'City of the address being shipped to.', 'woo-gutenberg-products-block' ),
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'wc_clean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['state'] = array(
			'description'       => __( 'ISO code, or name, for the state, province, or district of the address being shipped to.', 'woo-gutenberg-products-block' ),
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'wc_clean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['postcode'] = array(
			'description'       => __( 'Zip or Postcode of the address being shipped to.', 'woo-gutenberg-products-block' ),
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'wc_clean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['country'] = array(
			'description'       => __( 'ISO code for the country of the address being shipped to.', 'woo-gutenberg-products-block' ),
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'wc_clean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get packages with calculated shipping.
	 *
	 * Based on WC_Cart::get_shipping_packages but allows the destination to be
	 * customised based on passed params.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return array of cart items
	 */
	protected function get_shipping_packages( $request ) {
		$packages = WC()->cart->get_shipping_packages();

		if ( $request['country'] ) {
			foreach ( $packages as $key => $package ) {
				$packages[ $key ]['destination'] = [
					'address_1' => $request['address_1'],
					'address_2' => $request['address_2'],
					'city'      => $request['city'],
					'state'     => $request['state'],
					'postcode'  => $request['postcode'],
					'country'   => $request['country'],
				];
			}
		}

		$packages = WC()->shipping()->calculate_shipping( $packages );

		return $packages;
	}
}
