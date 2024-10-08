<?php
/**
 * API\Reports\Products\Stats\DataStore class file.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Variations\Stats;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\Variations\DataStore as VariationsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\DataStoreInterface;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\Admin\API\Reports\StatsDataStoreTrait;

/**
 * API\Reports\Variations\Stats\DataStore.
 */
class DataStore extends VariationsDataStore implements DataStoreInterface {
	use StatsDataStoreTrait;

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @override VariationsDataStore::$column_types
	 *
	 * @var array
	 */
	protected $column_types = array(
		'items_sold'       => 'intval',
		'net_revenue'      => 'floatval',
		'orders_count'     => 'intval',
		'variations_count' => 'intval',
	);

	/**
	 * Cache identifier.
	 *
	 * @override VariationsDataStore::$cache_key
	 *
	 * @var string
	 */
	protected $cache_key = 'variations_stats';

	/**
	 * Data store context used to pass to filters.
	 *
	 * @override VariationsDataStore::$context
	 *
	 * @var string
	 */
	protected $context = 'variations_stats';

	/**
	 * Assign report columns once full table name has been assigned.
	 *
	 * @override VariationsDataStore::assign_report_columns()
	 */
	protected function assign_report_columns() {
		$table_name           = self::get_db_table_name();
		$this->report_columns = array(
			'items_sold'       => 'SUM(product_qty) as items_sold',
			'net_revenue'      => 'SUM(product_net_revenue) AS net_revenue',
			'orders_count'     => "COUNT( DISTINCT ( CASE WHEN product_gross_revenue >= 0 THEN {$table_name}.order_id END ) ) as orders_count",
			'variations_count' => 'COUNT(DISTINCT variation_id) as variations_count',
		);
	}

	/**
	 * Updates the database query with parameters used for Products Stats report: categories and order status.
	 *
	 * @param array $query_args       Query arguments supplied by the user.
	 */
	protected function update_sql_query_params( $query_args ) {
		global $wpdb;

		$products_where_clause      = '';
		$products_from_clause       = '';
		$where_subquery             = array();
		$order_product_lookup_table = self::get_db_table_name();
		$order_item_meta_table      = $wpdb->prefix . 'woocommerce_order_itemmeta';

		$included_products = $this->get_included_products( $query_args );
		if ( $included_products ) {
			$products_where_clause .= " AND {$order_product_lookup_table}.product_id IN ({$included_products})";
		}

		$excluded_products = $this->get_excluded_products( $query_args );
		if ( $excluded_products ) {
			$products_where_clause .= "AND {$order_product_lookup_table}.product_id NOT IN ({$excluded_products})";
		}

		$included_variations = $this->get_included_variations( $query_args );
		if ( $included_variations ) {
			$products_where_clause .= " AND {$order_product_lookup_table}.variation_id IN ({$included_variations})";
		} elseif ( $this->should_exclude_simple_products( $query_args ) ) {
			$products_where_clause .= " AND {$order_product_lookup_table}.variation_id != 0";
		}

		$order_status_filter = $this->get_status_subquery( $query_args );
		if ( $order_status_filter ) {
			$products_from_clause  .= " JOIN {$wpdb->prefix}wc_order_stats ON {$order_product_lookup_table}.order_id = {$wpdb->prefix}wc_order_stats.order_id";
			$products_where_clause .= " AND ( {$order_status_filter} )";
		}

		$attribute_order_items_subquery = $this->get_order_item_by_attribute_subquery( $query_args );
		if ( $attribute_order_items_subquery ) {
			// JOIN on product lookup if we haven't already.
			if ( ! $order_status_filter ) {
				$products_from_clause .= " JOIN {$wpdb->prefix}wc_order_stats ON {$order_product_lookup_table}.order_id = {$wpdb->prefix}wc_order_stats.order_id";
			}

			// Add subquery for matching attributes to WHERE.
			$products_where_clause .= $attribute_order_items_subquery;
		}

		if ( 0 < count( $where_subquery ) ) {
			$operator               = $this->get_match_operator( $query_args );
			$products_where_clause .= 'AND (' . implode( " {$operator} ", $where_subquery ) . ')';
		}

		$this->add_time_period_sql_params( $query_args, $order_product_lookup_table );
		$this->total_query->add_sql_clause( 'where', $products_where_clause );
		$this->total_query->add_sql_clause( 'join', $products_from_clause );

		$this->add_intervals_sql_params( $query_args, $order_product_lookup_table );
		$this->interval_query->add_sql_clause( 'where', $products_where_clause );
		$this->interval_query->add_sql_clause( 'join', $products_from_clause );
		$this->interval_query->add_sql_clause( 'select', $this->get_sql_clause( 'select' ) . ' AS time_interval' );
	}

