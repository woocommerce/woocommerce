<?php
/**
 * API\Reports\Coupons\Stats\DataStore class file.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Coupons\Stats;

defined( 'ABSPATH' ) || exit;
use Automattic\WooCommerce\Admin\API\Reports\Coupons\DataStore as CouponsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\DataStoreInterface;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\Admin\API\Reports\StatsDataStoreTrait;

/**
 * API\Reports\Coupons\Stats\DataStore.
 */
class DataStore extends CouponsDataStore implements DataStoreInterface {
	use StatsDataStoreTrait;

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @override CouponsDataStore::$column_types
	 *
	 * @var array
	 */
	protected $column_types = array(
		'date_start'     => 'strval',
		'date_end'       => 'strval',
		'date_start_gmt' => 'strval',
		'date_end_gmt'   => 'strval',
		'amount'         => 'floatval',
		'coupons_count'  => 'intval',
		'orders_count'   => 'intval',
	);

	/**
	 * SQL columns to select in the db query.
	 *
	 * @override CouponsDataStore::$report_columns
	 *
	 * @var array
	 */
	protected $report_columns;

	/**
	 * Data store context used to pass to filters.
	 *
	 * @override CouponsDataStore::$context
	 *
	 * @var string
	 */
	protected $context = 'coupons_stats';

	/**
	 * Cache identifier.
	 *
	 * @override CouponsDataStore::get_default_query_vars()
	 *
	 * @var string
	 */
	protected $cache_key = 'coupons_stats';

	/**
	 * Assign report columns once full table name has been assigned.
	 *
	 * @override CouponsDataStore::assign_report_columns()
	 */
	protected function assign_report_columns() {
		$table_name           = self::get_db_table_name();
		$this->report_columns = array(
			'amount'        => 'SUM(discount_amount) as amount',
			'coupons_count' => 'COUNT(DISTINCT coupon_id) as coupons_count',
			'orders_count'  => "COUNT(DISTINCT {$table_name}.order_id) as orders_count",
		);
	}

	/**
	 * Updates the database query with parameters used for Products Stats report: categories and order status.
	 *
	 * @param array $query_args       Query arguments supplied by the user.
	 */
	protected function update_sql_query_params( $query_args ) {
		global $wpdb;

		$clauses = array(
			'where' => '',
			'join'  => '',
		);

		$order_coupon_lookup_table = self::get_db_table_name();

		$included_coupons = $this->get_included_coupons( $query_args, 'coupons' );
		if ( $included_coupons ) {
			$clauses['where'] .= " AND {$order_coupon_lookup_table}.coupon_id IN ({$included_coupons})";
		}

		$order_status_filter = $this->get_status_subquery( $query_args );
		if ( $order_status_filter ) {
			$clauses['join']  .= " JOIN {$wpdb->prefix}wc_order_stats ON {$order_coupon_lookup_table}.order_id = {$wpdb->prefix}wc_order_stats.order_id";
			$clauses['where'] .= " AND ( {$order_status_filter} )";
		}

		$this->add_time_period_sql_params( $query_args, $order_coupon_lookup_table );
		$this->add_intervals_sql_params( $query_args, $order_coupon_lookup_table );
		$clauses['where_time'] = $this->get_sql_clause( 'where_time' );

		$this->interval_query->add_sql_clause( 'limit', $this->get_sql_clause( 'limit' ) );
		$this->interval_query->add_sql_clause( 'order_by', $this->get_sql_clause( 'order_by' ) );
		$this->interval_query->add_sql_clause( 'select', $this->get_sql_clause( 'select' ) );
		$this->interval_query->add_sql_clause( 'select', 'AS time_interval' );

		foreach ( array( 'join', 'where_time', 'where' ) as $clause ) {
			$this->interval_query->add_sql_clause( $clause, $clauses[ $clause ] );
			$this->total_query->add_sql_clause( $clause, $clauses[ $clause ] );
		}
	}

