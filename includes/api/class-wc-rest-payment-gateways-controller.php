<?php
/**
 * REST API WC Payment gateways controller
 *
 * Handles requests to the /payment_gateways endpoint.
 *
 * @package WooCommerce/API
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Paymenga gateways controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Payment_Gateways_V2_Controller
 */
class WC_REST_Payment_Gateways_Controller extends WC_REST_Payment_Gateways_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
