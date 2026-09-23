<?php
/**
 * API\Reports\StockNotifications\Stats\DataStore class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\API\Reports\StockNotifications\Stats;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\DataStoreInterface;
use Automattic\WooCommerce\Admin\API\Reports\StatsDataStoreTrait;
use Automattic\WooCommerce\Admin\API\Reports\StockNotifications\DataStore as StockNotificationsDataStore;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;

/**
 * Stock notifications stats report: totals plus per-interval subtotals.
 *
 * - signups and customers: same definitions as the per-product report, bucketed on date_created_gmt.
 * - notifications_sent: rows whose date_notified_gmt falls in the period, any status. The date is
 *   overwritten on re-send, so a re-send moves the count to the new date rather than adding to it.
 * - active_signups: rows currently active, whatever the period. Totals only; it has no meaning per interval.
 */
class DataStore extends StockNotificationsDataStore implements DataStoreInterface {
	use StatsDataStoreTrait;

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @override StockNotificationsDataStore::$column_types
	 *
	 * @var array
	 */
	protected $column_types = array(
		'date_start'         => 'strval',
		'date_end'           => 'strval',
		'date_start_gmt'     => 'strval',
		'date_end_gmt'       => 'strval',
		'signups'            => 'intval',
		'customers'          => 'intval',
		'notifications_sent' => 'intval',
		'active_signups'     => 'intval',
	);

	/**
	 * SQL columns to select in the db query.
	 *
	 * @override StockNotificationsDataStore::$report_columns
	 *
	 * @var array
	 */
	protected $report_columns;

	/**
	 * Data store context used to pass to filters.
	 *
	 * @override StockNotificationsDataStore::$context
	 *
	 * @var string
	 */
	protected $context = 'stock_notifications_stats';

	/**
	 * Cache identifier.
	 *
	 * @override StockNotificationsDataStore::$cache_key
	 *
	 * @var string
	 */
	protected $cache_key = 'stock_notifications_stats';

	/**
	 * Assign report columns once full table name has been assigned.
	 *
	 * These run on the date_created_gmt series, whose WHERE clause already excludes pending rows.
	 *
	 * @override StockNotificationsDataStore::assign_report_columns()
	 *
	 * @return void
	 */
	protected function assign_report_columns() {
		$table_name           = self::get_db_table_name();
		$this->report_columns = array(
			'signups'   => 'COUNT(*) AS signups',
			'customers' => "COUNT( DISTINCT {$table_name}.user_email ) AS customers",
		);
	}

	/**
	 * Get the default query arguments to be used by get_data().
	 * These defaults are only partially applied when used via REST API, as that has its own defaults.
	 *
	 * @override StockNotificationsDataStore::get_default_query_vars()
	 *
	 * @return array Query parameters.
	 */
	public function get_default_query_vars() {
		$defaults             = parent::get_default_query_vars();
		$defaults['interval'] = 'day';
		$defaults['per_page'] = 100;
		$defaults['orderby']  = 'date';

		return $defaults;
	}

	/**
	 * Fills the interval and totals queries for the sign-ups series.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 * @return void
	 */
	protected function update_sql_query_params( $query_args ) {
		global $wpdb;

		$table_name = self::get_db_table_name();
		$where      = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is internal.
			"AND {$table_name}.status <> %s",
			NotificationStatus::PENDING
		);

		$this->add_time_period_sql_params( $query_args, $table_name );
		$this->add_intervals_sql_params( $query_args, $table_name );
		$this->add_order_by_sql_params( $query_args );
		$where_time = $this->get_sql_clause( 'where_time' );

		$this->interval_query->add_sql_clause( 'select', $this->get_sql_clause( 'select' ) );
		$this->interval_query->add_sql_clause( 'select', 'AS time_interval' );

