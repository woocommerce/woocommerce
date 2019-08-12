<?php
/**
 * API\Reports\Taxes\Stats\DataStore class file.
 *
 * @package WooCommerce Admin/Classes
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Taxes\Stats;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\API\Reports\DataStore as ReportsDataStore;
use \Automattic\WooCommerce\Admin\API\Reports\DataStoreInterface;
use \Automattic\WooCommerce\Admin\API\Reports\TimeInterval;

/**
 * API\Reports\Taxes\Stats\DataStore.
 */
class DataStore extends ReportsDataStore implements DataStoreInterface {

	/**
	 * Table used to get the data.
	 *
	 * @var string
	 */
	const TABLE_NAME = 'wc_order_tax_lookup';

	/**
	 * Mapping columns to data type to return correct response types.
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
	 * SQL columns to select in the db query.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'tax_codes'    => 'COUNT(DISTINCT tax_rate_id) as tax_codes',
		'total_tax'    => 'SUM(total_tax) AS total_tax',
		'order_tax'    => 'SUM(order_tax) as order_tax',
		'shipping_tax' => 'SUM(shipping_tax) as shipping_tax',
		'orders_count' => 'COUNT( DISTINCT ( CASE WHEN parent_id = 0 THEN order_id END ) ) as orders_count',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		// Avoid ambigious column order_id in SQL query.
		$this->report_columns['orders_count'] = str_replace( 'order_id', $table_name . '.order_id', $this->report_columns['orders_count'] );
	}

	/**
	 * Updates the database query with parameters used for Taxes report: categories and order status.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 * @return array            Array of parameters used for SQL query.
	 */
	protected function get_sql_query_params( $query_args ) {
		global $wpdb;

		$order_tax_lookup_table = $wpdb->prefix . self::TABLE_NAME;

		$sql_query_params = $this->get_time_period_sql_params( $query_args, $order_tax_lookup_table );
		$sql_query_params = array_merge( $sql_query_params, $this->get_limit_sql_params( $query_args ) );
		$sql_query_params = array_merge( $sql_query_params, $this->get_order_by_sql_params( $query_args ) );

		if ( isset( $query_args['taxes'] ) && ! empty( $query_args['taxes'] ) ) {
			$allowed_taxes                     = implode( ',', $query_args['taxes'] );
			$sql_query_params['where_clause'] .= " AND {$order_tax_lookup_table}.tax_rate_id IN ({$allowed_taxes})";
		}

		$order_status_filter = $this->get_status_subquery( $query_args );
		if ( $order_status_filter ) {
			$sql_query_params['where_clause'] .= " AND ( {$order_status_filter} )";
		}

		return $sql_query_params;
	}

	/**
	 * Updates the database query with parameters used for Taxes Stats report
	 *
	 * @param array $query_args       Query arguments supplied by the user.
	 * @param array $totals_params    SQL parameters for the totals query.
	 * @param array $intervals_params SQL parameters for the intervals query.
	 */
	protected function update_sql_query_params( $query_args, &$totals_params, &$intervals_params ) {
		global $wpdb;

		$taxes_where_clause = '';
		$taxes_from_clause  = '';

		$order_tax_lookup_table = $wpdb->prefix . self::TABLE_NAME;

		if ( isset( $query_args['taxes'] ) && ! empty( $query_args['taxes'] ) ) {
			$allowed_taxes       = implode( ',', $query_args['taxes'] );
			$taxes_where_clause .= " AND {$order_tax_lookup_table}.tax_rate_id IN ({$allowed_taxes})";
		}

		$totals_params                  = array_merge( $totals_params, $this->get_time_period_sql_params( $query_args, $order_tax_lookup_table ) );
		$totals_params['where_clause'] .= $taxes_where_clause;

		$intervals_params                  = array_merge( $intervals_params, $this->get_intervals_sql_params( $query_args, $order_tax_lookup_table ) );
		$intervals_params['where_clause'] .= $taxes_where_clause;
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
			$included_taxes = implode( ',', $args['include'] );
			$query         .= " WHERE tax_rate_id IN ({$included_taxes})";
		}
		return $wpdb->get_results( $query, ARRAY_A ); // WPCS: cache ok, DB call ok, unprepared SQL ok.
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

