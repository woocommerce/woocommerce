<?php
/**
 * REST API Settings V4 controller.
 *
 * This controller extends the V3 Options settings controller to make all V3 Options settings endpoints
 * available under the V4 namespace.
 *
 * @package WooCommerce\RestApi
 * @since   8.6.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Settings V4 controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Setting_Options_Controller
 */
class WC_REST_Settings_V4_Controller extends WC_REST_Setting_Options_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';
}
