<?php
/**
 * REST API Taxes controller
 *
 * Handles requests to the /taxes endpoint.
 *
 * @package Automattic/WooCommerce/RestApi
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Taxes controller class.
 *
 * @package Automattic/WooCommerce/RestApi
 * @extends WC_REST_Taxes_V2_Controller
 */
class WC_REST_Taxes_Controller extends WC_REST_Taxes_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
