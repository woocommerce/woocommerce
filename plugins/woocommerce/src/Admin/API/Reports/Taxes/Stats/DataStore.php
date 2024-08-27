<?php
/**
 * API\Reports\Taxes\Stats\DataStore class file.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Taxes\Stats;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\DataStore as ReportsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\DataStoreInterface;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\Admin\API\Reports\StatsDataStoreTrait;

/**
 * API\Reports\Taxes\Stats\DataStore.
 */
class DataStore extends ReportsDataStore implements DataStoreInterface {
	use StatsDataStoreTrait;

	/**
	 * Table used to get the data.
	 *
	 * @override ReportsDataStore::$table_name
	 *
	 * @var string
	 */
	protected static $table_name = 'wc_order_tax_lookup';

	/**
	 * Cache identifier.
	 *
	 * @override ReportsDataStore::$cache_key
	 *
	 * @var string
	 */
	protected $cache_key = 'taxes_stats';

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @override ReportsDataStore::$column_types
	 *
	 * @var array
	 */
	protected $column_types = array(
		'tax_codes'    => 'intval',
		'total_tax'    => 'floatval',
		'order_tax'    => 'floatval',
		'shipping_tax' => 'floatval',
		'orders_count' => 'intval',
	);

	/**
	 * Data store context used to pass to filters.
	 *
	 * @override ReportsDataStore::$context
	 *
	 * @var string
	 */
	protected $context = 'taxes_stats';

	/**
	 * Assign report columns once full table name has been assigned.
	 *
	 * @override ReportsDataStore::assign_report_columns()
	 */
	protected function assign_report_columns() {
		$table_name           = self::get_db_table_name();
		$this->report_columns = array(
			'tax_codes'    => 'COUNT(DISTINCT tax_rate_id) as tax_codes',
			'total_tax'    => 'SUM(total_tax) AS total_tax',
			'order_tax'    => 'SUM(order_tax) as order_tax',
			'shipping_tax' => 'SUM(shipping_tax) as shipping_tax',
			'orders_count' => "COUNT( DISTINCT ( CASE WHEN parent_id = 0 THEN {$table_name}.order_id END ) ) as orders_count",
		);
	}

	/**
	 * Updates the database query with parameters used for Taxes Stats report
	 *
	 * @param array $query_args       Query arguments supplied by the user.
	 */
	protected function update_sql_query_params( $query_args ) {
		global $wpdb;

		$taxes_where_clause     = '';
		$order_tax_lookup_table = self::get_db_table_name();

		if ( isset( $query_args['taxes'] ) && ! empty( $query_args['taxes'] ) ) {
			$query_args['taxes'] = (array) $query_args['taxes'];
			$tax_id_placeholders = implode( ',', array_fill( 0, count( $query_args['taxes'] ), '%d' ) );
			/* phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared */
			$taxes_where_clause .= $wpdb->prepare( " AND {$order_tax_lookup_table}.tax_rate_id IN ({$tax_id_placeholders})", $query_args['taxes'] );
			/* phpcs:enable */
		}

		$order_status_filter = $this->get_status_subquery( $query_args );
		if ( $order_status_filter ) {
			$taxes_where_clause .= " AND ( {$order_status_filter} )";
		}

		$this->add_time_period_sql_params( $query_args, $order_tax_lookup_table );
		$this->total_query->add_sql_clause( 'where', $taxes_where_clause );

		$this->add_intervals_sql_params( $query_args, $order_tax_lookup_table );
		$this->interval_query->add_sql_clause( 'where', $taxes_where_clause );
		$this->interval_query->add_sql_clause( 'select', $this->get_sql_clause( 'select' ) . ' AS time_interval' );
		$this->interval_query->add_sql_clause( 'where_time', $this->get_sql_clause( 'where_time' ) );
	}

	/**
	 * Get taxes associated with a store.
	 *
	 * @param array $args Array of args to filter the query by. Supports `include`.
	 * @return array An array of all taxes.
	 */
	public static function get_taxes( $args ) {
		global $wpdb;
		$query = "
			SELECT
				tax_rate_id,
				tax_rate_country,
				tax_rate_state,
				tax_rate_name,
				tax_rate_priority
			FROM {$wpdb->prefix}woocommerce_tax_rates
		";
		if ( ! empty( $args['include'] ) ) {
			$args['include'] = (array) $args['include'];
			/* phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared */
			$tax_placeholders = implode( ',', array_fill( 0, count( $args['include'] ), '%d' ) );
			$query           .= $wpdb->prepare( " WHERE tax_rate_id IN ({$tax_placeholders})", $args['include'] );
			/* phpcs:enable */
		}
		return $wpdb->get_results( $query, ARRAY_A ); // WPCS: cache ok, DB call ok, unprepared SQL ok.
	}

