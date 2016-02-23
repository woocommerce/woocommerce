<?php
/**
 * REST API Product Attributes controller
 *
 * Handles requests to the products/attributes endpoint.
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
 * REST API Product Attributes controller class.
 *
 * @package WooCommerce/API
 * @extends WP_REST_Controller
 */
class WC_REST_Product_Attributes_Controller extends WP_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'products/attributes';

	/**
	 * Register the routes for product attributes.
	 */
	public function register_routes() {

	}
}
