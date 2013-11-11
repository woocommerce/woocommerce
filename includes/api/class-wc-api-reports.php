<?php
/**
 * WooCommerce API Reports Class
 *
 * Handles requests to the /reports endpoint
 *
 * @author      WooThemes
 * @category    API
 * @package     WooCommerce/API
 * @since       2.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


class WC_API_Reports extends WC_API_Resource {

	/** @var string $base the route base */
	protected $base = '/reports';

	/**
	 * Register the routes for this class
	 *
	 * GET /reports
	 * GET /reports/sales
	 *
	 * @since 2.1
	 * @param array $routes
	 * @return array
	 */
	public function register_routes( $routes ) {

		# GET /reports
		$routes[ $this->base ] = array(
			array( array( $this, 'get_reports' ),     WC_API_Server::READABLE ),
		);

		# GET /reports/sales
		$routes[ $this->base . '/sales'] = array(
			array( array( $this, 'get_sales_report' ), WC_API_Server::READABLE ),
		);

		return $routes;
	}


	/**
	 * Get a simple listing of available reports
	 *
	 * @since 2.1
	 * @return array
	 */
	public function get_reports() {

		return array( 'reports' => array( 'sales' ) );
	}


	/**
	 * Get the sales report
	 *
	 * @since 2.1
	 * @return array
	 */
	public function get_sales_report() {

		// TODO: implement - DRY by abstracting the report classes?

		return array();
	}

}
