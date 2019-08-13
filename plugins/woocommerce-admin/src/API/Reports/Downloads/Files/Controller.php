<?php
/**
 * REST API Reports downloads files controller
 *
 * Handles requests to the /reports/downloads/files endpoint.
 *
 * @package WooCommerce Admin/API
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Downloads\Files;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Reports downloads files controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Reports_Controller
 */
class Controller extends \WC_REST_Reports_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/downloads/files';
}
