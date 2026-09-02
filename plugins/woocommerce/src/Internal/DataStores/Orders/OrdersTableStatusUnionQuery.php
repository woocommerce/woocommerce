<?php
/**
 * OrdersTableStatusUnionQuery class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Rewrites a "multiple statuses, ordered by creation date" order query (such as the default order admin list
 * screen query) as a UNION ALL of single-status queries.
 *
 * `status IN (...)` prevents the `type_status_date` index from serving a global `date_created_gmt` ordering, so on
 * large stores the optimizer may pick a plan that scans millions of rows for a single page. One branch per (type,
 * status) pair is fully served — filter and order — by `type_status_date`, leaving the outer query to merge a few
 * pre-sorted rows.
 *
 * Eligibility (exact clause match) and the store-size gate are documented at the methods that enforce them
 * (get_sql() and is_enabled()).
 */
class OrdersTableStatusUnionQuery {

	/**
	 * Maximum number of UNION branches (one per type/status pair). Queries needing more branches than this are
	 * left untouched.
	 */
	private const MAX_BRANCHES = 24;

	/**
	 * Maximum row depth (offset + row count). Each UNION branch must fetch up to this many rows, so deeply
	 * paginated queries are left untouched.
	 */
	private const MAX_ROWS = 2_000;

	/**
	 * Minimum number of orders (per the order count cache) matching the queried types and statuses for the rewrite
	 * to be enabled by default. A rough threshold for where a mis-planned query gets user-visible, not a measured
	 * crossover.
	 */
	private const MIN_ORDER_COUNT = 500_000;

	/**
	 * The query being rewritten.
	 *
	 * @var OrdersTableQuery
	 */
	private OrdersTableQuery $query;

	/**
	 * The orders table name.
	 *
	 * @var string
	 */
	private string $orders_table;

	/**
	 * Constructor.
	 *
	 * @param OrdersTableQuery $query The query to rewrite.
	 *
	 * @since 11.0.0
	 */
	public function __construct( OrdersTableQuery $query ) {
		$this->query        = $query;
		$this->orders_table = $query->get_table_name( 'orders' );
	}

	/**
	 * Returns the rewritten SQL query, or NULL when the query is not eligible for the rewrite.
	 *
	 * @param string[] $clauses          Associative array with the final 'fields', 'join', 'where', 'groupby',
	 *                                   'orderby' and 'limits' clauses (the latter four including their keywords).
	 * @param bool     $suppress_filters Whether the query is running with filters suppressed.
	 * @return string|null The rewritten SQL query, or NULL if the query is not eligible.
	 *
	 * @since 11.0.0
	 */
	public function get_sql( array $clauses, bool $suppress_filters ): ?string {
		// Each step either extracts a validated piece of the rewrite or bails out (returns null) when the query
		// isn't the plain "type + status, ordered by creation date" shape we can safely rewrite. The UNION is only
		// assembled once every piece is in place.
		if ( ! $this->has_rewritable_clause_shape( $clauses ) ) {
			return null;
		}

		$direction = $this->extract_order_direction( $clauses['orderby'] ?? '' );
		if ( null === $direction ) {
			return null;
		}

		$limit = $this->extract_limit( $clauses['limits'] ?? '' );
		if ( null === $limit ) {
			return null;
		}

		$types_and_statuses = $this->extract_types_and_statuses();
		if ( null === $types_and_statuses ) {
			return null;
		}
		list( $types, $statuses ) = $types_and_statuses;

		if ( ! $this->is_enabled( $types, $statuses, $suppress_filters ) ) {
			return null;
		}

		if ( ! $this->where_matches_type_status_args( $clauses['where'] ?? '' ) ) {
			return null;
		}

		list( $offset, $row_count ) = $limit;

		return $this->build_union_sql( $types, $statuses, $direction, $offset + $row_count, $clauses['limits'] ?? '' );
	}

