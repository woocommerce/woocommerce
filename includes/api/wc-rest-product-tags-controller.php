<?php
/**
 * REST API Product Tags controller
 *
 * Handles requests to the products/tags endpoint.
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
 * REST API Product Tags controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Controller
 */
class WC_REST_Product_Tags_Controller extends WC_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'products/tags';

	/**
	 * Type of object.
	 *
	 * @var string
	 */
	protected $object = 'product_tag';

	/**
	 * Register the routes for coupons.
	 */
	public function register_routes() {

	}
}
