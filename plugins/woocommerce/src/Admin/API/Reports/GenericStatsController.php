<?php
namespace Automattic\WooCommerce\Admin\API\Reports;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\GenericController;
use WP_Error;

/**
 * Generic base for all stats controllers.
 *
 * {@see GenericController Generic Controller} extended to be shared as a generic base for all Analytics stats controllers.
 *
 * Besides the `GenericController` functionality, it adds conventional stats-specific collection params and item schema.
 * So, you may want to extend only your report-specific {@see get_item_properties_schema() get_item_properties_schema()}`.
 * It also uses the stats-specific {@see get_items() get_items()} method,
 * which packs report data into `totals` and `intervals`.
 *
 *
 * Minimalistic example:
 * <pre><code class="language-php">class StatsController extends GenericStatsController {
 *     /** Route of your new REST endpoint. &ast;/
 *     protected $rest_base = 'reports/my-thing/stats';
 *     /** Define your proeprties schema. &ast;/
 *     protected function get_item_properties_schema() {
 *         return array(
 *             'my_property' => array(
 *                 'title'       => __( 'My property', 'my-extension' ),
 *                 'type'        => 'integer',
 *                 'readonly'    => true,
 *                 'context'     => array( 'view', 'edit' ),
 *                 'description' => __( 'Amazing thing.', 'my-extension' ),
 *                 'indicator'    => true,
 *              ),
 *         );
 *     }
 *     /** Define overall schema. You can use the defaults,
 *      * just remember to provide your title and call `add_additional_fields_schema`
 *      * to run the filters
 *      &ast;/
 *     public function get_item_schema() {
 *         $schema          = parent::get_item_schema();
 *         $schema['title'] = 'report_my_thing_stats';
 *
 *        return $this->add_additional_fields_schema( $schema );
 *     }
 * }
 * </code></pre>
 *
 * @extends GenericController
 */
abstract class GenericStatsController extends GenericController {

	/**
	 * Get the query params definition for collections.
	 * Adds `fields` & `intervals` to the generic list.
	 *
	 * @override GenericController::get_collection_params()
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params             = parent::get_collection_params();
		$params['fields']   = array(
			'description'       => __( 'Limit stats fields to the specified items.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_slug_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'string',
			),
		);
		$params['interval'] = array(
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

	/**
	 * Get the report's item properties schema.
	 * Will be used by `get_item_schema` as `totals` and `subtotals`.
	 *
	 * @return array
	 */
	abstract protected function get_item_properties_schema();

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * Please note that it does not call add_additional_fields_schema,
	 * as you may want to update the `title` first.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$data_values = $this->get_item_properties_schema();

		$segments = array(
			'segments' => array(
				'description' => __( 'Reports data grouped by segment condition.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'segment_id' => array(
							'description' => __( 'Segment identificator.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'subtotals'  => array(
							'description' => __( 'Interval subtotals.', 'woocommerce' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => $data_values,
						),
					),
				),
			),
		);

		$totals = array_merge( $data_values, $segments );

		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'report_stats',
			'type'       => 'object',
			'properties' => array(
				'totals'    => array(
					'description' => __( 'Totals data.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => $totals,
				),
				'intervals' => array(
					'description' => __( 'Reports data grouped by intervals.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'interval'       => array(
								'description' => __( 'Type of interval.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
								'enum'        => array( 'day', 'week', 'month', 'year' ),
							),
							'date_start'     => array(
								'description' => __( "The date the report start, in the site's timezone.", 'woocommerce' ),
								'type'        => 'string',
								'format'      => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'date_start_gmt' => array(
								'description' => __( 'The date the report start, as GMT.', 'woocommerce' ),
								'type'        => 'string',
								'format'      => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'date_end'       => array(
								'description' => __( "The date the report end, in the site's timezone.", 'woocommerce' ),
								'type'        => 'string',
								'format'      => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'date_end_gmt'   => array(
								'description' => __( 'The date the report end, as GMT.', 'woocommerce' ),
								'type'        => 'string',
								'format'      => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'subtotals'      => array(
								'description' => __( 'Interval subtotals.', 'woocommerce' ),
								'type'        => 'object',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
								'properties'  => $totals,
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Get the report data.
	 *
	 * Prepares query params, fetches the report data from the data store,
	 * prepares it for the response, and packs it into the convention-conforming response object.
	 *
	 * @override GenericController::get_items()
	 *
	 * @throws \WP_Error When the queried data is invalid.
	 * @param \WP_REST_Request $request Request data.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items( $request ) {
		$query_args = $this->prepare_reports_query( $request );
		try {
			$report_data = $this->get_datastore_data( $query_args );
		} catch ( ParameterException $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		$out_data = array(
			'totals'    => $report_data->totals ? get_object_vars( $report_data->totals ) : null,
			'intervals' => array(),
		);

		foreach ( $report_data->intervals as $interval_data ) {
			$item                    = $this->prepare_item_for_response( $interval_data, $request );
			$out_data['intervals'][] = $this->prepare_response_for_collection( $item );
		}

		return $this->add_pagination_headers(
			$request,
			$out_data,
			(int) $report_data->total,
			(int) $report_data->page_no,
			(int) $report_data->pages
		);
	}
}
