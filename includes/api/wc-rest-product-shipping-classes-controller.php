<?php
/**
 * REST API Product Shipping Classes controller
 *
 * Handles requests to the products/shipping_classes endpoint.
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
 * REST API Product Shipping Classes controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Controller
 */
class WC_REST_Product_Shipping_Classes_Controller extends WC_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $base = 'products/shipping_classes';

	/**
	 * Type of object.
	 *
	 * @var string
	 */
	protected $object = 'product_shipping_class';

	/**
	 * Register the routes for coupons.
	 */
	public function register_routes() {

	}
}
