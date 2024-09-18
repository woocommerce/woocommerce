<?php
/**
 * REST API Brands controller.
 *
 * Handles requests to /products/brands endpoint.
 *
 * Important: For internal use only by the Automattic\WooCommerce\Internal\Brands package.
 *
 * @package WooCommerce\RestApi
 * @since   9.4.0
 */

declare( strict_types = 1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * REST API Brands controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Product_Categories_Controller
 */
class WC_REST_Product_Brands_Controller extends WC_REST_Product_Categories_Controller {

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
