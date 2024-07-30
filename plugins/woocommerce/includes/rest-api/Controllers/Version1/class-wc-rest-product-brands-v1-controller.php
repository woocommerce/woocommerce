<?php
/**
 * REST API Brands controller.
 *
 * Handles requests to /products/brands endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   x.x.x
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Brands controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Product_Categories_Controller
 */
class WC_REST_Product_Brands_V1_Controller extends WC_REST_Product_Categories_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'products/brands';

	/**
	 * Taxonomy.
	 *
	 * @var string
	 */
	protected $taxonomy = 'product_brand';
}
