<?php
/**
 * OrdersTableStatusUnionQuery class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Caches\OrderCountCache;

defined( 'ABSPATH' ) || exit;

/**
 * Rewrites a "multiple statuses, ordered by creation date" order query (such as the default order admin list screen
 * query) as a UNION ALL of single-status queries.
 *
 * `status IN (...)` prevents the `type_status_date` index from providing a global `date_created_gmt` ordering, so
 * the optimizer must either sort all matching rows or walk the `date_created` index while filtering. On large
 * stores it may choose a plan that examines millions of rows to produce a single page of results, depending on the
 * optimizer heuristics available (e.g. the MySQL `prefer_ordering_index` switch). With one branch per (type,
 * status) pair, each branch is fully served — filtering and ordering — by the `type_status_date` index regardless
 * of optimizer behavior or status selectivity, and the outer query only needs to merge a handful of pre-sorted
 * candidate rows.
 *
 * The rewrite is skipped unless the final query clauses are byte-identical to those generated purely by the 'type'
 * and 'status' query args, which guarantees that queries customized in any way (other query args, search, meta
 * queries, or modifications via the 'woocommerce_orders_table_query_clauses' filter) are never altered. Queries
 * modified via the 'woocommerce_orders_table_query_sql' filter are not rewritten either: build_query() only
 * attempts the rewrite when that filter returned the query unchanged.
 *
 * By default the rewrite is also gated by store size: on stores below the order count threshold the regular query
 * is already fast even with a suboptimal plan, while each UNION branch adds a small constant cost.
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
	private const MAX_ROWS = 2000;

	/**
	 * Minimum number of orders (per the order count cache) matching the queried types and statuses for the
	 * rewrite to be enabled by default. An educated guess at the store size where a mis-planned query becomes
	 * user-visible, not a measured crossover: the rewrite never beats a well-planned regular query, so this
	 * only balances its small constant cost against the risk of a slow plan on bigger tables.
	 */
	private const MIN_ORDER_COUNT = 500000;

	/**
	 * The query being rewritten.
	 *
	 * @var OrdersTableQuery
	 */
	private $query;

	/**
	 * Constructor.
	 *
	 * @param OrdersTableQuery $query The query to rewrite.
	 *
	 * @since 11.0.0
	 */
	public function __construct( OrdersTableQuery $query ) {
		$this->query = $query;
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
		global $wpdb;

		$orders_table = $this->query->get_table_name( 'orders' );

		$fields  = $clauses['fields'] ?? '';
		$join    = $clauses['join'] ?? '';
		$where   = $clauses['where'] ?? '';
		$groupby = $clauses['groupby'] ?? '';
		$orderby = $clauses['orderby'] ?? '';
		$limits  = $clauses['limits'] ?? '';

		if ( '' !== $join || '' !== $groupby || "{$orders_table}.id" !== $fields ) {
			return null;
		}

		// Only an ORDER BY on date_created_gmt alone can be satisfied by the type_status_date index within each branch.
		$direction = null;
		foreach ( array( 'ASC', 'DESC' ) as $_direction ) {
			if ( "ORDER BY {$orders_table}.date_created_gmt {$_direction}" === $orderby ) {
				$direction = $_direction;
			}
		}

		if ( is_null( $direction ) ) {
			return null;
		}

		// Unlimited or deeply paginated queries gain nothing from the rewrite: each branch would have to fetch
		// (offset + row count) rows. The digit cap also rejects the "unlimited" sentinel row count.
		if ( ! preg_match( '/^LIMIT (\d{1,7}), (\d{1,7})$/', $limits, $limit_parts ) ) {
			return null;
		}

		$offset    = (int) $limit_parts[1];
		$row_count = (int) $limit_parts[2];

		if ( $row_count < 1 || ( $offset + $row_count ) > self::MAX_ROWS ) {
			return null;
		}

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

		// A single status doesn't need the rewrite (the type_status_date index already provides the ordering).
		if ( count( $statuses ) < 2 || ( count( $types ) * count( $statuses ) ) > self::MAX_BRANCHES ) {
			return null;
		}

		if ( ! $this->is_enabled( $types, $statuses, $suppress_filters ) ) {
			return null;
		}

		// Require the WHERE clause to be exactly the one the 'type' and 'status' args generate (same order as
		// OrdersTableQuery::process_orders_table_query_args()). Any other contribution — other query args or
		// filters — disqualifies the query. Both columns are of the 'string' type per the OrdersTableDataStore
		// column mappings.
		$expected_where = '1=1';
		foreach ( array( 'status', 'type' ) as $arg_key ) {
			$clause          = $this->query->where( $orders_table, $arg_key, '=', $this->query->get( $arg_key ), 'string' );
			$expected_where .= " AND ({$clause})";
		}

		if ( $where !== $expected_where ) {
			return null;
		}

		// Each branch is wrapped in a derived table (instead of using parenthesized UNION members) so that the
		// per-branch ORDER BY + LIMIT is honored across MySQL, MariaDB and SQLite.
		$branch_rows = $offset + $row_count;
		$branches    = array();

		foreach ( $types as $type ) {
			foreach ( $statuses as $status ) {
				$branch = $wpdb->prepare(
					"SELECT id, date_created_gmt FROM {$orders_table} WHERE type = %s AND status = %s ORDER BY date_created_gmt {$direction} LIMIT {$branch_rows}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$type,
					$status
				);

				$branches[] = 'SELECT id, date_created_gmt FROM ( ' . $branch . ' ) wco_branch_' . count( $branches );
			}
		}

		return 'SELECT id FROM ( ' . implode( ' UNION ALL ', $branches ) . " ) wco_candidates ORDER BY date_created_gmt {$direction} {$limits}";
	}

	/**
	 * Returns whether the rewrite should be used for the given types and statuses.
	 *
	 * By default the rewrite is only enabled when the number of orders matching the queried types and statuses
	 * (per the order count cache) reaches MIN_ORDER_COUNT: below that, the regular query is fast even with a
	 * suboptimal execution plan, while each UNION branch adds a small constant cost. The counts are read from the
	 * cache without recomputing them, so the rewrite stays disabled while the cache is cold (it is kept warm by
	 * the order admin list screen, among others).
	 *
	 * @param string[] $types            Queried order types.
	 * @param string[] $statuses         Queried order statuses.
	 * @param bool     $suppress_filters Whether the query is running with filters suppressed.
	 * @return bool Whether the rewrite should be used.
	 */
	private function is_enabled( array $types, array $statuses, bool $suppress_filters ): bool {
		$count_cache  = new OrderCountCache();
		$orders_count = 0;

		foreach ( $types as $type ) {
			$counts = $count_cache->get( $type, $statuses );

			if ( is_null( $counts ) ) {
				$orders_count = 0;
				break;
			}

			$orders_count += array_sum( $counts );
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
