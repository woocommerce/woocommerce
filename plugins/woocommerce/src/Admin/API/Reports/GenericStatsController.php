<?php
namespace Automattic\WooCommerce\Admin\API\Reports;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\GenericController;

/**
 * Generic base for all Stats controllers.
 *
 * @internal
 * @extends GenericController
 */
abstract class GenericStatsController extends GenericController {

	/**
	 * Get the query params for collections.
	 * Adds intervals to the generic list.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                    = parent::get_collection_params();
		$params['interval']        = array(
			'description'       => __( 'Time interval to use for buckets in the returned data.', 'woocommerce' ),
			'type'              => 'string',
			'default'           => 'week',
			'enum'              => array(
				'hour',
				'day',
				'week',
				'month',
				'quarter',
				'year',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
