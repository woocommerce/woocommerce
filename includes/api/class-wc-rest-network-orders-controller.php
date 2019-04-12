<?php
/**
 * REST API Network Orders controller
 *
 * Handles requests to the /orders/network endpoint
 *
 * @package WooCommerce/API
 * @since   3.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Network Orders controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Network_Orders_V2_Controller
 */
class WC_REST_Network_Orders_Controller extends WC_REST_Network_Orders_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