	/**
	 * Checks the fixed clauses (selected fields, join, group by) are exactly those of the plain order id list
	 * query. Any join, grouping or extra selected field means the query isn't a candidate for the rewrite.
	 *
	 * @param string[] $clauses The query clauses (see get_sql()).
	 * @return bool Whether the clause shape is rewritable.
	 */
	private function has_rewritable_clause_shape( array $clauses ): bool {
		return '' === ( $clauses['join'] ?? '' )
			&& '' === ( $clauses['groupby'] ?? '' )
			&& "{$this->orders_table}.id" === ( $clauses['fields'] ?? '' );
	}

	/**
	 * Extracts the sort direction from the ORDER BY clause, or NULL when it isn't an ORDER BY on date_created_gmt
	 * alone (the only ordering the type_status_date index can satisfy within each branch).
	 *
	 * @param string $orderby The ORDER BY clause, including the keyword.
	 * @return string|null 'ASC', 'DESC', or NULL when ineligible.
	 */
	private function extract_order_direction( string $orderby ): ?string {
		foreach ( array( 'ASC', 'DESC' ) as $direction ) {
			if ( "ORDER BY {$this->orders_table}.date_created_gmt {$direction}" === $orderby ) {
				return $direction;
			}
		}

		return null;
	}

	/**
	 * Extracts the offset and row count from the LIMIT clause, or NULL when the query is unlimited or too deeply
	 * paginated to benefit (each branch would have to fetch offset + row count rows). The offset + row count cap
	 * also rejects the "unlimited" sentinel row count.
	 *
	 * @param string $limits The LIMIT clause, including the keyword.
	 * @return int[]|null Array of [ offset, row count ], or NULL when ineligible.
	 */
	private function extract_limit( string $limits ): ?array {
		if ( ! preg_match( '/^LIMIT (\d+), (\d+)$/', $limits, $limit_parts ) ) {
			return null;
		}

		$offset    = (int) $limit_parts[1];
		$row_count = (int) $limit_parts[2];

		if ( $row_count < 1 || ( $offset + $row_count ) > self::MAX_ROWS ) {
			return null;
		}

		return array( $offset, $row_count );
	}

	/**
	 * Extracts the queried order types and statuses, or NULL when they don't form a rewritable set: the 'type' and
	 * 'status' args must both be set and contain only non-empty strings, cover at least two statuses (a single
	 * status is already served by the type_status_date index), and stay within the branch cap.
	 *
	 * @return array[]|null Array of [ types, statuses ] (each a list of unique strings), or NULL when ineligible.
	 */
	private function extract_types_and_statuses(): ?array {
		if ( ! $this->query->arg_isset( 'type' ) || ! $this->query->arg_isset( 'status' ) ) {
			return null;
		}

		$types    = array_values( array_unique( (array) $this->query->get( 'type' ) ) );
		$statuses = array_values( array_unique( (array) $this->query->get( 'status' ) ) );

		foreach ( array_merge( $types, $statuses ) as $value ) {
			if ( ! is_string( $value ) || '' === $value ) {
				return null;
			}
		}

		if ( count( $statuses ) < 2 || ( count( $types ) * count( $statuses ) ) > self::MAX_BRANCHES ) {
			return null;
		}

		return array( $types, $statuses );
	}

	/**
	 * Checks the WHERE clause is exactly the one the 'type' and 'status' args generate (same order as
	 * OrdersTableQuery::process_orders_table_query_args()). Any other contribution — other query args or filters —
	 * disqualifies the query. Both columns are of the 'string' type per the OrdersTableDataStore column mappings.
	 *
	 * @param string $where The WHERE clause (without the WHERE keyword).
	 * @return bool Whether the WHERE clause is exactly the type/status one.
	 */
	private function where_matches_type_status_args( string $where ): bool {
		$expected_where = '1=1';
		foreach ( array( 'status', 'type' ) as $arg_key ) {
			$clause          = $this->query->where( $this->orders_table, $arg_key, '=', $this->query->get( $arg_key ), 'string' );
			$expected_where .= " AND ({$clause})";
		}

		return $where === $expected_where;
	}

