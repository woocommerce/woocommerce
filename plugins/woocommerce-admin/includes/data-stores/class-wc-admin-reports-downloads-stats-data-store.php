<?php
/**
 * WC_Admin_Reports_Downloads_Data_Store class file.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Reports_Downloads_Data_Store.
 */
class WC_Admin_Reports_Downloads_Stats_Data_Store extends WC_Admin_Reports_Downloads_Data_Store implements WC_Admin_Reports_Data_Store_Interface {

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'download_count' => 'intval',
	);

	/**
	 * SQL columns to select in the db query and their mapping to SQL code.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'download_count' => 'COUNT(DISTINCT download_log_id) as download_count',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
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
			'orderby'  => 'date',
			'fields'   => '*',
			'interval' => 'week',
		);
		$query_args = wp_parse_args( $query_args, $defaults );

		if ( empty( $query_args['before'] ) ) {
			$query_args['before'] = date( WC_Admin_Reports_Interval::$iso_datetime_format, $now );
		}
		if ( empty( $query_args['after'] ) ) {
			$query_args['after'] = date( WC_Admin_Reports_Interval::$iso_datetime_format, $week_back );
		}

		$cache_key = $this->get_cache_key( $query_args );
		$data      = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $data ) {
			$selections       = $this->selected_columns( $query_args );
			$sql_query_params = $this->get_sql_query_params( $query_args );
			$totals_query     = array_merge( array(), $this->get_time_period_sql_params( $query_args, $table_name ) );
			$intervals_query  = array_merge( array(), $this->get_intervals_sql_params( $query_args, $table_name ) );

			$totals_query['where_clause']        .= $sql_query_params['where_clause'];
			$totals_query['from_clause']         .= $sql_query_params['from_clause'];
			$intervals_query['where_clause']     .= $sql_query_params['where_clause'];
			$intervals_query['from_clause']      .= $sql_query_params['from_clause'];
			$intervals_query['select_clause']     = str_replace( 'date_created', 'timestamp', $intervals_query['select_clause'] );
			$intervals_query['where_time_clause'] = str_replace( 'date_created', 'timestamp', $intervals_query['where_time_clause'] );

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
			); // WPCS: cache ok, DB call ok, , unprepared SQL ok.

			$db_records_count = count( $db_intervals );

			$expected_interval_count = WC_Admin_Reports_Interval::intervals_between( $query_args['after'], $query_args['before'], $query_args['interval'] );
			$total_pages             = (int) ceil( $expected_interval_count / $intervals_query['per_page'] );
			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				return array();
			}

			$this->update_intervals_sql_params( $intervals_query, $query_args, $db_records_count, $expected_interval_count, $table_name );
			$intervals_query['where_time_clause'] = str_replace( 'date_created', 'timestamp', $intervals_query['where_time_clause'] );

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
				return new WP_Error( 'woocommerce_reports_downloads_stats_result_failed', __( 'Sorry, fetching downloads data failed.', 'wc-admin' ) );
			}

			if ( '' !== $selections ) {
				$selections = ', ' . $selections;
			}

			$intervals = $wpdb->get_results(
				"SELECT
					MAX(timestamp) AS datetime_anchor,
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
				return new WP_Error( 'woocommerce_reports_downloads_stats_result_failed', __( 'Sorry, fetching downloads data failed.', 'wc-admin' ) );
			}

			$totals = (object) $this->cast_numbers( $totals[0] );
			$data   = (object) array(
				'totals'    => $totals,
				'intervals' => $intervals,
				'total'     => $expected_interval_count,
				'pages'     => $total_pages,
				'page_no'   => (int) $query_args['page'],
			);

			if ( $this->intervals_missing( $expected_interval_count, $db_records_count, $intervals_query['per_page'], $query_args['page'], $query_args['order'], $query_args['orderby'], count( $intervals ) ) ) {
				$this->fill_in_missing_intervals( $db_intervals, $query_args['adj_after'], $query_args['adj_before'], $query_args['interval'], $data );
				$this->sort_intervals( $data, $query_args['orderby'], $query_args['order'] );
				$this->remove_extra_records( $data, $query_args['page'], $intervals_query['per_page'], $db_records_count, $expected_interval_count, $query_args['orderby'] );
			} else {
				$this->update_interval_boundary_dates( $query_args['after'], $query_args['before'], $query_args['interval'], $data->intervals );
			}
			$this->create_interval_subtotals( $data->intervals );

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

	/**
	 * Sorts intervals according to user's request.
	 *
	 * They are pre-sorted in SQL, but after adding gaps, they need to be sorted including the added ones.
	 *
	 * @param stdClass $data      Data object, must contain an array under $data->intervals.
	 * @param string   $sort_by   Ordering property.
	 * @param string   $direction DESC/ASC.
	 */
	protected function sort_intervals( &$data, $sort_by, $direction ) {
		if ( 'date' === $sort_by ) {
			$this->order_by = 'time_interval';
		} else {
			$this->order_by = $sort_by;
		}

		$this->order    = $direction;
		usort( $data->intervals, array( $this, 'interval_cmp' ) );
	}

	/**
	 * Compares two report data objects by pre-defined object property and ASC/DESC ordering.
	 *
	 * @param stdClass $a Object a.
	 * @param stdClass $b Object b.
	 * @return string
	 */
	protected function interval_cmp( $a, $b ) {
		if ( '' === $this->order_by || '' === $this->order ) {
			return 0;
		}
		if ( $a[ $this->order_by ] === $b[ $this->order_by ] ) {
			return 0;
		} elseif ( $a[ $this->order_by ] > $b[ $this->order_by ] ) {
			return strtolower( $this->order ) === 'desc' ? -1 : 1;
		} elseif ( $a[ $this->order_by ] < $b[ $this->order_by ] ) {
			return strtolower( $this->order ) === 'desc' ? 1 : -1;
		}
	}

}
