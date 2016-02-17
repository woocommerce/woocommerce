<?php
/**
 * REST API Order Refunds controller
 *
 * Handles requests to the /orders/<order_id>/refunds endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Order Refunds controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Controller
 */
class WC_REST_Order_Refunds_Controller extends WC_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $base = 'orders/(?P<order_id>[\d]+)/refunds';

	/**
	 * Type of object.
	 *
	 * @var string
	 */
	protected $object = 'shop_order_refund';

	/**
	 * Register the routes for order refunds.
	 */
	public function register_routes() {

	}
}
