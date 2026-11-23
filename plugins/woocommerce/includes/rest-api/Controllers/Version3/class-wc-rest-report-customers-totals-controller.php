<?php
/**
 * REST API Reports Customers Totals controller
 *
 * Handles requests to the /reports/customers/count endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   3.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Reports Customers Totals controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Reports_Controller
 */
class WC_REST_Report_Customers_Totals_Controller extends WC_REST_Reports_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/customers/totals';

	/**
	 * Get reports list.
	 *
	 * @since 3.5.0
	 * @return array
	 */
	protected function get_reports() {
		global $wpdb;

		$users_count     = count_users();
		$total_customers = 0;

		foreach ( $users_count['avail_roles'] as $role => $total ) {
			if ( in_array( $role, array( 'administrator', 'shop_manager' ), true ) ) {
				continue;
			}

			$total_customers += (int) $total;
		}

		// Same cache group and invalidation signal WP_User_Query used, so meta written outside WooCommerce still refreshes the total.
		$cache_key    = 'wc_report_customers_totals_paying_' . get_current_blog_id() . '_' . wp_cache_get_last_changed( 'users' );
		$total_paying = wp_cache_get( $cache_key, 'user-queries' );

		if ( false === $total_paying ) {
			/*
			 * Let WP_User_Query build the role, capability and site scoping, then count with its
			 * clauses. Running the query itself selects and sorts every matching user ID only to
			 * throw them away: roughly 100KB of PHP memory per 1,000 paying customers.
			 */
			$customers_query = new WP_User_Query();
			$customers_query->prepare_query(
				array(
					'role__not_in' => array( 'administrator', 'shop_manager' ),
					'number'       => 0,
					'fields'       => 'ID',
					'count_total'  => true,
					'meta_query'   => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- The paying_customer flag lives only in usermeta, so the usermeta join is the only way to read it; the clauses are counted rather than materialised.
						array(
							'key'     => 'paying_customer',
							'value'   => 1,
							'compare' => '=',
						),
					),
				)
			);

			$total_paying = $wpdb->get_var( "SELECT COUNT(*) {$customers_query->query_from} {$customers_query->query_where}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Both clauses are built and escaped by WP_User_Query::prepare_query().

			// Never cache a failed count; a zeroed total would stick until the next user or user meta changed.
			if ( null !== $total_paying ) {
				$total_paying = (int) $total_paying;

				wp_cache_set( $cache_key, $total_paying, 'user-queries' );
			}
		}

		$total_paying = (int) $total_paying;

		$data = array(
			array(
				'slug'  => 'paying',
				'name'  => __( 'Paying customer', 'woocommerce' ),
				'total' => $total_paying,
			),
			array(
				'slug'  => 'non_paying',
				'name'  => __( 'Non-paying customer', 'woocommerce' ),
				'total' => $total_customers - $total_paying,
			),
		);

		return $data;
	}

	/**
	 * Prepare a report object for serialization.
	 *
	 * @param  stdClass        $report Report data.
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $report, $request ) {
		$data = array(
			'slug'  => $report->slug,
			'name'  => $report->name,
			'total' => $report->total,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		/**
		 * Filter a report returned from the API.
		 *
		 * Allows modification of the report data right before it is returned.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param object           $report   The original report object.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_report_customers_count', $response, $report, $request );
	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'report_customer_total',
			'type'       => 'object',
			'properties' => array(
				'slug'  => array(
					'description' => __( 'An alphanumeric identifier for the resource.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'name'  => array(
					'description' => __( 'Customer type name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'total' => array(
					'description' => __( 'Amount of customers.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
