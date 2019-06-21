<?php
/**
 * REST API Product Attributes controller
 *
 * Handles requests to the products/attributes endpoint.
 *
 * @package Automattic/WooCommerce/RestApi
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Product Attributes controller class.
 *
 * @package Automattic/WooCommerce/RestApi
 * @extends WC_REST_Product_Attributes_V2_Controller
 */
class WC_REST_Product_Attributes_Controller extends WC_REST_Product_Attributes_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
