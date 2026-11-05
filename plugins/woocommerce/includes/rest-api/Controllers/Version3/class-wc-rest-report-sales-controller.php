<?php
/**
 * REST API Reports controller
 *
 * Handles requests to the reports/sales endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Report Sales controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Report_Sales_V2_Controller
 */
class WC_REST_Report_Sales_Controller extends WC_REST_Report_Sales_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Prepare a report sales object for serialization.
	 *
	 * Extends the v2 response with a per-period `refunds` field inside each
	 * `totals[date]` bucket so consumers can compute net sales per period
	 * without a second request. Top-level `total_refunds` and per-period
	 * `sales` semantics are unchanged.
	 *
	 * @param null                                  $_       Unused.
	 * @param WP_REST_Request<array<string, mixed>> $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $_, $request ) {
		$response = parent::prepare_item_for_response( $_, $request );
		$data     = $response->get_data();

		if ( ! isset( $data['totals'] ) || ! is_array( $data['totals'] ) ) {
			return $response;
		}

		// Initialise the refunds bucket on every period so consumers get a
		// stable shape (decimal string) even on periods with no refunds.
		foreach ( $data['totals'] as $time => $bucket ) {
			$data['totals'][ $time ]['refunds'] = wc_format_decimal( 0.00, 2 );
		}

		// `$this->report` is a WC_Report_Sales_By_Date (the v1 base annotates
		// it as the abstract WC_Admin_Report). Annotate locally so the call to
		// the concrete `get_report_data()` typechecks.
		/** @var WC_Report_Sales_By_Date $report */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort
		$report      = $this->report;
		$report_data = $report->get_report_data();
		if ( ! empty( $report_data->refund_lines ) ) {
			foreach ( $report_data->refund_lines as $refund ) {
				// Match the bucket key format used by the parent's sales /
				// orders / items / coupons loops (local time, not UTC) so
				// refunds line up with their corresponding sales row.
				// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- Match adjacent loops in v1 base controller.
				$time = ( 'day' === $this->report->chart_groupby ) ? date( 'Y-m-d', strtotime( $refund->post_date ) ) : date( 'Y-m', strtotime( $refund->post_date ) );

				if ( ! isset( $data['totals'][ $time ] ) ) {
					continue;
				}

				$data['totals'][ $time ]['refunds'] = wc_format_decimal( (float) $data['totals'][ $time ]['refunds'] + (float) $refund->total_refund, 2 );
			}
		}

		$response->set_data( $data );
		return $response;
	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * Extends the v2 schema with the per-period `refunds` field and replaces
	 * the previously incorrect `totals` typing (`array` of `array`) with the
	 * actual object-of-objects shape so the schema reflects reality.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$schema['properties']['totals'] = array(
			'description'          => __( 'Totals.', 'woocommerce' ),
			'type'                 => 'object',
			'context'              => array( 'view' ),
			'readonly'             => true,
			'additionalProperties' => array(
				'type'       => 'object',
				'properties' => array(
					'sales'     => array(
						'description' => __( 'Gross sales in the period.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'orders'    => array(
						'description' => __( 'Number of orders in the period.', 'woocommerce' ),
						'type'        => 'integer',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'items'     => array(
						'description' => __( 'Number of items sold in the period.', 'woocommerce' ),
						'type'        => 'integer',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'tax'       => array(
						'description' => __( 'Tax charged in the period.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'shipping'  => array(
						'description' => __( 'Shipping charged in the period.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'discount'  => array(
						'description' => __( 'Discounts applied in the period.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'refunds'   => array(
						'description' => __( 'Refunds issued in the period.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'customers' => array(
						'description' => __( 'New customers in the period.', 'woocommerce' ),
						'type'        => 'integer',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
