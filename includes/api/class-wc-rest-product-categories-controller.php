<?php
/**
 * REST API Product Categories controller
 *
 * Handles requests to the products/categories endpoint.
 *
 * @package WooCommerce/API
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Product Categories controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Product_Categories_V2_Controller
 */
class WC_REST_Product_Categories_Controller extends WC_REST_Product_Categories_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
