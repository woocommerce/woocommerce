<?php
/**
 * REST API Customers Controller
 *
 * Handles requests to /customers/*
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * Customers controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_Admin_REST_Reports_Customers_Controller
 */
class WC_Admin_REST_Customers_Controller extends WC_Admin_REST_Reports_Customers_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'customers';

	/**
	 * Maps query arguments from the REST request.
	 *
	 * @param array $request Request array.
	 * @return array
	 */
	protected function prepare_reports_query( $request ) {
		$args              = parent::prepare_reports_query( $request );
		$args['customers'] = $request['include'];
		return $args;
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params            = parent::get_collection_params();
		$params['include'] = $params['customers'];
		unset( $params['customers'] );
		return $params;
	}
}
