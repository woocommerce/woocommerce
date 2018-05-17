<?php
/**
 * REST API Order Refunds controller
 *
 * Handles requests to the /orders/<order_id>/refunds endpoint.
 *
 * @package WooCommerce/API
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Order Refunds controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Order_Refunds_V2_Controller
 */
class WC_REST_Order_Refunds_Controller extends WC_REST_Order_Refunds_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
