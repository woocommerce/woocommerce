<?php
/**
 * REST API WC Telemetry Controller
 *
 * Handles requests to the /system_status/App/* endpoints.
 *
 * @package WooCommerce\RestApi
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Telemetry controller.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Telemetry_V2_Controller
 */
class WC_REST_Telemetry_Controller extends WC_REST_Telemetry_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
