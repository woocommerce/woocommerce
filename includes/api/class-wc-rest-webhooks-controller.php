<?php
/**
 * REST API Webhooks controller
 *
 * Handles requests to the /webhooks endpoint.
 *
 * @package WooCommerce/API
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Webhooks controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Webhooks_V2_Controller
 */
class WC_REST_Webhooks_Controller extends WC_REST_Webhooks_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
