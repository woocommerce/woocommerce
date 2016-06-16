<?php
/**
 * REST API Shipping Zone Locations controller
 *
 * Handles requests to the /shipping/zones/<id>/locations endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Shipping Zone Locations class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Shipping_Zones_Controller_Base
 */
class WC_REST_Shipping_Zone_Locations_Controller extends WC_REST_Shipping_Zones_Controller_Base {

	/**
	 * Register the routes for Shipping Zone Locations.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d-]+)/locations', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_items' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Get all Shipping Zone Locations.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$zone = $this->get_zone( $request['id'] );

		if ( is_wp_error( $zone ) ) {
			return $zone;
		}
		
		$locations = $zone->get_zone_locations();
		$data      = array();

		foreach ( $locations as $location_obj ) {
			$location = $this->prepare_item_for_response( $location_obj, $request );
			$location = $this->prepare_response_for_collection( $location );
			$data[]   = $location;
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Update all Shipping Zone Locations.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_items( $request ) {
		$zone = $this->get_zone( $request['id'] );

		if ( is_wp_error( $zone ) ) {
			return $zone;
		}

		$raw_locations = $request->get_json_params();
		$locations     = array();

		foreach ( (array) $raw_locations as $raw_location ) {
			if ( empty( $raw_location['code'] ) || empty( $raw_location['type'] ) ) {
				continue;
			}
			$locations[] = $raw_location;
		}

		$zone->set_locations( $locations );
		$zone->save();

		return $this->get_items( $request );
	}

	/**
	 * Prepare the Shipping Zone Location for the REST response.
	 *
	 * @param array $item Shipping Zone Location.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$context = empty( $request['context'] ) ? 'view' : $request['context'];
		$data    = $this->add_additional_fields_to_object( $item, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $request['id'] ) );

		return $response;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param int $zone_id Given Shipping Zone ID.
	 * @return array Links for the given Shipping Zone Location.
	 */
	protected function prepare_links( $zone_id ) {
		$base  = '/' . $this->namespace . '/' . $this->rest_base . '/' . $zone_id;
		$links = array(
			'collection' => array(
				'href' => rest_url( $base . '/locations' ),
			),
			'describes'  => array(
				'href' => rest_url( $base ),
			),
		);

		return $links;
	}

	/**
	 * Get the Shipping Zone Locations schema, conforming to JSON Schema
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$single_location_schema = array(
			'type'       => 'object',
			'properties' => array(
				'code' => array(
					'description' => __( 'Shipping zone location code.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'type' => array(
					'description' => __( 'Shipping zone location type.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
					'enum'        => array(
						'postcode',
						'state',
						'country',
						'continent',
					),
				),
			),
		);
		$schema = array(
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title'   => 'shipping_zone_locations',
			'type'    => 'array',
			'items'   => $this->add_additional_fields_schema( $single_location_schema )
		);

		return $schema;
	}
}