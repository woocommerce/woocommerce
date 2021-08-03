<?php
/**
 * REST API WC System Status App Controller
 *
 * Handles requests to the /system_status/App/* endpoints.
 *
 * @package WooCommerce\RestApi
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * System status App controller.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_System_Status_App_V2_Controller
 */
class WC_REST_System_Status_App_Controller extends WC_REST_System_Status_App_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
