<?php
/**
 * REST API Order Notes controller
 *
 * Handles requests to the /orders/<order_id>/notes endpoint.
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
 * REST API Order Notes controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Controller
 */
class WC_REST_Order_Notes_Controller extends WC_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'orders/(?P<order_id>[\d]+)/notes';

	/**
	 * Type of object.
	 *
	 * @var string
	 */
	protected $object = 'order_note';

	/**
	 * Register the routes for order notes.
	 */
	public function register_routes() {

	}
}