		foreach ( array( $this->interval_query, $this->total_query ) as $query ) {
			$query->add_sql_clause( 'where_time', $where_time );
			$query->add_sql_clause( 'where', $where );
		}
	}

	/**
	 * Returns the report data based on normalized parameters.
	 * Will be called by `get_data` if there is no data in cache.
	 *
	 * Runs the standard interval flow for sign-ups, then merges in notifications sent,
	 * which are bucketed on a different date column, and the point-in-time active count.
	 *
	 * @override StockNotificationsDataStore::get_noncached_stats_data()
	 *
	 * @see get_data
	 * @see get_noncached_stats_data
	 * @param array     $query_args              Query parameters.
	 * @param array     $params                  Query limit parameters.
	 * @param \stdClass $data                   Reference to the data object to fill.
	 * @param int       $expected_interval_count Number of expected intervals.
	 * @return \stdClass|\WP_Error Data object `{ totals: *, intervals: array, total: int, pages: int, page_no: int }`, or error.
	 */
	public function get_noncached_stats_data( $query_args, $params, &$data, $expected_interval_count ) {
		global $wpdb;

		$table_name = self::get_db_table_name();

		$this->initialize_queries();

		$selections   = $this->selected_columns( $query_args );
		$limit_params = $this->get_limit_sql_params( $query_args );
		$this->update_sql_query_params( $query_args );

		$db_intervals = $wpdb->get_col(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- cache ok, DB call ok, unprepared SQL ok.
			$this->interval_query->get_query_statement()
		);

		$db_interval_count = count( $db_intervals );

		$totals = array();
		if ( '' !== $selections ) {
			$this->total_query->add_sql_clause( 'select', $selections );
			$totals = $wpdb->get_row(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- cache ok, DB call ok, unprepared SQL ok.
				$this->total_query->get_query_statement(),
				ARRAY_A
			);

			if ( null === $totals ) {
				return $data;
			}
		}

		// Intervals.
		$this->update_intervals_sql_params( $query_args, $db_interval_count, $expected_interval_count, $table_name );
		$this->interval_query->add_sql_clause( 'order_by', $this->get_sql_clause( 'order_by' ) );
		$this->interval_query->add_sql_clause( 'limit', $this->get_sql_clause( 'limit' ) );
		$this->interval_query->add_sql_clause( 'select', ', MAX( ' . $this->get_local_datetime_sql( $table_name, $this->date_column_name ) . ' ) AS datetime_anchor' );

		if ( '' !== $selections ) {
			$this->interval_query->add_sql_clause( 'select', ', ' . $selections );
		}

		$intervals = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- cache ok, DB call ok, unprepared SQL ok.
			$this->interval_query->get_query_statement(),
			ARRAY_A
		);

		if ( null === $intervals ) {
			return $data;
		}

		// Zero-filled intervals copy their keys from the totals, so set only the per-interval metrics for now.
		$data->totals    = (object) $this->cast_numbers( $totals );
		$data->intervals = $intervals;

		if ( $this->intervals_missing( $expected_interval_count, $db_interval_count, $limit_params['per_page'], $query_args['page'], $query_args['order'], $query_args['orderby'], count( $intervals ) ) ) {
			// @phpstan-ignore argument.type, class.notFound (base docblocks name an unqualified stdClass)
			$this->fill_in_missing_intervals( $db_intervals, $query_args['adj_after'], $query_args['adj_before'], $query_args['interval'], $data );
			// @phpstan-ignore class.notFound
			$this->sort_intervals( $data, $query_args['orderby'], $query_args['order'] );
			// @phpstan-ignore class.notFound
			$this->remove_extra_records( $data, $query_args['page'], $limit_params['per_page'], $db_interval_count, $expected_interval_count, $query_args['orderby'], $query_args['order'] );
		} else {
			$this->update_interval_boundary_dates( $query_args['after'], $query_args['before'], $query_args['interval'], $data->intervals );
		}

		/**
		 * The base class docblocks type $data as an unqualified stdClass.
		 *
		 * @var \stdClass $data
		 */

		if ( $this->is_field_requested( $query_args, 'notifications_sent' ) ) {
			$sent_by_interval = $this->get_notifications_sent_by_interval( $query_args );
			foreach ( $data->intervals as $idx => $interval ) {
				$data->intervals[ $idx ]['notifications_sent'] = $sent_by_interval[ $interval['time_interval'] ] ?? 0;
			}
			$data->totals->notifications_sent = array_sum( $sent_by_interval );
		}

		if ( $this->is_field_requested( $query_args, 'active_signups' ) ) {
			$data->totals->active_signups = (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is internal.
					"SELECT COUNT(*) FROM {$table_name} WHERE status = %s",
					NotificationStatus::ACTIVE
				)
			);
		}

		return $data;
	}

	/**
	 * Returns notifications sent in the query period, keyed by time interval id.
	 *
	 * Covers the whole period, not just the current page, so any page can look up its intervals.
	 *
	 * @param array $query_args Query parameters.
	 * @return array<string, int>
	 */
	protected function get_notifications_sent_by_interval( $query_args ) {
		global $wpdb;

		$table_name = self::get_db_table_name();
		$column     = 'date_notified_gmt';
		$interval   = $this->get_interval_sql( $query_args['interval'], $table_name, $column );
		$where      = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is internal.
			"{$table_name}.`{$column}` >= %s AND {$table_name}.`{$column}` <= %s",
			$this->get_utc_datetime_string( $query_args['after'] ),
			$this->get_utc_datetime_string( $query_args['before'] )
		);

		$rows = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- built from internal clauses and a prepared WHERE.
			"SELECT {$interval} AS time_interval, COUNT(*) AS notifications_sent FROM {$table_name} WHERE {$where} GROUP BY time_interval",
			ARRAY_A
		);

		return array_map( 'intval', array_column( (array) $rows, 'notifications_sent', 'time_interval' ) );
	}

	/**
	 * Whether the request asks for a field, either explicitly or by not limiting fields.
	 *
	 * @param array  $query_args Query parameters.
	 * @param string $field      Field name.
	 * @return bool
	 */
	protected function is_field_requested( $query_args, $field ) {
		return ! isset( $query_args['fields'] ) || ! is_array( $query_args['fields'] ) || in_array( $field, $query_args['fields'], true );
	}
}
