<?php
/**
 * REST API Shipping Zones controller
 *
 * Handles requests to the /shipping/zones endpoint.
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
 * REST API Shipping Zones class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Controller
 */
class WC_REST_Shipping_Zones_Controller extends WC_REST_Controller {

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
	 * Get the Shipping Zones schema, conforming to JSON Schema
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'shipping_zone',
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name' => array(
					'description' => __( 'Shipping zone name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'order' => array(
					'description' => __( 'Shipping zone order.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'required'    => false,
					'arg_options' => array(
						'sanitize_callback' => 'absint',
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
