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
 * @extends WC_REST_Controller
 */
class WC_REST_Shipping_Zone_Locations_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'shipping/zones';

	/**
	 * Get the Shipping Zone Locations schema, conforming to JSON Schema
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'shipping_zone_location',
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
						'sanitize_callback' => 'absint',
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

		return $this->add_additional_fields_schema( $schema );
	}

}