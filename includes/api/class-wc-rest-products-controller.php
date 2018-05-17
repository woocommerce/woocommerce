<?php
/**
 * REST API Products controller
 *
 * Handles requests to the /products endpoint.
 *
 * @package WooCommerce/API
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Products controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Products_V2_Controller
 */
class WC_REST_Products_Controller extends WC_REST_Products_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
