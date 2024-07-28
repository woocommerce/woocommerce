<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Admin\API\Reports;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Admin\API\Reports\SqlQuery;

/**
 * Trait to contain *Stats specific methods for data stores.
 *
 * @see Automattic\WooCommerce\Admin\API\Reports\DataStore
 */
trait StatsDataStoreTrait {
	/**
	 * Initialize query objects.
	 */
	protected function initialize_queries() {
		$this->clear_all_clauses();
		unset( $this->subquery );
		$table_name = self::get_db_table_name();

		$this->total_query = new SqlQuery( $this->context . '_total' );
		$this->total_query->add_sql_clause( 'from', $table_name );

		$this->interval_query = new SqlQuery( $this->context . '_interval' );
		$this->interval_query->add_sql_clause( 'from', $table_name );
		$this->interval_query->add_sql_clause( 'group_by', 'time_interval' );
	}

	/**
	 * Returns the stats report data based on normalized parameters.
	 * Prepares the basic intervals and object structure
	 * Will be called by `get_data` if there is no data in cache.
	 * Will call `get_noncached_stats_data` to fetch the actual data.
	 *
	 * @see get_data
	 * @param array $query_args Query parameters.
	 * @return stdClass|WP_Error Data object, or error.
	 */
	public function get_noncached_data( $query_args ) {
		$params                  = $this->get_limit_params( $query_args );
		$expected_interval_count = TimeInterval::intervals_between( $query_args['after'], $query_args['before'], $query_args['interval'] );
		$total_pages             = (int) ceil( $expected_interval_count / $params['per_page'] );

		// Default, empty data object.
		$data = (object) array(
			'totals'    => null,
			'intervals' => array(),
			'total'     => $expected_interval_count,
			'pages'     => $total_pages,
			'page_no'   => (int) $query_args['page'],
		);
		// If the requested page is out off range, return the deault empty object.
		if ( $query_args['page'] >= 1 && $query_args['page'] <= $total_pages ) {
			// Fetch the actual data.
			$data = $this->get_noncached_stats_data( $query_args, $params, $data, $expected_interval_count );
		}

		return $data;
	}
}
