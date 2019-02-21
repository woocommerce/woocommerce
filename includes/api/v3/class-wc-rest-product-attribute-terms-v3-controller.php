<?php
/**
 * REST API Product Attribute Terms controller
 *
 * Handles requests to the products/attributes/<attribute_id>/terms endpoint.
 *
 * @package WooCommerce/API
 * @since   3.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Product Attribute Terms controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Product_Attribute_Terms_V2_Controller
 */
class WC_REST_Product_Attribute_Terms_V3_Controller extends WC_REST_Product_Attribute_Terms_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
