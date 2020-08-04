<?php
/**
 * REST API WC Shipping Methods controller
 *
 * Handles requests to the /shipping_methods endpoint.
 *
 * @package Automattic/WooCommerce/RestApi
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shipping methods controller class.
 *
 * @package Automattic/WooCommerce/RestApi
 * @extends WC_REST_Shipping_Methods_V2_Controller
 */
class WC_REST_Shipping_Methods_Controller extends WC_REST_Shipping_Methods_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
