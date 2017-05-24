<?php
/**
 * REST API Data controller.
 *
 * Handles requests to the /data/continents endpoint.
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
class WC_REST_Data_Continents_Controller extends WC_REST_Data_Controller {

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
	protected $rest_base = 'data/continents';

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
					'continent' => array(
						'description' => __( '2 character continent code.', 'woocommerce' ),
						'type'        => 'string',
					),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Return the list of continents, countries, and states, possibly restricted by args in $request.
	 *
	 * @since  3.1.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$continents          = WC()->countries->get_continents();
		$countries           = WC()->countries->get_countries();
		$states              = WC()->countries->get_states();
		$location_filter     = strtoupper( $request['location'] );

		$data = array();
		foreach ( $continents as $continent_code => $continent_list ) {
			if ( $location_filter && strtoupper( $continent_code ) !== $location_filter ) {
				continue;
			}

			$continent = array(
				'code' => $continent_code,
				'name' => $continent_list['name'],
			);
			$local_countries = array();
			foreach ( $continent_list['countries'] as $country_code ) {
				if ( isset( $countries[ $country_code ] ) ) {
					$country = array(
						'code' => $country_code,
						'name' => $countries[ $country_code ],
					);

					$local_states = array();
					if ( isset( $states[ $country_code ] ) ) {
						foreach ( $states[ $country_code ] as $state_code => $state_name ) {
							$local_states[] = array(
								'code' => $state_code,
								'name' => $state_name,
							);
						}
					}
					$country['states'] = $local_states;
					$local_countries[] = $country;
				}
			}

			if ( ! empty( $local_countries ) ) {
				$continent['countries'] = $local_countries;
				$data[] = $continent;
			}
		}

		// If the data is filtered by continent, we don't need the array wrapper
		if ( $location_filter && ! empty( $data ) ) {
			$data = $data[0];
		}

		if ( empty( $data ) ) {
			$data = new WP_Error( 'woocommerce_rest_data_invalid_location', __( 'There are no locations matching these parameters.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$response = $this->prepare_item_for_response( $data, $request );

		/**
		 * Filter the location list returned from the API.
		 *
		 * Allows modification of the loction data right before it is returned.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param array            $data     The original list of continent(s), countries, and states.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_data_continents', $response, $data, $request );
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
	 * @return array Links for the given continent.
	 */
	protected function prepare_links( $item ) {
		$links = array();
		if ( array_key_exists( 'code', $item ) ) {
			$continent_code = strtolower( $item['code'] );
			$links['self'] = array(
				'href' => rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $continent_code ) ),
			);
			$links['collection'] = array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			);
		} else {
			$links['self'] = array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			);
		}

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
			'title'   => 'data_continents',
			'type'       => 'object',
			'properties' => array(
				'code' => array(
					'type'        => 'string',
					'description' => __( '2 character continent code.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'name' => array(
					'type'        => 'string',
					'description' => __( 'Full name of continent.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'countries' => array(
					'type'        => 'array',
					'description' => __( 'List of countries on this continent.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'context'    => array( 'view' ),
						'readonly'   => true,
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
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
