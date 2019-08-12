<?php
/**
 * REST API Customers Controller
 *
 * Handles requests to /customers/*
 *
 * @package WooCommerce Admin/API
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

/**
 * Customers controller.
 *
 * @package WooCommerce Admin/API
 * @extends \Automattic\WooCommerce\Admin\API\Reports\Customers\Controller
 */
class Customers extends \Automattic\WooCommerce\Admin\API\Reports\Customers\Controller {

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