	/**
	 * Get the default query arguments to be used by get_data().
	 * These defaults are only partially applied when used via REST API, as that has its own defaults.
	 *
	 * @override CouponsDataStore::get_default_query_vars()
	 *
	 * @return array Query parameters.
	 */
	public function get_default_query_vars() {
		$defaults             = parent::get_default_query_vars();
		$defaults['coupons']  = array();
		$defaults['interval'] = 'week';

		return $defaults;
	}

	/**
	 * Returns the report data based on normalized parameters.
	 * Will be called by `get_data` if there is no data in cache.
	 *
	 * @override CouponsDataStore::get_noncached_stats_data()
	 *
	 * @see get_data
	 * @see get_noncached_stats_data
	 * @param array    $query_args Query parameters.
	 * @param array    $params            Query limit parameters.
	 * @param stdClass $data                    Reference to the data object to fill.
	 * @param int      $expected_interval_count Number of expected intervals.
	 * @return stdClass|WP_Error Data object `{ totals: *, intervals: array, total: int, pages: int, page_no: int }`, or error.
	 */
	public function get_noncached_stats_data( $query_args, $params, &$data, $expected_interval_count ) {
		global $wpdb;

		$table_name = self::get_db_table_name();

		$this->initialize_queries();

		$selections      = $this->selected_columns( $query_args );
		$totals_query    = array();
		$intervals_query = array();
		$limit_params    = $this->get_limit_sql_params( $query_args );
		$this->update_sql_query_params( $query_args, $totals_query, $intervals_query );

		$db_intervals = $wpdb->get_col(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- cache ok, DB call ok, unprepared SQL ok.
			$this->interval_query->get_query_statement()
		);

		$db_interval_count = count( $db_intervals );

		$this->total_query->add_sql_clause( 'select', $selections );
		$totals = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- cache ok, DB call ok, unprepared SQL ok.
			$this->total_query->get_query_statement(),
			ARRAY_A
		);

		if ( null === $totals ) {
			return $data;
		}

		// phpcs:ignore Generic.Commenting.Todo.TaskFound
		// @todo remove these assignements when refactoring segmenter classes to use query objects.
		$totals_query          = array(
			'from_clause'       => $this->total_query->get_sql_clause( 'join' ),
			'where_time_clause' => $this->total_query->get_sql_clause( 'where_time' ),
			'where_clause'      => $this->total_query->get_sql_clause( 'where' ),
		);
		$intervals_query       = array(
			'select_clause'     => $this->get_sql_clause( 'select' ),
			'from_clause'       => $this->interval_query->get_sql_clause( 'join' ),
			'where_time_clause' => $this->interval_query->get_sql_clause( 'where_time' ),
			'where_clause'      => $this->interval_query->get_sql_clause( 'where' ),
			'limit'             => $this->get_sql_clause( 'limit' ),
		);
		$segmenter             = new Segmenter( $query_args, $this->report_columns );
		$totals[0]['segments'] = $segmenter->get_totals_segments( $totals_query, $table_name );
		$totals                = (object) $this->cast_numbers( $totals[0] );

		// Intervals.
		$this->update_intervals_sql_params( $query_args, $db_interval_count, $expected_interval_count, $table_name );
		$this->interval_query->add_sql_clause( 'select', ", MAX({$table_name}.date_created) AS datetime_anchor" );

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

		$data->totals    = $totals;
		$data->intervals = $intervals;

		if ( TimeInterval::intervals_missing( $expected_interval_count, $db_interval_count, $limit_params['per_page'], $query_args['page'], $query_args['order'], $query_args['orderby'], count( $intervals ) ) ) {
			$this->fill_in_missing_intervals( $db_intervals, $query_args['adj_after'], $query_args['adj_before'], $query_args['interval'], $data );
			$this->sort_intervals( $data, $query_args['orderby'], $query_args['order'] );
			$this->remove_extra_records( $data, $query_args['page'], $limit_params['per_page'], $db_interval_count, $expected_interval_count, $query_args['orderby'], $query_args['order'] );
		} else {
			$this->update_interval_boundary_dates( $query_args['after'], $query_args['before'], $query_args['interval'], $data->intervals );
		}
		$segmenter->add_intervals_segments( $data, $intervals_query, $table_name );

		return $data;
	}
}
