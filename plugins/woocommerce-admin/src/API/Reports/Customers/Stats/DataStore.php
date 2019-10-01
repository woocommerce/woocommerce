<?php
/**
 * API\Reports\Customers\Stats\DataStore class file.
 *
 * @package WooCommerce Admin/Classes
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Customers\Stats;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore as CustomersDataStore;
use \Automattic\WooCommerce\Admin\API\Reports\DataStoreInterface;

/**
 * API\Reports\Customers\Stats\DataStore.
 */
class DataStore extends CustomersDataStore implements DataStoreInterface {
	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'customers_count'     => 'intval',
		'avg_orders_count'    => 'floatval',
		'avg_total_spend'     => 'floatval',
		'avg_avg_order_value' => 'floatval',
	);

	/**
	 * SQL columns to select in the db query and their mapping to SQL code.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'customers_count'     => 'COUNT( * ) as customers_count',
		'avg_orders_count'    => 'AVG( orders_count ) as avg_orders_count',
		'avg_total_spend'     => 'AVG( total_spend ) as avg_total_spend',
		'avg_avg_order_value' => 'AVG( avg_order_value ) as avg_avg_order_value',
	);

	/**
	 * Cache identifier.
	 *
	 * @var string
	 */
	protected $cache_key = 'customers_stats';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// This space intentionally left blank (to avoid parent constructor).
	}

	/**
	 * Returns the report data based on parameters supplied by the user.
	 *
	 * @param array $query_args  Query parameters.
	 * @return stdClass|WP_Error Data.
	 */
	public function get_data( $query_args ) {
		global $wpdb;

		$customers_table_name = $wpdb->prefix . self::TABLE_NAME;

		// These defaults are only partially applied when used via REST API, as that has its own defaults.
		$defaults   = array(
			'per_page' => get_option( 'posts_per_page' ),
			'page'     => 1,
			'order'    => 'DESC',
			'orderby'  => 'date_registered',
			'fields'   => '*',
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
			$data = (object) array(
				'customers_count'     => 0,
				'avg_orders_count'    => 0,
				'avg_total_spend'     => 0.0,
				'avg_avg_order_value' => 0.0,
			);

			$selections       = $this->selected_columns( $query_args );
			$sql_query_params = $this->get_sql_query_params( $query_args );

			$report_data = $wpdb->get_results(
				"SELECT {$selections} FROM
				(
					SELECT
						(
							CASE WHEN COUNT( order_id ) = 0
								THEN NULL
								ELSE COUNT( order_id )
							END
						) as orders_count,
      					SUM( gross_total ) as total_spend, 
						( SUM( gross_total ) / COUNT( order_id ) ) as avg_order_value
					FROM
						{$customers_table_name}
						{$sql_query_params['from_clause']}
					WHERE
						1=1
						{$sql_query_params['where_time_clause']}
						{$sql_query_params['where_clause']}
					GROUP BY
						{$customers_table_name}.customer_id
					HAVING
						1=1
						{$sql_query_params['having_clause']}
				) as tt",
				ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			if ( null === $report_data ) {
				return $data;
			}

			$data = (object) $this->cast_numbers( $report_data[0] );

			$this->set_cached_data( $cache_key, $data );
		}

		return $data;
	}
}
