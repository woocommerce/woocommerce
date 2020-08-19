<?php
/**
 * REST API Product Attribute Terms controller
 *
 * Handles requests to the products/attributes/<attribute_id>/terms endpoint.
 *
 * @package Automattic/WooCommerce/RestApi
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Product Attribute Terms controller class.
 *
 * @package Automattic/WooCommerce/RestApi
 * @extends WC_REST_Product_Attribute_Terms_V1_Controller
 */
class WC_REST_Product_Attribute_Terms_V2_Controller extends WC_REST_Product_Attribute_Terms_V1_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v2';
}
