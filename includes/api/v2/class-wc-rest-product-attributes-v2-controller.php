<?php
/**
 * REST API Product Attributes controller
 *
 * Handles requests to the products/attributes endpoint.
 *
 * @package WooCommerce/API
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Product Attributes controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Product_Attributes_V1_Controller
 */
class WC_REST_Product_Attributes_V2_Controller extends WC_REST_Product_Attributes_V1_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v2';
}
