<?php
/**
 * REST API Product Categories Controller
 *
 * Handles requests to /products/categories.
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product categories controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Product_Categories_Controller
 */
class WC_Admin_REST_Product_Categories_Controller extends WC_REST_Product_Categories_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

}
