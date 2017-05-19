<?php
/**
 * REST API Data controller
 *
 * Handles requests to the /data/location endpoint.
 *
 * @author   WooThemes
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
class WC_REST_Data_Locations_Controller extends WC_REST_Data_Controller {

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
	protected $rest_base = 'data/locations';

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
		) );
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<location>[\w-]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args' => array(
					'continent' => array(
						'description' => __( 'ISO3166 alpha-2 country or continent code', 'woocommerce' ),
						'type'        => 'string',
					),
				),
			),
		) );
	}

	/**
	 * Return the list of continents, countries, and states, possibly restricted by args in $request
	 *
	 * @since  3.1.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$continents = WC()->countries->get_continents();
		$countries = WC()->countries->get_countries();
		$states = WC()->countries->get_states();
		$location_filter = strtoupper( $request['location'] );
		$is_continent_filter = in_array( $location_filter, array_map( 'strtoupper', array_keys( $continents ) ) );

		$data = array();
		foreach ( $continents as $continent_code => $continent_list ) {
			if ( $is_continent_filter && ( strtoupper( $continent_code ) !== $location_filter ) ) {
				continue;
			}

			$continent = array(
				'code' => $continent_code,
				'name' => $continent_list['name'],
			);
			$local_countries = array();
			foreach ( $continent_list['countries'] as $country_code ) {
				if ( ! $is_continent_filter && $location_filter && ( strtoupper( $country_code ) !== $location_filter ) ) {
					continue;
				}
				if ( isset( $countries[ $country_code ] ) ) {
					$country = array(
						'code' => $country_code,
						'name' => $countries[ $country_code ],
					);

					if ( isset( $states[ $country_code ] ) ) {
						$country['states'] = $states[ $country_code ];
					} else {
						$country['states'] = array();
					}
					$local_countries[] = $country;
				}
			}
			if ( ! empty( $local_countries ) ) {
				$continent['countries'] = $local_countries;
				$data[] = $continent;
			}
		}

		if ( empty( $data ) ) {
			$data = new WP_Error( 'woocommerce_rest_data_invalid_location', __( 'There are no locations matching these parameters.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$response = $this->prepare_item_for_response( $data, $request );

		/**
		 * Filter the locations list returned from the API.
		 *
		 * Allows modification of the loction data right before it is returned.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param array            $data     The original location list.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_data_locations', $response, $data, $request );
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
		return $response;
	}
}