	/**
	 * Get the default query arguments to be used by get_data().
	 * These defaults are only partially applied when used via REST API, as that has its own defaults.
	 *
	 * @override ReportsDataStore::get_default_query_vars()
	 *
	 * @return array Query parameters.
	 */
	public function get_default_query_vars() {
		$defaults            = parent::get_default_query_vars();
		$defaults['orderby'] = 'tax_rate_id';
		$defaults['taxes']   = array();

		return $defaults;
	}

	/**
	 * Returns the report data based on normalized parameters.
	 * Will be called by `get_data` if there is no data in cache.
	 *
	 * @override ReportsDataStore::get_noncached_data()
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

		$selections       = $this->selected_columns( $query_args );
		$order_stats_join = "JOIN {$wpdb->prefix}wc_order_stats ON {$table_name}.order_id = {$wpdb->prefix}wc_order_stats.order_id";
		$this->update_sql_query_params( $query_args );
		$this->interval_query->add_sql_clause( 'join', $order_stats_join );

		$db_intervals = $wpdb->get_col(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- cache ok, DB call ok, unprepared SQL ok.
			$this->interval_query->get_query_statement()
		);
		$db_interval_count = count( $db_intervals );

		$this->total_query->add_sql_clause( 'select', $selections );
		$this->total_query->add_sql_clause( 'join', $order_stats_join );
		$this->total_query->add_sql_clause( 'where_time', $this->get_sql_clause( 'where_time' ) );

		$totals = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- cache ok, DB call ok, unprepared SQL ok.
			$this->total_query->get_query_statement(),
			ARRAY_A
		);

		if ( null === $totals ) {
			return new \WP_Error( 'woocommerce_analytics_taxes_stats_result_failed', __( 'Sorry, fetching revenue data failed.', 'woocommerce' ) );
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
		);
		$segmenter             = new Segmenter( $query_args, $this->report_columns );
		$totals[0]['segments'] = $segmenter->get_totals_segments( $totals_query, $table_name );

		$this->update_intervals_sql_params( $query_args, $db_interval_count, $expected_interval_count, $table_name );

		if ( '' !== $selections ) {
			$this->interval_query->add_sql_clause( 'select', ', ' . $selections );
		}

		$this->interval_query->add_sql_clause( 'select', ", MAX({$table_name}.date_created) AS datetime_anchor" );
		$this->interval_query->add_sql_clause( 'order_by', $this->get_sql_clause( 'order_by' ) );
		$this->interval_query->add_sql_clause( 'limit', $this->get_sql_clause( 'limit' ) );

		$intervals = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- cache ok, DB call ok, unprepared SQL ok.
			$this->interval_query->get_query_statement(),
			ARRAY_A
		);

		if ( null === $intervals ) {
			return new \WP_Error( 'woocommerce_analytics_taxes_stats_result_failed', __( 'Sorry, fetching tax data failed.', 'woocommerce' ) );
		}

		$totals = (object) $this->cast_numbers( $totals[0] );

		$data->totals    = $totals;
		$data->intervals = $intervals;

		if ( TimeInterval::intervals_missing( $expected_interval_count, $db_interval_count, $params['per_page'], $query_args['page'], $query_args['order'], $query_args['orderby'], count( $intervals ) ) ) {
			$this->fill_in_missing_intervals( $db_intervals, $query_args['adj_after'], $query_args['adj_before'], $query_args['interval'], $data );
			$this->sort_intervals( $data, $query_args['orderby'], $query_args['order'] );
			$this->remove_extra_records( $data, $query_args['page'], $params['per_page'], $db_interval_count, $expected_interval_count, $query_args['orderby'], $query_args['order'] );
		} else {
			$this->update_interval_boundary_dates( $query_args['after'], $query_args['before'], $query_args['interval'], $data->intervals );
		}
		$segmenter->add_intervals_segments( $data, $intervals_query, $table_name );
		return $data;
	}
}
