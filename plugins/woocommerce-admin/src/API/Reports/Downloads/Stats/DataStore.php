<?php
/**
 * API\Reports\Downloads\Stats\DataStore class file.
 *
 * @package WooCommerce Admin/Classes
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Downloads\Stats;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\API\Reports\Downloads\DataStore as DownloadsDataStore;
use \Automattic\WooCommerce\Admin\API\Reports\DataStoreInterface;
use \Automattic\WooCommerce\Admin\API\Reports\TimeInterval;

/**
 * API\Reports\Downloads\Stats\DataStore.
 */
class DataStore extends DownloadsDataStore implements DataStoreInterface {

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'download_count' => 'intval',
	);

	/**
	 * SQL columns to select in the db query and their mapping to SQL code.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'download_count' => 'COUNT(DISTINCT download_log_id) as download_count',
	);

	/**
	 * Cache identifier.
	 *
	 * @var string
	 */
	protected $cache_key = 'downloads_stats';

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
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
			'orderby'  => 'date',
			'fields'   => '*',
			'interval' => 'week',
			'before'   => TimeInterval::default_before(),
			'after'    => TimeInterval::default_after(),
		);
		$query_args = wp_parse_args( $query_args, $defaults );
		$this->normalize_timezones( $query_args, $defaults );

		/*
		 * We need to get the cache key here because
		 * parent::update_intervals_sql_params() modifies $query_args.
		 */
		$cache_key = $this->get_cache_key( $query_args );
		$data      = $this->get_cached_data( $cache_key );

		if ( false === $data ) {
			$selections       = $this->selected_columns( $query_args );
			$sql_query_params = $this->get_sql_query_params( $query_args );
			$totals_query     = array_merge( array(), $this->get_time_period_sql_params( $query_args, $table_name ) );
			$intervals_query  = array_merge( array(), $this->get_intervals_sql_params( $query_args, $table_name ) );

			$totals_query['where_clause']        .= $sql_query_params['where_clause'];
			$totals_query['from_clause']         .= $sql_query_params['from_clause'];
			$intervals_query['where_clause']     .= $sql_query_params['where_clause'];
			$intervals_query['from_clause']      .= $sql_query_params['from_clause'];
			$intervals_query['select_clause']     = str_replace( 'date_created', 'timestamp', $intervals_query['select_clause'] );
			$intervals_query['where_time_clause'] = str_replace( 'date_created', 'timestamp', $intervals_query['where_time_clause'] );

			$db_intervals = $wpdb->get_col(
				"SELECT
					{$intervals_query['select_clause']} AS time_interval
					FROM
						{$table_name}
						{$intervals_query['from_clause']}
					WHERE
						1=1
						{$intervals_query['where_time_clause']}
						{$intervals_query['where_clause']}
					GROUP BY
					time_interval"
			); // WPCS: cache ok, DB call ok, , unprepared SQL ok.

			$db_records_count = count( $db_intervals );

			$expected_interval_count = TimeInterval::intervals_between( $query_args['after'], $query_args['before'], $query_args['interval'] );
			$total_pages             = (int) ceil( $expected_interval_count / $intervals_query['per_page'] );
			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				return array();
			}

			$this->update_intervals_sql_params( $intervals_query, $query_args, $db_records_count, $expected_interval_count, $table_name );
			$intervals_query['where_time_clause'] = str_replace( 'date_created', 'timestamp', $intervals_query['where_time_clause'] );

			$totals = $wpdb->get_results(
				"SELECT
						{$selections}
					FROM
						{$table_name}
						{$totals_query['from_clause']}
					WHERE
						1=1
						{$totals_query['where_time_clause']}
						{$totals_query['where_clause']}",
				ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			if ( null === $totals ) {
				return new \WP_Error( 'woocommerce_reports_downloads_stats_result_failed', __( 'Sorry, fetching downloads data failed.', 'woocommerce-admin' ) );
			}

			if ( '' !== $selections ) {
				$selections = ', ' . $selections;
			}

			$intervals = $wpdb->get_results(
				"SELECT
					MAX(timestamp) AS datetime_anchor,
					{$intervals_query['select_clause']} AS time_interval
					{$selections}
					FROM
						{$table_name}
						{$intervals_query['from_clause']}
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
				return new \WP_Error( 'woocommerce_reports_downloads_stats_result_failed', __( 'Sorry, fetching downloads data failed.', 'woocommerce-admin' ) );
			}

			$totals = (object) $this->cast_numbers( $totals[0] );
			$data   = (object) array(
				'totals'    => $totals,
				'intervals' => $intervals,
				'total'     => $expected_interval_count,
				'pages'     => $total_pages,
				'page_no'   => (int) $query_args['page'],
			);

			if ( $this->intervals_missing( $expected_interval_count, $db_records_count, $intervals_query['per_page'], $query_args['page'], $query_args['order'], $query_args['orderby'], count( $intervals ) ) ) {
				$this->fill_in_missing_intervals( $db_intervals, $query_args['adj_after'], $query_args['adj_before'], $query_args['interval'], $data );
				$this->sort_intervals( $data, $query_args['orderby'], $query_args['order'] );
				$this->remove_extra_records( $data, $query_args['page'], $intervals_query['per_page'], $db_records_count, $expected_interval_count, $query_args['orderby'], $query_args['order'] );
			} else {
				$this->update_interval_boundary_dates( $query_args['after'], $query_args['before'], $query_args['interval'], $data->intervals );
			}
			$this->create_interval_subtotals( $data->intervals );

			$this->set_cached_data( $cache_key, $data );
		}

		return $data;
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
}
