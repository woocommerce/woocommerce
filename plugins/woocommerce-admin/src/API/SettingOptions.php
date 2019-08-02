<?php
/**
 * REST API Setting Options Controller
 *
 * Handles requests to /settings/{option}
 *
 * @package WooCommerce Admin/API
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

/**
 * Setting Options controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Setting_Options_Controller
 */
class SettingOptions extends \WC_REST_Setting_Options_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

}
