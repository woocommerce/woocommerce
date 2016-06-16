<?php
/**
 * REST API Shipping Zone Methods controller
 *
 * Handles requests to the /shipping/zones/<id>/methods endpoint.
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
 * REST API Shipping Zone Methods class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Shipping_Zones_Controller_Base
 */
class WC_REST_Shipping_Zone_Methods_Controller extends WC_REST_Shipping_Zones_Controller_Base {

	/**
	 * Register the routes for Shipping Zone Methods.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<zone_id>[\d-]+)/methods', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Get all Shipping Zone Methods.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$zone = $this->get_zone( $request['zone_id'] );

		if ( is_wp_error( $zone ) ) {
			return $zone;
		}

		$methods = $zone->get_shipping_methods();
		$data    = array();

		foreach ( $methods as $method_obj ) {
			$method = $this->prepare_item_for_response( $method_obj, $request );
			$method = $this->prepare_response_for_collection( $method );
			$data[] = $method;
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Prepare the Shipping Zone Method for the REST response.
	 *
	 * @param array $item Shipping Zone Method.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$method = array(
			'instance_id'        => $item->instance_id,
			'title'              => $item->instance_settings['title'],
			'order'              => $item->method_order,
			'enabled'            => ( 'yes' === $item->enabled ),
			'method_id'          => $item->id,
			'method_title'       => $item->method_title,
			'method_description' => $item->method_description,
		);

		$context = empty( $request['context'] ) ? 'view' : $request['context'];
		$data    = $this->add_additional_fields_to_object( $method, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Get the Shipping Zone Methods schema, conforming to JSON Schema
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$single_method_schema = array(
			'type'       => 'object',
			'properties' => array(
				'instance_id' => array(
					'description' => __( 'Shipping method instance ID.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
				),
				'title' => array(
					'description' => __( 'Shipping method customer facing title.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'order' => array(
					'description' => __( 'Shipping method sort order.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'required'    => false,
					'arg_options' => array(
						'sanitize_callback' => 'absint',
					),
				),
				'enabled' => array(
					'description' => __( 'Shipping method enabled status.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'required'    => false,
				),
				'method_id' => array(
					'description' => __( 'Shipping method ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'method_title' => array(
					'description' => __( 'Shipping method title.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'method_description' => array(
					'description' => __( 'Shipping method description.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
			),
		);
		$schema = array(
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title'   => 'shipping_zone_methods',
			'type'    => 'array',
			'items'   => $this->add_additional_fields_schema( $single_method_schema )
		);

		return $schema;
	}
}