	/**
	 * Returns if simple products should be excluded from the report.
	 *
	 * @internal
	 *
	 * @param array $query_args Query parameters.
	 *
	 * @return boolean
	 */
	protected function should_exclude_simple_products( array $query_args ) {
		return apply_filters( 'experimental_woocommerce_analytics_variations_stats_should_exclude_simple_products', true, $query_args );
	}

	/**
	 * Get the default query arguments to be used by get_data().
	 * These defaults are only partially applied when used via REST API, as that has its own defaults.
	 *
	 * @override VariationsDataStore::get_default_query_vars()
	 *
	 * @return array Query parameters.
	 */
	public function get_default_query_vars() {
		$defaults                      = parent::get_default_query_vars();
		$defaults['category_includes'] = array();
		$defaults['interval']          = 'week';
		unset( $defaults['extended_info'] );

		return $defaults;
	}

	/**
	 * Returns the report data based on normalized parameters.
	 * Will be called by `get_data` if there is no data in cache.
	 *
	 * @override VariationsDataStore::get_noncached_stats_data()
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

		$this->update_sql_query_params( $query_args );
		$this->get_limit_sql_params( $query_args );
		$this->interval_query->add_sql_clause( 'where_time', $this->get_sql_clause( 'where_time' ) );

		/* phpcs:disable WordPress.DB.PreparedSQL.NotPrepared */
		$db_intervals = $wpdb->get_col(
			$this->interval_query->get_query_statement()
		);
		/* phpcs:enable */

		$db_interval_count = count( $db_intervals );

		$intervals = array();
		$this->update_intervals_sql_params( $query_args, $db_interval_count, $expected_interval_count, $table_name );
		$this->total_query->add_sql_clause( 'select', $selections );
		$this->total_query->add_sql_clause( 'where_time', $this->get_sql_clause( 'where_time' ) );

		/* phpcs:disable WordPress.DB.PreparedSQL.NotPrepared */
		$totals = $wpdb->get_results(
			$this->total_query->get_query_statement(),
			ARRAY_A
		);
		/* phpcs:enable */

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
			'order_by'          => $this->get_sql_clause( 'order_by' ),
			'limit'             => $this->get_sql_clause( 'limit' ),
		);
		$segmenter             = new Segmenter( $query_args, $this->report_columns );
		$totals[0]['segments'] = $segmenter->get_totals_segments( $totals_query, $table_name );

		if ( null === $totals ) {
			return new \WP_Error( 'woocommerce_analytics_variations_stats_result_failed', __( 'Sorry, fetching revenue data failed.', 'woocommerce' ) );
		}

		$this->interval_query->add_sql_clause( 'order_by', $this->get_sql_clause( 'order_by' ) );
		$this->interval_query->add_sql_clause( 'limit', $this->get_sql_clause( 'limit' ) );
		$this->interval_query->add_sql_clause( 'select', ", MAX({$table_name}.date_created) AS datetime_anchor" );
		if ( '' !== $selections ) {
			$this->interval_query->add_sql_clause( 'select', ', ' . $selections );
		}

		/* phpcs:disable WordPress.DB.PreparedSQL.NotPrepared */
		$intervals = $wpdb->get_results(
			$this->interval_query->get_query_statement(),
			ARRAY_A
		);
		/* phpcs:enable */

		if ( null === $intervals ) {
			return new \WP_Error( 'woocommerce_analytics_variations_stats_result_failed', __( 'Sorry, fetching revenue data failed.', 'woocommerce' ) );
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

	/**
	 * Normalizes order_by clause to match to SQL query.
	 *
	 * @override VariationsDataStore::normalize_order_by()
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
