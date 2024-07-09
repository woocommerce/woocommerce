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
}