	/**
	 * Assembles the UNION ALL rewrite from the validated pieces. Each branch is wrapped in a derived table (instead
	 * of using parenthesized UNION members) so that the per-branch ORDER BY + LIMIT is honored across MySQL,
	 * MariaDB and SQLite.
	 *
	 * @param string[] $types       Queried order types.
	 * @param string[] $statuses    Queried order statuses.
	 * @param string   $direction   Sort direction ('ASC' or 'DESC').
	 * @param int      $branch_rows Number of rows each branch must fetch (offset + row count).
	 * @param string   $limits      The outer LIMIT clause, including the keyword.
	 * @return string The rewritten SQL query.
	 */
	private function build_union_sql( array $types, array $statuses, string $direction, int $branch_rows, string $limits ): string {
		global $wpdb;

		$branches = array();

		foreach ( $types as $type ) {
			foreach ( $statuses as $status ) {
				$branch = $wpdb->prepare(
					"SELECT id, date_created_gmt FROM {$this->orders_table} WHERE type = %s AND status = %s ORDER BY date_created_gmt {$direction} LIMIT {$branch_rows}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$type,
					$status
				);

				$branches[] = 'SELECT id, date_created_gmt FROM ( ' . $branch . ' ) union' . count( $branches );
			}
		}

		return 'SELECT id FROM ( ' . implode( ' UNION ALL ', $branches ) . " ) candidates ORDER BY date_created_gmt {$direction} {$limits}";
	}

	/**
	 * Returns whether the rewrite should be used for the given types and statuses.
	 *
	 * Enabled by default once the matching order count reaches MIN_ORDER_COUNT. Counts come from
	 * OrderUtil::get_count_for_type() — the same facade the order admin list screen uses for its status counts —
	 * which reads the order count cache and computes (and caches) the counts on a miss.
	 *
	 * @param string[] $types            Queried order types.
	 * @param string[] $statuses         Queried order statuses.
	 * @param bool     $suppress_filters Whether the query is running with filters suppressed.
	 * @return bool Whether the rewrite should be used.
	 */
	private function is_enabled( array $types, array $statuses, bool $suppress_filters ): bool {
		$orders_count = 0;

		foreach ( $types as $type ) {
			$counts = OrderUtil::get_count_for_type( $type );

			foreach ( $statuses as $status ) {
				$orders_count += $counts[ $status ] ?? 0;
			}
		}

		$enabled = $orders_count >= self::MIN_ORDER_COUNT;

		if ( $suppress_filters ) {
			return $enabled;
		}

		/**
		 * Filters whether a query for multiple order statuses ordered by creation date may be rewritten as a
		 * UNION ALL of single-status queries for performance. The rewrite produces the same results and, even
		 * when enabled here, only applies to queries generated purely from the 'type' and 'status' query args
		 * (no search, meta or field filters), such as the default order admin list screen query.
		 *
		 * Hosts that know their database benefits from the rewrite regardless of store size (or that don't want
		 * to depend on the order count cache being warm) can force-enable it with
		 * add_filter( 'woocommerce_orders_table_query_status_union_optimization', '__return_true' ); the
		 * structural eligibility checks above still apply.
		 *
		 * @param bool             $enabled Whether the rewrite is enabled. Defaults to TRUE only when the cached
		 *                                  number of orders matching the queried types and statuses is at least
		 *                                  500,000; FALSE otherwise (including when the order count cache is cold).
		 * @param OrdersTableQuery $query   The OrdersTableQuery instance.
		 *
		 * @since 11.0.0
		 */
		return (bool) apply_filters( 'woocommerce_orders_table_query_status_union_optimization', $enabled, $this->query );
	}
}
