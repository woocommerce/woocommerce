<?php
/**
 * REST API Product Shipping Classes controller
 *
 * Handles requests to the products/shipping_classes endpoint.
 *
 * @package Automattic/WooCommerce/RestApi
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Product Shipping Classes controller class.
 *
 * @package Automattic/WooCommerce/RestApi
 * @extends WC_REST_Product_Shipping_Classes_V2_Controller
 */
class WC_REST_Product_Shipping_Classes_Controller extends WC_REST_Product_Shipping_Classes_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
