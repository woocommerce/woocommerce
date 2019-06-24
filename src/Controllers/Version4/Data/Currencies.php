<?php
/**
 * REST API Data currencies controller.
 *
 * Handles requests to the /data/currencies endpoint.
 *
 * @package Automattic/WooCommerce/RestApi
 */

namespace Automattic\WooCommerce\RestApi\Controllers\Version4\Data;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Controllers\Version4\Data as DataController;

/**
 * REST API Data Currencies controller class.
 */
class Currencies extends DataController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'data/currencies';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/current',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_current_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<currency>[\w-]{3})',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'location' => array(
							'description' => __( 'ISO4217 currency code.', 'woocommerce-rest-api' ),
							'type'        => 'string',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Get currency information.
	 *
	 * @param  string           $code    Currency code.
	 * @param  \WP_REST_Request $request Request data.
	 * @return array|mixed Response data, ready for insertion into collection data.
	 */
	public function get_currency( $code = false, $request ) {
		$currencies = get_woocommerce_currencies();
		$data       = array();

		if ( ! array_key_exists( $code, $currencies ) ) {
			return false;
		}

		$currency = array(
			'code'   => $code,
			'name'   => $currencies[ $code ],
			'symbol' => get_woocommerce_currency_symbol( $code ),
		);

		return $currency;
	}

	/**
	 * Return the list of currencies.
	 *
	 * @param  \WP_REST_Request $request Request data.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {
		$currencies = get_woocommerce_currencies();
		foreach ( array_keys( $currencies ) as $code ) {
			$currency = $this->get_currency( $code, $request );
			$response = $this->prepare_item_for_response( $currency, $request );
			$data[]   = $this->prepare_response_for_collection( $response );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Return information for a specific currency.
	 *
	 * @param  \WP_REST_Request $request Request data.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_item( $request ) {
		$data = $this->get_currency( strtoupper( $request['currency'] ), $request );
		if ( empty( $data ) ) {
			return new \WP_Error( 'woocommerce_rest_data_invalid_currency', __( 'There are no currencies matching these parameters.', 'woocommerce-rest-api' ), array( 'status' => 404 ) );
		}
		return $this->prepare_item_for_response( $data, $request );
	}

	/**
	 * Return information for the current site currency.
	 *
	 * @param  \WP_REST_Request $request Request data.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_current_item( $request ) {
		$currency = get_option( 'woocommerce_currency' );
		return $this->prepare_item_for_response( $this->get_currency( $currency, $request ), $request );
	}

	/**
	 * Get data for this object in the format of this endpoint's schema.
	 *
	 * @param mixed            $object Object to prepare.
	 * @param \WP_REST_Request $request Request object.
	 * @return mixed Array of data in the correct format.
	 */
	protected function get_data_for_response( $object, $request ) {
		return $object;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param mixed            $item Object to prepare.
	 * @param \WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function prepare_links( $item, $request ) {
		$code  = strtoupper( $item['code'] );
		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $code ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		return $links;
	}


	/**
	 * Get the currency schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'currency',
			'type'       => 'object',
			'properties' => array(
				'code'   => array(
					'type'        => 'string',
					'description' => __( 'ISO4217 currency code.', 'woocommerce-rest-api' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'name'   => array(
					'type'        => 'string',
					'description' => __( 'Full name of currency.', 'woocommerce-rest-api' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'symbol' => array(
					'type'        => 'string',
					'description' => __( 'Currency symbol.', 'woocommerce-rest-api' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
