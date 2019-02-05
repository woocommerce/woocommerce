<?php
/**
 * REST API Product Variations Controller
 *
 * Handles requests to /products/variations.
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product variations controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Product_Variations_Controller
 */
class WC_Admin_REST_Product_Variations_Controller extends WC_REST_Product_Variations_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';
}
