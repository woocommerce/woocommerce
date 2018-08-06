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
	const TABLE_NAME = '';

	/**
	 * Returns string to be used as cache key for the data.
	 *
	 * @param array $params Query parameters.
	 * @return string
	 */
	protected function get_cache_key( $params ) {
		return 'woocommerce_' . $this::TABLE_NAME . '_' . md5( wp_json_encode( $params ) );
	}

	/**
	 * Normalizes order_by clause to match to SQL query.
	 *
	 * @param string $order_by Order by option requeste by user.
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
	 * @param array    $intervals Array of intervals extracted from SQL db.
	 */
	protected function update_interval_boundary_dates( $datetime_start, $datetime_end, $time_interval, &$intervals ) {
		foreach ( $intervals as $key => $interval ) {
			$datetime = new DateTime( $interval['datetime_anchor'] );
			$one_sec  = new DateInterval( 'PT1S' );

			$prev_start = WC_Reports_Interval::iterate( $datetime, $time_interval, true );
			$prev_start->add( $one_sec );
			if ( $datetime_start ) {
				$start_datetime                  = new DateTime( $datetime_start );
				$intervals[ $key ]['date_start'] = ( $prev_start < $start_datetime ? $start_datetime : $prev_start )->format( 'Y-m-d H:i:s' );
			} else {
				$intervals[ $key ]['date_start'] = $prev_start->format( 'Y-m-d H:i:s' );
			}

			$next_end = WC_Reports_Interval::iterate( $datetime, $time_interval );
			$next_end->sub( $one_sec );
			if ( $datetime_end ) {
				$end_datetime                  = new DateTime( $datetime_end );
				$intervals[ $key ]['date_end'] = ( $next_end > $end_datetime ? $end_datetime : $next_end )->format( 'Y-m-d H:i:s' );
			} else {
				$intervals[ $key ]['date_end'] = $next_end->format( 'Y-m-d H:i:s' );
			}

			$intervals[ $key ]['interval'] = $time_interval;
		}
	}

	/**
	 * Casts strings returned from the database to appropriate data types for output.
	 *
	 * @param array $array Associative array of values extracted from the database.
	 * @return array|WP_Error
	 */
	protected function cast_numbers( $array ) {
		/* translators: %s: Method name */
		return new WP_Error( 'invalid-method', sprintf( __( "Method '%s' not implemented. Must be overridden in subclass.", 'woocommerce' ), __METHOD__ ), array( 'status' => 405 ) );
	}

	/**
	 * Change structure of intervals to form a correct response.
	 *
	 * @param array $intervals Time interval, e.g. day, week, month.
	 */
	protected function create_interval_subtotals( &$intervals ) {
		foreach ( $intervals as $key => $interval ) {
			// Move intervals result to subtotals object.
			$intervals[ $key ] = array(
				'interval'       => $interval['interval'],
				'date_start'     => $interval['date_start'],
				'date_start_gmt' => $interval['date_start'],
				'date_end'       => $interval['date_end'],
				'date_end_gmt'   => $interval['date_end'],
			);

			unset( $interval['interval'] );
			unset( $interval['date_start'] );
			unset( $interval['date_end'] );
			unset( $interval['datetime_anchor'] );
			unset( $interval['time_interval'] );
			$intervals[ $key ]['subtotals'] = (object) $this->cast_numbers( $interval );
		}
	}

	/**
	 * Fills where clause of SQL request for 'Totals' section of data response based on user supplied parameters.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return array
	 */
	protected function get_totals_sql_params( $query_args ) {
		$totals_query = array(
			'from_clause'  => '',
			'where_clause' => '',
		);

		if ( isset( $query_args['before'] ) && '' !== $query_args['before'] ) {
			$datetime                      = new DateTime( $query_args['before'] );
			$datetime_str                  = $datetime->format( WC_Reports_Interval::$sql_datetime_format );
			$totals_query['where_clause'] .= " AND date_created <= '$datetime_str'";

		}

		if ( isset( $query_args['after'] ) && '' !== $query_args['after'] ) {
			$datetime                      = new DateTime( $query_args['after'] );
			$datetime_str                  = $datetime->format( WC_Reports_Interval::$sql_datetime_format );
			$totals_query['where_clause'] .= " AND date_created >= '$datetime_str'";
		}

		return $totals_query;
	}

	/**
	 * Fills clauses of SQL request for 'Intervals' section of data response based on user supplied parameters.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return array
	 */
	protected function get_intervals_sql_params( $query_args ) {
		$intervals_query = array(
			'from_clause'  => '',
			'where_clause' => '',
		);

		if ( isset( $query_args['before'] ) && '' !== $query_args['before'] ) {
			$datetime                         = new DateTime( $query_args['before'] );
			$datetime_str                     = $datetime->format( WC_Reports_Interval::$sql_datetime_format );
			$intervals_query['where_clause'] .= " AND date_created <= '$datetime_str'";

		}

		if ( isset( $query_args['after'] ) && '' !== $query_args['after'] ) {
			$datetime                         = new DateTime( $query_args['after'] );
			$datetime_str                     = $datetime->format( WC_Reports_Interval::$sql_datetime_format );
			$intervals_query['where_clause'] .= " AND date_created >= '$datetime_str'";
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
