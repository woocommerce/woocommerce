<?php
/**
 * REST API Reports stock notifications controller
 *
 * Handles requests to the /reports/stock-notifications endpoint.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\API\Reports\StockNotifications;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\ExportableInterface;
use Automattic\WooCommerce\Admin\API\Reports\GenericController;
use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API Reports stock notifications controller class.
 *
 * @internal
 */
class Controller extends GenericController implements ExportableInterface {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/stock-notifications';

	/**
	 * Get data from `'stock-notifications'` GenericQuery.
	 *
	 * @override GenericController::get_datastore_data()
	 *
	 * @param array $query_args Query arguments.
	 * @return mixed Results from the data store.
	 */
	protected function get_datastore_data( $query_args = array() ) {
		$query = new GenericQuery( $query_args, 'stock-notifications' );
		return $query->get_data();
	}

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
		$args['page']                = $request['page'];
		$args['per_page']            = $request['per_page'];
		$args['orderby']             = $request['orderby'];
		$args['order']               = $request['order'];
		$args['product_includes']    = (array) $request['products'];
		$args['extended_info']       = $request['extended_info'];
		$args['force_cache_refresh'] = $request['force_cache_refresh'];
		return $args;
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
		$response->add_links( $this->prepare_links( $report ) );

		/**
		 * Filter a stock notifications report item returned from the API.
		 *
		 * @since 11.3.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param array            $report   The original report item.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_report_stock_notifications', $response, $report, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param array $report Report data item.
	 * @return array
	 */
	protected function prepare_links( $report ) {
		return array(
			'product' => array(
				'href' => rest_url( sprintf( '/%s/products/%d', $this->namespace, $report['product_id'] ) ),
			),
		);
	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'report_stock_notifications',
			'type'       => 'object',
			'properties' => array(
				'product_id'     => array(
					'description' => __( 'ID of the product or variation customers signed up for.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'signups'        => array(
					'description' => __( 'Sign-ups created in the period. Excludes unconfirmed (pending) sign-ups.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'customers'      => array(
					'description' => __( 'Distinct email addresses behind the sign-ups in the period.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'active_signups' => array(
					'description' => __( 'Customers waiting for the product right now, whatever the period.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'days_waiting'   => array(
					'description' => __( 'Days since the oldest active sign-up was created.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'extended_info'  => array(
					'name'      => array(
						'type'        => 'string',
						'readonly'    => true,
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Product name.', 'woocommerce' ),
					),
					'permalink' => array(
						'type'        => 'string',
						'readonly'    => true,
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Product link.', 'woocommerce' ),
					),
					'edit_url'  => array(
						'type'        => 'string',
						'readonly'    => true,
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Product edit link.', 'woocommerce' ),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['orderby']['default'] = 'active_signups';
		$params['orderby']['enum']    = $this->apply_custom_orderby_filters(
			array(
				'product_id',
				'signups',
				'customers',
				'active_signups',
				'days_waiting',
			)
		);
		$params['products']           = array(
			'description'       => __( 'Limit result to items with specified product ids.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'integer',
			),
		);
		$params['extended_info']      = array(
			'description'       => __( 'Add additional piece of info about each product to the report.', 'woocommerce' ),
			'type'              => 'boolean',
			'default'           => false,
			'sanitize_callback' => 'wc_string_to_bool',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get the column names for export.
	 *
	 * @return array Key value pair of Column ID => Label.
	 */
	public function get_export_columns() {
		$export_columns = array(
			'product_name'   => __( 'Product', 'woocommerce' ),
			'active_signups' => __( 'Waiting', 'woocommerce' ),
			'signups'        => __( 'Sign-ups', 'woocommerce' ),
			'customers'      => __( 'Customers', 'woocommerce' ),
			'days_waiting'   => __( 'Days waiting', 'woocommerce' ),
		);

		/**
		 * Filter to add or remove column names from the stock notifications report for export.
		 *
		 * @since 11.3.0
		 *
		 * @param array $export_columns Key value pair of Column ID => Label.
		 */
		return apply_filters( 'woocommerce_report_stock_notifications_export_columns', $export_columns );
	}

	/**
	 * Get the column values for export.
	 *
	 * @param array $item Single report item/row.
	 * @return array Key value pair of Column ID => Row Value.
	 */
	public function prepare_item_for_export( $item ) {
		$export_item = array(
			'product_name'   => $item['extended_info']['name'] ?? '',
			'active_signups' => $item['active_signups'],
			'signups'        => $item['signups'],
			'customers'      => $item['customers'],
			'days_waiting'   => $item['days_waiting'],
		);

		/**
		 * Filter to prepare extra columns in the export item for the stock notifications report.
		 *
		 * @since 11.3.0
		 *
		 * @param array $export_item Key value pair of Column ID => Row Value.
		 * @param array $item        Single report item/row.
		 */
		return apply_filters( 'woocommerce_report_stock_notifications_prepare_export_item', $export_item, $item );
	}
}