		// These defaults are only partially applied when used via REST API, as that has its own defaults.
		$defaults   = array(
			'per_page' => get_option( 'posts_per_page' ),
			'page'     => 1,
			'order'    => 'DESC',
			'orderby'  => 'tax_rate_id',
			'before'   => TimeInterval::default_before(),
			'after'    => TimeInterval::default_after(),
			'fields'   => '*',
			'taxes'    => array(),
		);
		$query_args = wp_parse_args( $query_args, $defaults );
		$this->normalize_timezones( $query_args, $defaults );

		$cache_key = $this->get_cache_key( $query_args );
		$data      = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $data ) {
			$data = (object) array(
				'totals'    => (object) array(),
				'intervals' => (object) array(),
				'total'     => 0,
				'pages'     => 0,
				'page_no'   => 0,
			);

			$selections      = $this->selected_columns( $query_args );
			$totals_query    = array();
			$intervals_query = array();
			$this->update_sql_query_params( $query_args, $totals_query, $intervals_query );

			$db_intervals = $wpdb->get_col(
				"SELECT
							{$intervals_query['select_clause']} AS time_interval
						FROM
							{$table_name}
							{$intervals_query['from_clause']}
						JOIN
							{$wpdb->prefix}wc_order_stats ON {$table_name}.order_id = {$wpdb->prefix}wc_order_stats.order_id
						WHERE
							1=1
							{$intervals_query['where_time_clause']}
							{$intervals_query['where_clause']}
						GROUP BY
							time_interval"
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			$db_interval_count       = count( $db_intervals );
			$expected_interval_count = TimeInterval::intervals_between( $query_args['after'], $query_args['before'], $query_args['interval'] );
			$total_pages             = (int) ceil( $expected_interval_count / $intervals_query['per_page'] );

			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				return $data;
			}

			$totals = $wpdb->get_results(
				"SELECT
						{$selections}
					FROM
						{$table_name}
						{$totals_query['from_clause']}
					JOIN
						{$wpdb->prefix}wc_order_stats ON {$table_name}.order_id = {$wpdb->prefix}wc_order_stats.order_id
					WHERE
						1=1
						{$totals_query['where_time_clause']}
						{$totals_query['where_clause']}",
				ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			if ( null === $totals ) {
				return new \WP_Error( 'woocommerce_reports_taxes_stats_result_failed', __( 'Sorry, fetching revenue data failed.', 'woocommerce-admin' ) );
			}
			$segmenter             = new Segmenter( $query_args, $this->report_columns );
			$totals[0]['segments'] = $segmenter->get_totals_segments( $totals_query, $table_name );

			$this->update_intervals_sql_params( $intervals_query, $query_args, $db_interval_count, $expected_interval_count, $table_name );

			if ( '' !== $selections ) {
				$selections = ', ' . $selections;
			}

			$intervals = $wpdb->get_results(
				"SELECT
							MAX({$table_name}.date_created) AS datetime_anchor,
							{$intervals_query['select_clause']} AS time_interval
							{$selections}
						FROM
							{$table_name}
							{$intervals_query['from_clause']}
						JOIN
							{$wpdb->prefix}wc_order_stats ON {$table_name}.order_id = {$wpdb->prefix}wc_order_stats.order_id
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
				return new \WP_Error( 'woocommerce_reports_taxes_stats_result_failed', __( 'Sorry, fetching tax data failed.', 'woocommerce-admin' ) );
			}

			$totals = (object) $this->cast_numbers( $totals[0] );

			$data = (object) array(
				'totals'    => $totals,
				'intervals' => $intervals,
				'total'     => $expected_interval_count,
				'pages'     => $total_pages,
				'page_no'   => (int) $query_args['page'],
			);

			if ( TimeInterval::intervals_missing( $expected_interval_count, $db_interval_count, $intervals_query['per_page'], $query_args['page'], $query_args['order'], $query_args['orderby'], count( $intervals ) ) ) {
				$this->fill_in_missing_intervals( $db_intervals, $query_args['adj_after'], $query_args['adj_before'], $query_args['interval'], $data );
				$this->sort_intervals( $data, $query_args['orderby'], $query_args['order'] );
				$this->remove_extra_records( $data, $query_args['page'], $intervals_query['per_page'], $db_interval_count, $expected_interval_count, $query_args['orderby'], $query_args['order'] );
			} else {
				$this->update_interval_boundary_dates( $query_args['after'], $query_args['before'], $query_args['interval'], $data->intervals );
			}
			$segmenter->add_intervals_segments( $data, $intervals_query, $table_name );
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

}
