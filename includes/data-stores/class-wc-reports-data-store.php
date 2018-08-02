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
	 * Fills where clause of SQL request for 'Totals' section of data response based on user supplied parameters.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return array
	 */
	protected function get_totals_sql_params( $query_args ) {
		$totals_query['where_clause'] = '';

		if ( isset( $query_args['before'] ) && '' !== $query_args['before'] ) {
			$datetime                      = new DateTime( $query_args['before'] );
			$datetime_str                  = $datetime->format( WC_Reports_Interval::$iso_datetime_format );
			$totals_query['where_clause'] .= " AND start_time <= '$datetime_str'";

		}

		if ( isset( $query_args['after'] ) && '' !== $query_args['after'] ) {
			$datetime                      = new DateTime( $query_args['after'] );
			$datetime_str                  = $datetime->format( WC_Reports_Interval::$iso_datetime_format );
			$totals_query['where_clause'] .= " AND start_time >= '$datetime_str'";
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
		$intervals_query                 = array();
		$intervals_query['where_clause'] = '';

		if ( isset( $query_args['before'] ) && '' !== $query_args['before'] ) {
			$datetime                         = new DateTime( $query_args['before'] );
			$datetime_str                     = $datetime->format( WC_Reports_Interval::$iso_datetime_format );
			$intervals_query['where_clause'] .= " AND start_time <= '$datetime_str'";

		}

		if ( isset( $query_args['after'] ) && '' !== $query_args['after'] ) {
			$datetime                         = new DateTime( $query_args['after'] );
			$datetime_str                     = $datetime->format( WC_Reports_Interval::$iso_datetime_format );
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

	/**
	 * Updates the LIMIT query part for Intervals query of the report.
	 *
	 * If there are less records in the database than time intervals, then we need to remap offset in SQL query
	 * to fetch correct records.
	 *
	 * @param array $intervals_query Array with clauses for the Intervals SQL query.
	 * @param int   $db_records      Number of records in the db for requested time period.
	 */
	protected function update_intervals_sql_params( &$intervals_query, &$query_args, $db_interval_count, $expected_interval_count ) {
		if ( $db_interval_count === $expected_interval_count ) {
			return;
		}

		if ( 'date' === strtolower( $query_args['orderby'] ) ) {
			// page X in request translates to slightly different dates in the db, in case some
			// records are missing from the db.
			if ( 'asc' === strtolower( $query_args['order'] ) ) {
				// ORDER BY date ASC.
				$new_start_date = new DateTime( $query_args['after'] );

				$intervals_to_skip = ( $query_args['page'] - 1 ) * $intervals_query['per_page'];
				$latest_end_date   = new DateTime( $query_args['before'] );
				for ( $i = 0; $i < $intervals_to_skip; $i++ ) {
					if ( $new_start_date > $latest_end_date ) {
						$new_start_date = $latest_end_date;
						break;
					}
					$new_start_date = WC_Reports_Interval::iterate( $new_start_date, $query_args['interval'] );
				}

				$new_end_date = clone $new_start_date;
				for ( $i = 0; $i < $intervals_query['per_page']; $i++ ) {
					if ( $new_end_date > $latest_end_date ) {
						$new_end_date = $latest_end_date;
						break;
					}
					$new_end_date = WC_Reports_Interval::iterate( $new_end_date, $query_args['interval'] );
				}
			} else {
				// ORDER BY date DESC.
				$new_end_date        = new DateTime( $query_args['before'] );
				$intervals_to_skip   = ( $query_args['page'] - 1 ) * $intervals_query['per_page'];
				$earliest_start_date = new DateTime( $query_args['after'] );
				for ( $i = 0; $i < $intervals_to_skip; $i++ ) {
					if ( $new_end_date < $earliest_start_date ) {
						$new_end_date = $earliest_start_date;
						break;
					}
					$new_end_date = WC_Reports_Interval::iterate( $new_end_date, $query_args['interval'], true );
				}

				$new_start_date = clone $new_end_date;

				for ( $i = 0; $i < $intervals_query['per_page']; $i++ ) {
					if ( $new_start_date < $earliest_start_date ) {
						$new_start_date = $earliest_start_date;
						break;
					}
					$new_start_date = WC_Reports_Interval::iterate( $new_start_date, $query_args['interval'], true );
				}
			}

			$query_args['adj_after']  = $new_start_date->format( WC_Reports_Interval::$iso_datetime_format );
			$query_args['adj_before'] = $new_end_date->format( WC_Reports_Interval::$iso_datetime_format );

			$intervals_query['where_clause']  = '';
			$intervals_query['where_clause'] .= " AND start_time <= '{$query_args['adj_before']}'";
			$intervals_query['where_clause'] .= " AND start_time >= '{$query_args['adj_after']}'";
			$intervals_query['limit']         = 'LIMIT 0,' . $intervals_query['per_page'];

		} else {
			if ( 'asc' === $query_args['order'] ) {
				$offset = ( ( $query_args['page'] - 1 ) * $intervals_query['per_page'] ) - ( $expected_interval_count - $db_interval_count );
				$offset = $offset < 0 ? 0 : $offset;

				$count = $query_args['page'] * $intervals_query['per_page'] - ( $expected_interval_count - $db_interval_count );

				if ( $count < 0 ) {
					$count = 0;
				} elseif ( $count > $intervals_query['per_page'] ) {
					$count = $intervals_query['per_page'];
				}
				$intervals_query['limit'] = 'LIMIT ' . $offset . ',' . $count;
			}
			// Otherwise no change in limit clause.
			$query_args['adj_after']  = $query_args['after'];
			$query_args['adj_before'] = $query_args['before'];
		}
	}

}
