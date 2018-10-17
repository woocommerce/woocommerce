<?php
/**
 * WC_Admin_Reports_Products_Stats_Data_Store class file.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;


/**
 * WC_Reports_Products_Stats_Data_Store.
 */
class WC_Admin_Reports_Products_Stats_Data_Store extends WC_Admin_Reports_Products_Data_Store implements WC_Admin_Reports_Data_Store_Interface {

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'date_start'     => 'strval',
		'date_end'       => 'strval',
		'product_id'     => 'intval',
		'items_sold'     => 'intval',
		'gross_revenue'  => 'floatval',
		'orders_count'   => 'intval',
		'products_count' => 'intval',
	);

	/**
	 * SQL columns to select in the db query.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'items_sold'     => 'SUM(product_qty) as items_sold',
		'gross_revenue'  => 'SUM(product_gross_revenue) AS gross_revenue',
		'orders_count'   => 'COUNT(DISTINCT order_id) as orders_count',
		'products_count' => 'COUNT(DISTINCT product_id) as products_count',
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

		$allowed_products      = $this->get_allowed_products( $query_args );
		$products_where_clause = '';
		$products_from_clause  = '';

		$order_product_lookup_table = $wpdb->prefix . self::TABLE_NAME;
		if ( count( $allowed_products ) > 0 ) {
			$allowed_products_str   = implode( ',', $allowed_products );
			$products_where_clause .= " AND {$order_product_lookup_table}.product_id IN ({$allowed_products_str})";
		}

		if ( is_array( $query_args['order_status'] ) && count( $query_args['order_status'] ) > 0 ) {
			$statuses = array_map( array( $this, 'normalize_order_status' ), $query_args['order_status'] );

			$products_from_clause  .= " JOIN {$wpdb->prefix}posts ON {$order_product_lookup_table}.order_id = {$wpdb->prefix}posts.ID";
			$products_where_clause .= " AND {$wpdb->prefix}posts.post_status IN ( '" . implode( "','", $statuses ) . "' ) ";
		}

		$totals_params                  = array_merge( $totals_params, $this->get_time_period_sql_params( $query_args ) );
		$totals_params['where_clause'] .= $products_where_clause;
		$totals_params['from_clause']  .= $products_from_clause;

		$intervals_params                  = array_merge( $intervals_params, $this->get_intervals_sql_params( $query_args ) );
		$intervals_params['where_clause'] .= $products_where_clause;
		$intervals_params['from_clause']  .= $products_from_clause;
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
		$defaults   = array(
			'per_page'     => get_option( 'posts_per_page' ),
			'page'         => 1,
			'order'        => 'DESC',
			'orderby'      => 'date',
			'before'       => date( WC_Admin_Reports_Interval::$iso_datetime_format, $now ),
			'after'        => date( WC_Admin_Reports_Interval::$iso_datetime_format, $week_back ),
			'fields'       => '*',
			'categories'   => array(),
			'interval'     => 'week',
			'products'     => array(),
			// This is not a parameter for products reports per se, but we should probably restricts order statuses here, too.
			'order_status' => parent::get_report_order_statuses(),
		);
		$query_args = wp_parse_args( $query_args, $defaults );

		$cache_key    = $this->get_cache_key( $query_args );
		$product_data = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $product_data ) {
			$selections      = $this->selected_columns( $query_args );
			$totals_query    = array();
			$intervals_query = array();
			$this->update_sql_query_params( $query_args, $totals_query, $intervals_query );

			$db_records_count = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM (
							SELECT
								{$intervals_query['select_clause']} AS time_interval
							FROM
								{$table_name}
								{$intervals_query['from_clause']}
							WHERE
								1=1
								{$intervals_query['where_clause']}
							GROUP BY
								time_interval
					  		) AS t"
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			$total_pages = (int) ceil( $db_records_count / $intervals_query['per_page'] );
			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				return array();
			}

			$totals = $wpdb->get_results(
				"SELECT
						{$selections}
					FROM
						{$table_name}
						{$totals_query['from_clause']}
					WHERE
						1=1
						{$totals_query['where_clause']}", ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			if ( null === $totals ) {
				return new WP_Error( 'woocommerce_reports_products_stats_result_failed', __( 'Sorry, fetching revenue data failed.', 'wc-admin' ) );
			}

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
							{$intervals_query['where_clause']}
						GROUP BY
							time_interval
						ORDER BY
							{$intervals_query['order_by_clause']}
						{$intervals_query['limit']}", ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			if ( null === $intervals ) {
				return new WP_Error( 'woocommerce_reports_products_stats_result_failed', __( 'Sorry, fetching revenue data failed.', 'wc-admin' ) );
			}

			$totals = (object) $this->cast_numbers( $totals[0] );

			$this->update_interval_boundary_dates( $query_args['after'], $query_args['before'], $query_args['interval'], $intervals );
			$this->create_interval_subtotals( $intervals );

			$data = (object) array(
				'totals'    => $totals,
				'intervals' => $intervals,
				'total'     => $db_records_count,
				'pages'     => $total_pages,
				'page_no'   => (int) $query_args['page'],
			);

			wp_cache_set( $cache_key, $data, $this->cache_group );
		}

		return $data;
	}

}
