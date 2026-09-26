<?php
/**
 * REST API Reports stock notifications stats controller
 *
 * Handles requests to the /reports/stock-notifications/stats endpoint.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\API\Reports\StockNotifications\Stats;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;
use Automattic\WooCommerce\Admin\API\Reports\GenericStatsController;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API Reports stock notifications stats controller class.
 *
 * @internal
 */
class Controller extends GenericStatsController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/stock-notifications/stats';

	/**
	 * Maps query arguments from the REST request.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request Request array.
	 * @return array
	 */
	protected function prepare_reports_query( $request ) {
		$args                        = array();
		$args['before']              = $request['before'];
		$args['after']               = $request['after'];
		$args['interval']            = $request['interval'];
		$args['page']                = $request['page'];
		$args['per_page']            = $request['per_page'];
		$args['orderby']             = $request['orderby'];
		$args['order']               = $request['order'];
		$args['fields']              = $request['fields'];
		$args['force_cache_refresh'] = $request['force_cache_refresh'];

		return $args;
	}

	/**
	 * Get data from `'stock-notifications-stats'` GenericQuery.
	 *
	 * @override GenericController::get_datastore_data()
	 *
	 * @param array $query_args Query arguments.
	 * @return mixed Results from the data store.
	 */
	protected function get_datastore_data( $query_args = array() ) {
		$query = new GenericQuery( $query_args, 'stock-notifications-stats' );
		return $query->get_data();
	}

	/**
	 * Prepare a report data item for serialization.
	 *
	 * @param array                                 $report  Report data item as returned from Data Store.
	 * @param WP_REST_Request<array<string, mixed>> $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $report, $request ) {
		$response = parent::prepare_item_for_response( $report, $request );

		/**
		 * Filter a stock notifications stats interval returned from the API.
		 *
		 * @since 11.3.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param array            $report   The original report interval.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_report_stock_notifications_stats', $response, $report, $request );
	}

	/**
	 * Get the Report's item properties schema.
	 * Will be used by `get_item_schema` as `totals` and `subtotals`.
	 *
	 * @return array
	 */
	protected function get_item_properties_schema() {
		return array(
			'signups'            => array(
				'title'       => __( 'Sign-ups', 'woocommerce' ),
				'description' => __( 'Sign-ups created in the period. Excludes unconfirmed (pending) sign-ups.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'customers'          => array(
				'title'       => __( 'Customers', 'woocommerce' ),
				'description' => __( 'Distinct email addresses behind the sign-ups in the period.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'notifications_sent' => array(
				'title'       => __( 'Notifications sent', 'woocommerce' ),
				'description' => __( 'Back-in-stock emails sent in the period. A re-sent notification counts once, on its latest send date.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'active_signups'     => array(
				'title'       => __( 'Waiting', 'woocommerce' ),
				'description' => __( 'Customers waiting right now, whatever the period. Totals only, not included in intervals.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 * It does not have the segments as in GenericStatsController.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema          = parent::get_item_schema();
		$schema['title'] = 'report_stock_notifications_stats';

		unset( $schema['properties']['totals']['properties']['segments'] );
		$schema['properties']['intervals']['items']['properties']['subtotals']['properties'] = array_diff_key(
			$this->get_item_properties_schema(),
			array( 'active_signups' => true )
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                        = parent::get_collection_params();
		$params['interval']['default'] = 'day';
		$params['orderby']['enum']     = $this->apply_custom_orderby_filters(
			array(
				'date',
				'signups',
				'customers',
			)
		);

		return $params;
	}
}
