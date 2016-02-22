<?php
/**
 * REST API Tax Classes controller
 *
 * Handles requests to the /taxes/classes endpoint.
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
 * REST API Tax Classes controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Controller
 */
class WC_REST_Tax_Classes_Controller extends WC_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'taxes/classes';

	/**
	 * Register the routes for coupons.
	 */
	public function register_routes() {

	}
}
