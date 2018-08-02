<?php
/**
 * WC_Reports_Revenue_Store class file.
 *
 * @package WooCommerce/Classes
 * @since 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Product Data Store: Stored in CPT.
 * //extends WC_Data_Store_WP.
 *
 * @version  3.5.0
 */
class WC_Reports_Data_Store {

	/**
	 * Cache group for the reports.
	 *
	 * @var string
	 */
	protected $cache_group = 'reports';

	/**
	 * Time out for the cache.
	 *
	 * @var int
	 */
	protected $cache_timeout = 3600;

	/**
	 * Table used as a data store for this report.
	 *
	 * @var string
	 */
	protected $table_name = '';

	/**
	 * Returns string to be used as cache key for the data.
	 *
	 * @param array $params Query parameters.
	 *
	 * @return string
	 */
	protected function get_cache_key( $params ) {
		return 'woocommerce_' . $this->table_name . '_' . md5( wp_json_encode( $params ) . date( 'Y-m-d_H' ) );
	}

	/**
	 * Normalizes order_by clause to match to SQL query.
	 *
	 * @param string $order_by Order by option requeste by user.
	 *
	 * @return string
	 */
	protected function normalize_order_by( $order_by ) {
		if ( 'date' === $order_by ) {
			return 'time_interval';
		}

		return $order_by;
	}

	/**
	 * Updates start and end dates for intervals so that they represent intervals' borders, not times when data in db were recorded.
	 *
	 * E.g. if there are db records for only Tuesday and Thursday this week, the actual week interval is [Mon, Sun], not [Tue, Thu].
	 *
	 * @param DateTime $datetime_start Start date.
	 * @param DateTime $datetime_end End date.
	 * @param string   $time_interval Time interval, e.g. day, week, month.
	 * @param stdClass $data Data with SQL extracted intervals.
	 *
	 * @return stdClass
	 */
	protected function update_interval_boundary_dates( $datetime_start, $datetime_end, $time_interval, &$data ) {
		$end_datetime = new DateTime( $datetime_end );
		$time_ids     = array_flip( wp_list_pluck( $data->intervals, 'time_interval' ) );
		$datetime     = new DateTime( $datetime_start );
		while ( $datetime <= $end_datetime ) {
			$next_end = WC_Reports_Interval::iterate( $datetime, $time_interval );

			$time_id      = WC_Reports_Interval::time_interval_id( $time_interval, $datetime );
			$interval_end = ( $next_end > $end_datetime ? $end_datetime : $next_end )->format( 'Y-m-d H:i:s' );
			if ( array_key_exists( $time_id, $time_ids ) ) {
				$record             = $data->intervals[ $time_ids[ $time_id ] ];
				$record->date_start = $datetime->format( 'Y-m-d H:i:s' );
				$record->date_end   = $interval_end;
			}

			$datetime = $next_end;
		}

		return $data;
	}

	/**
	 * Fills where clause of SQL request for 'Totals' section of data response based on user supplied parameters.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 *
	 * @return array
	 */
	protected function get_totals_sql_params( $query_args ) {
		$totals_query['where_clause'] = '';

		if ( isset( $query_args['before'] ) && '' !== $query_args['before'] ) {
			$datetime                     = new DateTime( $query_args['before'] );
			$datetime_str                 = $datetime->format( WC_Reports_Interval::$iso_datetime_format );
			$totals_query['where_clause'] .= " AND start_time <= '$datetime_str'";

		}

		if ( isset( $query_args['after'] ) && '' !== $query_args['after'] ) {
			$datetime                     = new DateTime( $query_args['after'] );
			$datetime_str                 = $datetime->format( WC_Reports_Interval::$iso_datetime_format );
			$totals_query['where_clause'] .= " AND start_time >= '$datetime_str'";
		}

		return $totals_query;
	}

	/**
	 * Fills clauses of SQL request for 'Intervals' section of data response based on user supplied parameters.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 *
	 * @return array
	 */
	protected function get_intervals_sql_params( $query_args ) {
		$intervals_query                 = array();
		$intervals_query['where_clause'] = '';

		if ( isset( $query_args['before'] ) && '' !== $query_args['before'] ) {
			$datetime                        = new DateTime( $query_args['before'] );
			$datetime_str                    = $datetime->format( WC_Reports_Interval::$iso_datetime_format );
			$intervals_query['where_clause'] .= " AND start_time <= '$datetime_str'";

		}

		if ( isset( $query_args['after'] ) && '' !== $query_args['after'] ) {
			$datetime                        = new DateTime( $query_args['after'] );
			$datetime_str                    = $datetime->format( WC_Reports_Interval::$iso_datetime_format );
			$intervals_query['where_clause'] .= " AND start_time >= '$datetime_str'";
		}

		if ( isset( $query_args['interval'] ) && '' !== $query_args['interval'] ) {
			$interval                         = $query_args['interval'];
			$intervals_query['select_clause'] = WC_Reports_Interval::mysql_datetime_format( $interval );
		}

		$intervals_query['per_page'] = get_option( 'posts_per_page' );
		if ( isset( $query_args['per_page'] ) && is_numeric( $query_args['per_page'] ) ) {
			$intervals_query['per_page'] = (int) $query_args['per_page'];
		}

		$intervals_query['offset'] = 0;
		if ( isset( $query_args['page'] ) ) {
			$intervals_query['offset'] = ( (int) $query_args['page'] - 1 ) * $intervals_query['per_page'];
		}

		$intervals_query['limit'] = "LIMIT {$intervals_query['offset']}, {$intervals_query['per_page']}";

		$intervals_query['order_by_clause'] = '';
		if ( isset( $query_args['orderby'] ) ) {
			$intervals_query['order_by_clause'] = $this->normalize_order_by( $query_args['orderby'] );
		}

		if ( isset( $query_args['order'] ) ) {
			$intervals_query['order_by_clause'] .= ' ' . $query_args['order'];
		} else {
			$intervals_query['order_by_clause'] .= ' DESC';
		}

		return $intervals_query;
	}

}
