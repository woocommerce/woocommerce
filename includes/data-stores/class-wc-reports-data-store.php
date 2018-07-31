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

	// TODO: this does not really belong here, maybe factor out the comparison as separate class.
	/**
	 * Order by property, used in the cmp function.
	 *
	 * @var string
	 */
	private $order_by = '';

	/**
	 * Order property, used in the cmp function.
	 *
	 * @var string
	 */
	private $order = '';

	/**
	 * Returns string to be used as cache key for the data.
	 *
	 * @param array $params Query parameters.
	 * @return string
	 */
	protected function get_cache_key( $params ) {
		return 'woocommerce_' . $this->table_name . '_' . md5( wp_json_encode( $params ) . date( 'Y-m-d_H' ) );
	}

	/**
	 * Compares two report data objects by pre-defined object property and ASC/DESC ordering.
	 *
	 * @param stdClass $a Object a.
	 * @param stdClass $b Object b.
	 * @return string
	 */
	private function interval_cmp( $a, $b ) {
		if ( '' === $this->order_by || '' === $this->order ) {
			return 0;
			// TODO: should return WP_Error here perhaps?
		}
		if ( $a->{$this->order_by} === $b->{$this->order_by} ) {
			return 0;
		} elseif ( $a->{$this->order_by} > $b->{$this->order_by} ) {
			return strtolower( $this->order ) === 'desc' ? -1 : 1;
		} elseif ( $a->{$this->order_by} < $b->{$this->order_by} ) {
			return strtolower( $this->order ) === 'desc' ? 1 : -1;
		}
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
	 * Sorts intervals according to user's request.
	 *
	 * They are pre-sorted in SQL, but after adding gaps, they need to be sorted including the added ones.
	 *
	 * @param stdClass $data      Data object, must contain an array under $data->intervals.
	 * @param string   $sort_by   Ordering property.
	 * @param string   $direction DESC/ASC.
	 */
	protected function sort_intervals( &$data, $sort_by, $direction ) {
		$this->order_by = $this->normalize_order_by( $sort_by );
		$this->order    = $direction;
		usort( $data->intervals, array( $this, 'interval_cmp' ) );
	}

	/**
	 * Fills in interval gaps from DB with 0-filled objects.
	 *
	 * @param DateTime $datetime_start Start date.
	 * @param DateTime $datetime_end   End date.
	 * @param string   $time_interval  Time interval, e.g. day, week, month.
	 * @param stdClass $data           Data with SQL extracted intervals.
	 * @return stdClass
	 */
	protected function fill_in_missing_intervals( $datetime_start, $datetime_end, $time_interval, &$data ) {
		// TODO: this is incredibly basic for now,
		// need to think about how to handle at least:
		// - weeks starting on Saturday, Sunday, Monday, Wednesday, etc.
		// - incorrect +1 month.
		// - time zones, DST.
		// - more?
		// At this point, we don't know when we can stop iterating, as the ordering can be based on any value.
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
			} else {
				$record_arr                  = array();
				$record_arr['time_interval'] = $time_id;
				$record_arr['date_start']    = $datetime->format( 'Y-m-d H:i:s' );
				$record_arr['date_end']      = $interval_end;

				// Totals object used to get all needed properties.
				$totals_arr = get_object_vars( $data->totals );
				foreach ( $totals_arr as $key => $val ) {
					$totals_arr[ $key ] = 0;
				}

				$data->intervals[] = (object) array_merge( $record_arr, $totals_arr );
			}
			$datetime = $next_end;
		}

		return $data;
	}

	/**
	 * Updates dates for intervals.
	 *
	 * @param DateTime $datetime_start Start date.
	 * @param DateTime $datetime_end   End date.
	 * @param string   $time_interval  Time interval, e.g. day, week, month.
	 * @param stdClass $data           Data with SQL extracted intervals.
	 * @return stdClass
	 */
	protected function update_dates( $datetime_start, $datetime_end, $time_interval, &$data ) {
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
	 * Removes extra records from intervals so that only requested number of records get returned.
	 *
	 * @param stdClass $data   Data from whose intervals the records get removed.
	 * @param int      $offset Offset requested by the user.
	 * @param int      $count  Number of records requested by the user.
	 */
	protected function remove_extra_records( &$data, $offset, $count ) {
		$data->intervals = array_slice( $data->intervals, $offset * $count, $count );
	}

	/**
	 * Fills where clause of SQL request for 'Totals' section of data response based on user supplied parameters.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return array
	 */
	protected function get_totals_sql_params( $query_args ) {
		$totals_query['where_clause'] = '';

		if ( isset( $query_args['before'] ) && '' !== $query_args['before'] ) {
			$hour                          = $query_args['before'];
			$totals_query['where_clause'] .= " AND start_time <= '$hour'";

		}

		if ( isset( $query_args['after'] ) && '' !== $query_args['after'] ) {
			$hour                          = $query_args['after'];
			$totals_query['where_clause'] .= " AND start_time >= '$hour'";
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
		$intervals_query = array();
		$intervals_query['where_clause'] = '';

		if ( isset( $query_args['before'] ) && '' !== $query_args['before'] ) {
			$hour                             = $query_args['before'];
			$intervals_query['where_clause'] .= " AND start_time <= '$hour'";

		}

		if ( isset( $query_args['after'] ) && '' !== $query_args['after'] ) {
			$hour                             = $query_args['after'];
			$intervals_query['where_clause'] .= " AND start_time >= '$hour'";
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

	/**
	 * Updates the LIMIT query part for Intervals query of the report.
	 *
	 * If there are less records in the database than time intervals, then we need to remap offset in SQL query
	 * to fetch correct records.
	 *
	 * @param array $intervals_query            Array with clauses for the Intervals SQL query.
	 * @param int   $db_records_within_interval Number of records in the db for requested time period.
	 */
	protected function update_intervals_sql_params( &$intervals_query, $db_records_within_interval ) {
		$page_no                   = floor( ( $db_records_within_interval - 1 ) / $intervals_query['per_page'] ) + 1;
		$intervals_query['offset'] = ( $page_no - 1 ) * $intervals_query['per_page'];
	}

}
