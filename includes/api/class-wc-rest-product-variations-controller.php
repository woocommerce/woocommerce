<?php
/**
 * REST API variations controller
 *
 * Handles requests to the /products/<product_id>/variations endpoints.
 *
 * @package WooCommerce\API
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API variations controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Product_Variations_V2_Controller
 */
class WC_REST_Product_Variations_Controller extends WC_REST_Product_Variations_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
