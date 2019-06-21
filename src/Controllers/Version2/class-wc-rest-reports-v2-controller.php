<?php
/**
 * REST API Reports controller
 *
 * Handles requests to the reports endpoint.
 *
 * @package Automattic/WooCommerce/RestApi
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Reports controller class.
 *
 * @package Automattic/WooCommerce/RestApi
 * @extends WC_REST_Reports_V1_Controller
 */
class WC_REST_Reports_V2_Controller extends WC_REST_Reports_V1_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v2';
}
