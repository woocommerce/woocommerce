<?php
/**
 * REST API Orders controller
 *
 * Handles requests to the /orders endpoint.
 *
 * @package  WooCommerce/API
 * @since    2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Orders controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Orders_V2_Controller
 */
class WC_REST_Orders_Controller extends WC_REST_Orders_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
