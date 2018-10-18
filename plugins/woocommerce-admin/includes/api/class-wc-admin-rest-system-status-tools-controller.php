<?php
/**
 * REST API WC System Status Tools Controller
 *
 * Handles requests to the /system_status/tools/* endpoints.
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * System status tools controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_System_Status_Tools_Controller
 */
class WC_Admin_REST_System_Status_Tools_Controller extends WC_REST_System_Status_Tools_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * A list of available tools for use in the system status section.
	 * 'button' becomes 'action' in the API.
	 *
	 * @return array
	 */
	public function get_tools() {
		return array_merge(
			parent::get_tools(),
			array(
				'rebuild_stats' => array(
					'name'   => __( 'Rebuild reports data', 'wc-admin' ),
					'button' => __( 'Rebuild reports', 'wc-admin' ),
					'desc'   => __( 'This tool will rebuild all of the information used by the reports.', 'wc-admin' ),
				),
			)
		);
	}

	/**
	 * Actually executes a tool.
	 *
	 * @param  string $tool Tool.
	 * @return array
	 */
	public function execute_tool( $tool ) {
		$ran = true;
		$message = '';

		switch ( $tool ) {
			case 'rebuild_stats':
				WC_Admin_Reports_Orders_Data_Store::queue_order_stats_repopulate_database();
				$message = __( 'Rebuilding reports data in the background . . .', 'wc-admin' );
				break;
			default:
				return parent::execute_tool( $tool );
		}

		return array(
			'success' => $ran,
			'message' => $message,
		);
	}
}
