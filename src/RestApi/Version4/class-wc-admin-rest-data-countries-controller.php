<?php
/**
 * REST API Data countries controller.
 *
 * Handles requests to the /data/countries endpoint.
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Data countries controller class.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Data_Countries_Controller
 */
class WC_Admin_REST_Data_Countries_Controller extends WC_REST_Data_Countries_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

}
