<?php
/**
 * REST API Product Categories controller
 *
 * Handles requests to the products/categories endpoint.
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
 * REST API Product Categories controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Controller
 */
class WC_REST_Product_Categories_Controller extends WC_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'products/categories';

	/**
	 * Type of object.
	 *
	 * @var string
	 */
	protected $object = 'product_cat';

	/**
	 * Register the routes for coupons.
	 */
	public function register_routes() {

	}
}
