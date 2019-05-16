<?php
/**
 * REST API Shipping Zone Locations controller
 *
 * Handles requests to the /shipping/zones/<id>/locations endpoint.
 *
 * @package WooCommerce/API
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Shipping Zone Locations class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Shipping_Zone_Locations_V2_Controller
 */
class WC_REST_Shipping_Zone_Locations_Controller extends WC_REST_Shipping_Zone_Locations_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';
}
