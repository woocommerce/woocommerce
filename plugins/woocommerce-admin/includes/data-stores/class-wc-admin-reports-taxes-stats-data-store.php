<?php
/**
 * WC_Admin_Reports_Taxes_Stats_Data_Store class file.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;


/**
 * WC_Reports_Taxes_Stats_Data_Store.
 */
class WC_Admin_Reports_Taxes_Stats_Data_Store extends WC_Admin_Reports_Data_Store implements WC_Admin_Reports_Data_Store_Interface {

	/**
	 * Table used to get the data.
	 *
	 * @var string
	 */
	const TABLE_NAME = 'wc_order_tax_lookup';

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'tax_codes'    => 'intval',
		'total_tax'    => 'floatval',
		'order_tax'    => 'floatval',
		'shipping_tax' => 'floatval',
		'order_count'  => 'intval',
	);

	/**
	 * SQL columns to select in the db query.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'tax_codes'    => 'COUNT(DISTINCT tax_rate_id) as tax_codes',
		'total_tax'    => 'SUM(total_tax) AS total_tax',
		'order_tax'    => 'SUM(order_tax) as order_tax',
		'shipping_tax' => 'SUM(shipping_tax) as shipping_tax',
		'order_count'  => 'COUNT(DISTINCT order_id) as orders',
	);

	/**
	 * Updates the database query with parameters used for Taxes report: categories and order status.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 * @return array            Array of parameters used for SQL query.
	 */
	protected function get_sql_query_params( $query_args ) {
		global $wpdb;

		$order_tax_lookup_table = $wpdb->prefix . self::TABLE_NAME;

		$sql_query_params = $this->get_time_period_sql_params( $query_args, $order_tax_lookup_table );
		$sql_query_params = array_merge( $sql_query_params, $this->get_limit_sql_params( $query_args ) );
		$sql_query_params = array_merge( $sql_query_params, $this->get_order_by_sql_params( $query_args ) );

		if ( isset( $query_args['taxes'] ) && ! empty( $query_args['taxes'] ) ) {
			$allowed_taxes                     = implode( ',', $query_args['taxes'] );
			$sql_query_params['where_clause'] .= " AND {$order_tax_lookup_table}.tax_rate_id IN ({$allowed_taxes})";
		}

		return $sql_query_params;
	}

	/**
	 * Updates the database query with parameters used for Taxes Stats report
	 *
	 * @param array $query_args       Query arguments supplied by the user.
	 * @param array $totals_params    SQL parameters for the totals query.
	 * @param array $intervals_params SQL parameters for the intervals query.
	 */
	protected function update_sql_query_params( $query_args, &$totals_params, &$intervals_params ) {
		global $wpdb;

		$taxes_where_clause = '';
		$taxes_from_clause  = '';

		$order_tax_lookup_table = $wpdb->prefix . self::TABLE_NAME;

		if ( isset( $query_args['taxes'] ) && ! empty( $query_args['taxes'] ) ) {
			$allowed_taxes       = implode( ',', $query_args['taxes'] );
			$taxes_where_clause .= " AND {$order_tax_lookup_table}.tax_rate_id IN ({$allowed_taxes})";
		}

		$totals_params                  = array_merge( $totals_params, $this->get_time_period_sql_params( $query_args, $order_tax_lookup_table ) );
		$totals_params['where_clause'] .= $taxes_where_clause;

		$intervals_params                  = array_merge( $intervals_params, $this->get_intervals_sql_params( $query_args, $order_tax_lookup_table ) );
		$intervals_params['where_clause'] .= $taxes_where_clause;
	}

	/**
	 * Returns the report data based on parameters supplied by the user.
	 *
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
			'per_page' => get_option( 'posts_per_page' ),
			'page'     => 1,
			'order'    => 'DESC',
			'orderby'  => 'tax_rate_id',
			'before'   => date( WC_Admin_Reports_Interval::$iso_datetime_format, $now ),
			'after'    => date( WC_Admin_Reports_Interval::$iso_datetime_format, $week_back ),
			'fields'   => '*',
			'taxes'    => array(),
		);
		$query_args = wp_parse_args( $query_args, $defaults );

		$cache_key = $this->get_cache_key( $query_args );
		$data      = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $data ) {
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
								{$intervals_query['where_time_clause']}
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
						{$totals_query['where_time_clause']}
						{$totals_query['where_clause']}",
				ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			if ( null === $totals ) {
				return new WP_Error( 'woocommerce_reports_taxes_stats_result_failed', __( 'Sorry, fetching revenue data failed.', 'wc-admin' ) );
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
				return new WP_Error( 'woocommerce_reports_taxes_stats_result_failed', __( 'Sorry, fetching revenue data failed.', 'wc-admin' ) );
			}

			$totals = (object) $this->cast_numbers( $totals[0] );
			$data     = (object) array(
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

	/**
	 * Returns string to be used as cache key for the data.
	 *
	 * @param array $params Query parameters.
	 * @return string
	 */
	protected function get_cache_key( $params ) {
		return 'woocommerce_' . self::TABLE_NAME . '_stats_' . md5( wp_json_encode( $params ) );
	}

}
