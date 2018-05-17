<?php
/**
 * REST API Setting Options controller
 *
 * Handles requests to the /settings/$group/$setting endpoints.
 *
 * @package WooCommerce/API
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Setting Options controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Setting_Options_V2_Controller
 */
class WC_REST_Setting_Options_Controller extends WC_REST_Setting_Options_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
