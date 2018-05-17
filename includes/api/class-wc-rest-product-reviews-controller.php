<?php
/**
 * REST API Product Reviews Controller
 *
 * Handles requests to /products/<product_id>/reviews.
 *
 * @package WooCommerce/API
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Product Reviews Controller Class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Product_Reviews_V2_Controller
 */
class WC_REST_Product_Reviews_Controller extends WC_REST_Product_Reviews_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
