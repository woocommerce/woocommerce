<?php
/**
 * REST API Webhooks controller
 *
 * Handles requests to the /webhooks endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Webhooks controller class.
 *
 * @package WooCommerce/API
 * @extends WP_REST_Controller
 */
class WC_REST_Webhooks_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	public $namespace = 'wc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'webhooks';

	/**
	 * Register the routes for webhooks.
	 */
	public function register_routes() {

	}
}
