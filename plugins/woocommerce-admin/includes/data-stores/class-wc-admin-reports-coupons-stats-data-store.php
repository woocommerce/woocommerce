<?php
/**
 * WC_Admin_Reports_Coupons_Stats_Data_Store class file.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;


/**
 * WC_Reports_Coupons_Stats_Data_Store.
 */
class WC_Admin_Reports_Coupons_Stats_Data_Store extends WC_Admin_Reports_Coupons_Data_Store implements WC_Admin_Reports_Data_Store_Interface {

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'date_start'     => 'strval',
		'date_end'       => 'strval',
		'date_start_gmt' => 'strval',
		'date_end_gmt'   => 'strval',
		'gross_discount' => 'floatval',
		'coupons_count'  => 'intval',
		'orders_count'   => 'intval',
	);

	/**
	 * SQL columns to select in the db query.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'gross_discount' => 'SUM(coupon_gross_discount) as gross_discount',
		'coupons_count'  => 'COUNT(DISTINCT coupon_id) as products_count',
		'orders_count'   => 'COUNT(DISTINCT order_id) as orders_count',
	);

	/**
	 * Updates the database query with parameters used for Products Stats report: categories and order status.
	 *
	 * @param array $query_args       Query arguments supplied by the user.
	 * @param array $totals_params    SQL parameters for the totals query.
	 * @param array $intervals_params SQL parameters for the intervals query.
	 */
	protected function update_sql_query_params( $query_args, &$totals_params, &$intervals_params ) {
		global $wpdb;

		$coupons_where_clause = '';
		$coupons_from_clause  = '';

		$order_coupon_lookup_table = $wpdb->prefix . self::TABLE_NAME;

		$included_coupons = $this->get_included_coupons( $query_args );
		if ( $included_coupons ) {
			$coupons_where_clause .= " AND {$order_coupon_lookup_table}.coupon_id IN ({$included_coupons})";
		}

		$order_status_filter = $this->get_status_subquery( $query_args );
		if ( $order_status_filter ) {
			$coupons_from_clause  .= " JOIN {$wpdb->prefix}posts ON {$order_coupon_lookup_table}.order_id = {$wpdb->prefix}posts.ID";
			$coupons_where_clause .= " AND ( {$order_status_filter} )";
		}

		$totals_params                  = array_merge( $totals_params, $this->get_time_period_sql_params( $query_args, $order_coupon_lookup_table ) );
		$totals_params['where_clause'] .= $coupons_where_clause;
		$totals_params['from_clause']  .= $coupons_from_clause;

		$intervals_params                  = array_merge( $intervals_params, $this->get_intervals_sql_params( $query_args, $order_coupon_lookup_table ) );
		$intervals_params['where_clause'] .= $coupons_where_clause;
		$intervals_params['from_clause']  .= $coupons_from_clause;
	}

	/**
	 * Returns the report data based on parameters supplied by the user.
	 *
	 * @since 3.5.0
	 * @param array $query_args  Query parameters.
	 * @return stdClass|WP_Error Data.
	 */
	public function get_data( $query_args ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$now        = time();
		$week_back  = $now - WEEK_IN_SECONDS;

		// These defaults are only partially applied when used via REST API, as that has its own defaults.
		$defaults = array(
			'per_page'     => get_option( 'posts_per_page' ),
			'page'         => 1,
			'order'        => 'DESC',
			'orderby'      => 'date',
			'before'       => date( WC_Admin_Reports_Interval::$iso_datetime_format, $now ),
			'after'        => date( WC_Admin_Reports_Interval::$iso_datetime_format, $week_back ),
			'fields'       => '*',
			'interval'     => 'week',
			'code'         => array(),
			// This is not a parameter for products reports per se, but we should probably restricts order statuses here, too.
			'order_status' => parent::get_report_order_statuses(),
		);
		$query_args = wp_parse_args( $query_args, $defaults );

		$cache_key = $this->get_cache_key( $query_args );
		$data      = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $data ) {
			$data = (object) array(
				'data'    => array(),
				'total'   => 0,
				'pages'   => 0,
				'page_no' => 0,
			);

			$selections      = $this->selected_columns( $query_args );
			$totals_query    = array();
			$intervals_query = array();
			$this->update_sql_query_params( $query_args, $totals_query, $intervals_query );

			$db_intervals = $wpdb->get_col(
				"SELECT
							{$intervals_query['select_clause']} AS time_interval
						FROM
							{$table_name}
							{$intervals_query['from_clause']}
						WHERE
							1=1
							{$intervals_query['where_time_clause']}
							{$intervals_query['where_clause']}
						GROUP BY
							time_interval"
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			$db_interval_count = count( $db_intervals );
			$total_pages       = (int) ceil( $db_interval_count / $intervals_query['per_page'] );
			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				return $data;
			}

			$totals = $wpdb->get_results(
				"SELECT
						{$selections}
					FROM
						{$table_name}
						{$totals_query['from_clause']}
					WHERE
						1=1
						{$totals_query['where_time_clause']}
						{$totals_query['where_clause']}",
				ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			if ( null === $totals ) {
				return $data;
			}

			$expected_interval_count = WC_Admin_Reports_Interval::intervals_between( $query_args['after'], $query_args['before'], $query_args['interval'] );
			$this->update_intervals_sql_params( $intervals_query, $query_args, $db_interval_count, $expected_interval_count );

			if ( '' !== $selections ) {
				$selections = ', ' . $selections;
			}

			$intervals = $wpdb->get_results(
				"SELECT
							MAX(date_created) AS datetime_anchor,
							{$intervals_query['select_clause']} AS time_interval
							{$selections}
						FROM
							{$table_name}
							{$intervals_query['from_clause']}
						WHERE
							1=1
							{$intervals_query['where_time_clause']}
							{$intervals_query['where_clause']}
						GROUP BY
							time_interval
						ORDER BY
							{$intervals_query['order_by_clause']}
						{$intervals_query['limit']}",
				ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			if ( null === $intervals ) {
				return $data;
			}

			$totals = (object) $this->cast_numbers( $totals[0] );

			if ( WC_Admin_Reports_Interval::intervals_missing( $expected_interval_count, $db_interval_count, $intervals_query['per_page'], $query_args['page'], $query_args['order'], $query_args['orderby'], count( $intervals ) ) ) {
				$this->fill_in_missing_intervals( $db_intervals, $query_args['adj_after'], $query_args['adj_before'], $query_args['interval'], $data );
				$this->sort_intervals( $data, $query_args['orderby'], $query_args['order'] );
				$this->remove_extra_records( $data, $query_args['page'], $intervals_query['per_page'], $db_interval_count, $expected_interval_count, $query_args['orderby'] );
			} else {
				$this->update_interval_boundary_dates( $query_args['after'], $query_args['before'], $query_args['interval'], $data->intervals );
			}
			$this->create_interval_subtotals( $data->intervals );

			$data = (object) array(
				'data'    => $data,
				'total'   => $expected_interval_count,
				'pages'   => $total_pages,
				'page_no' => (int) $query_args['page'],
			);

			wp_cache_set( $cache_key, $data, $this->cache_group );
		}

		return $data;
	}

}
