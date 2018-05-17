<?php
/**
 * REST API Order Notes controller
 *
 * Handles requests to the /orders/<order_id>/notes endpoint.
 *
 * @package WooCommerce/API
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Order Notes controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Order_Notes_V2_Controller
 */
class WC_REST_Order_Notes_Controller extends WC_REST_Order_Notes_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
