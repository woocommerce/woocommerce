<?php
/**
 * API\Reports\Downloads\Stats\DataStore class file.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Downloads\Stats;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\Downloads\DataStore as DownloadsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\DataStoreInterface;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\Admin\API\Reports\StatsDataStoreTrait;

/**
 * API\Reports\Downloads\Stats\DataStore.
 */
class DataStore extends DownloadsDataStore implements DataStoreInterface {
	use StatsDataStoreTrait;

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @override DownloadsDataStore::$column_types
	 *
	 * @var array
	 */
	protected $column_types = array(
		'download_count' => 'intval',
	);

	/**
	 * Cache identifier.
	 *
	 * @override DownloadsDataStore::$cache_key
	 *
	 * @var string
	 */
	protected $cache_key = 'downloads_stats';

	/**
	 * Data store context used to pass to filters.
	 *
	 * @override DownloadsDataStore::$context
	 *
	 * @var string
	 */
	protected $context = 'downloads_stats';

	/**
	 * Assign report columns once full table name has been assigned.
	 *
	 * @override DownloadsDataStore::assign_report_columns()
	 */
	protected function assign_report_columns() {
		$this->report_columns = array(
			'download_count' => 'COUNT(DISTINCT download_log_id) as download_count',
		);
	}

	/**
	 * Get the default query arguments to be used by get_data().
	 * These defaults are only partially applied when used via REST API, as that has its own defaults.
	 *
	 * @override DownloadsDataStore::default_query_args()
	 *
	 * @return array Query parameters.
	 */
	public function get_default_query_vars() {
		$defaults             = parent::get_default_query_vars();
		$defaults['interval'] = 'week';

		return $defaults;
	}

	/**
	 * Returns the report data based on normalized parameters.
	 * Will be called by `get_data` if there is no data in cache.
	 *
	 * @override DownloadsDataStore::get_noncached_data()
	 *
	 * @see get_data
	 * @see get_noncached_stats_data
	 * @param array    $query_args Query parameters.
	 * @param array    $params                  Query limit parameters.
	 * @param stdClass $data                    Reference to the data object to fill.
	 * @param int      $expected_interval_count Number of expected intervals.
	 * @return stdClass|WP_Error Data object `{ totals: *, intervals: array, total: int, pages: int, page_no: int }`, or error.
	 */
	public function get_noncached_stats_data( $query_args, $params, &$data, $expected_interval_count ) {
		global $wpdb;

		$table_name = self::get_db_table_name();

		$this->initialize_queries();
		$selections = $this->selected_columns( $query_args );
		$this->add_sql_query_params( $query_args );
		$where_time = $this->add_time_period_sql_params( $query_args, $table_name );
		$this->add_intervals_sql_params( $query_args, $table_name );

		$this->interval_query->add_sql_clause( 'select', $this->get_sql_clause( 'select' ) . ' AS time_interval' );
		$this->interval_query->str_replace_clause( 'select', 'date_created', 'timestamp' );
		$this->interval_query->str_replace_clause( 'where_time', 'date_created', 'timestamp' );

		$db_intervals = $wpdb->get_col(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- cache ok, DB call ok, unprepared SQL ok.
			$this->interval_query->get_query_statement()
		);

		$db_records_count = count( $db_intervals );

		$this->update_intervals_sql_params( $query_args, $db_records_count, $expected_interval_count, $table_name );
		$this->interval_query->str_replace_clause( 'where_time', 'date_created', 'timestamp' );
		$this->total_query->add_sql_clause( 'select', $selections );
		$this->total_query->add_sql_clause( 'where', $this->interval_query->get_sql_clause( 'where' ) );
		if ( $where_time ) {
			$this->total_query->add_sql_clause( 'where_time', $where_time );
		}
		$totals = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- cache ok, DB call ok, unprepared SQL ok.
			$this->total_query->get_query_statement(),
			ARRAY_A
		);
		if ( null === $totals ) {
			return new \WP_Error( 'woocommerce_analytics_downloads_stats_result_failed', __( 'Sorry, fetching downloads data failed.', 'woocommerce' ) );
		}

		$this->interval_query->add_sql_clause( 'order_by', $this->get_sql_clause( 'order_by' ) );
		$this->interval_query->add_sql_clause( 'limit', $this->get_sql_clause( 'limit' ) );
		$this->interval_query->add_sql_clause( 'select', ', MAX(timestamp) AS datetime_anchor' );
		if ( '' !== $selections ) {
			$this->interval_query->add_sql_clause( 'select', ', ' . $selections );
		}

		$intervals = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- cache ok, DB call ok, unprepared SQL ok.
			$this->interval_query->get_query_statement(),
			ARRAY_A
		);

		if ( null === $intervals ) {
			return new \WP_Error( 'woocommerce_analytics_downloads_stats_result_failed', __( 'Sorry, fetching downloads data failed.', 'woocommerce' ) );
		}

		$totals = (object) $this->cast_numbers( $totals[0] );

		$data->totals    = $totals;
		$data->intervals = $intervals;

		if ( $this->intervals_missing( $expected_interval_count, $db_records_count, $params['per_page'], $query_args['page'], $query_args['order'], $query_args['orderby'], count( $intervals ) ) ) {
			$this->fill_in_missing_intervals( $db_intervals, $query_args['adj_after'], $query_args['adj_before'], $query_args['interval'], $data );
			$this->sort_intervals( $data, $query_args['orderby'], $query_args['order'] );
			$this->remove_extra_records( $data, $query_args['page'], $params['per_page'], $db_records_count, $expected_interval_count, $query_args['orderby'], $query_args['order'] );
		} else {
			$this->update_interval_boundary_dates( $query_args['after'], $query_args['before'], $query_args['interval'], $data->intervals );
		}

		return $data;
	}

	/**
	 * Normalizes order_by clause to match to SQL query.
	 *
	 * @override DownloadsDataStore::normalize_order_by()
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
}
