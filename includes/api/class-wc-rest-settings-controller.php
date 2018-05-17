<?php
/**
 * REST API Settings controller
 *
 * Handles requests to the /settings endpoints.
 *
 * @package WooCommerce/API
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Settings controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Settings_V2_Controller
 */
class WC_REST_Settings_Controller extends WC_REST_Settings_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
