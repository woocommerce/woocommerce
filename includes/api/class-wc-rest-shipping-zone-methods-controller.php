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