<?php
/**
 * REST API Data controller.
 *
 * Handles requests to the /data/countries endpoint.
 *
 * @author   Automattic
 * @category API
 * @package  WooCommerce/API
 * @since    3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Data controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Controller
 */
class WC_REST_Data_Countries_Controller extends WC_REST_Data_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v2';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'data/countries';

	/**
	 * Register routes.
	 *
	 * @since 3.1.0
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<location>[\w-]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args' => array(
					'location' => array(
						'description' => __( 'ISO3166 alpha-2 country code.', 'woocommerce' ),
						'type'        => 'string',
					),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Return the list of states for a given country.
	 *
	 * @since  3.1.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$countries           = WC()->countries->get_countries();
		$states              = WC()->countries->get_states();
		$location_filter     = strtoupper( $request['location'] );
		$data                = array();

		if ( isset( $countries[ $location_filter ] ) ) {
			$country = array(
				'code' => $location_filter,
				'name' => $countries[ $location_filter ],
			);

			$local_states = array();
			if ( isset( $states[ $location_filter ] ) ) {
				foreach ( $states[ $location_filter ] as $state_code => $state_name ) {
					$local_states[] = array(
						'code' => $state_code,
						'name' => $state_name,
					);
				}
			}
			$country['states'] = $local_states;
		}

		if ( ! empty( $country ) ) {
			$data = $country;
		}

		if ( empty( $data ) ) {
			$data = new WP_Error( 'woocommerce_rest_data_invalid_location', __( 'There are no locations matching these parameters.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$response = $this->prepare_item_for_response( $data, $request );

		/**
		 * Filter the states list for a country returned from the API.
		 *
		 * Allows modification of the loction data right before it is returned.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param array            $data     The original country's states list.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_data_country_states', $response, $data, $request );
	}

	/**
	 * Prepare the data object for response.
	 *
	 * @since  3.1.0
	 * @param object $item Data object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$response = rest_ensure_response( $item );
		if ( ! is_wp_error( $item ) ) {
			$response->add_links( $this->prepare_links( $item ) );
		}
		return $response;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param object $item Data object.
	 * @return array Links for the given country.
	 */
	protected function prepare_links( $item ) {
		$country_code = strtolower( $item['code'] );
		$links = array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $country_code ) ),
			),
		);

		return $links;
	}


	/**
	 * Get the location schema, conforming to JSON Schema.
	 *
	 * @since  3.1.0
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title'   => 'data_countries',
			'type'       => 'object',
			'properties' => array(
				'code' => array(
					'type'        => 'string',
					'description' => __( 'ISO3166 alpha-2 country code.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'name' => array(
					'type'        => 'string',
					'description' => __( 'Full name of country.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'states' => array(
					'type'        => 'array',
					'description' => __( 'List of states in this country.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'context'    => array( 'view' ),
						'readonly'   => true,
						'properties' => array(
							'code' => array(
								'type'        => 'string',
								'description' => __( 'State code.', 'woocommerce' ),
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'name' => array(
								'type'        => 'string',
								'description' => __( 'Full name of state.', 'woocommerce' ),
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
