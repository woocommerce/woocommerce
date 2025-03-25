<?php

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;
use Exception;

/**
 * Creates the join and where clauses needed to perform an order search using Custom Order Tables.
 *
 * @internal
 */
class OrdersTableSearchQuery {
	/**
	 * Holds the Orders Table Query object.
	 *
	 * @var OrdersTableQuery
	 */
	private $query;

	/**
	 * Holds the search term to be used in the WHERE clauses.
	 *
	 * @var string
	 */
	private $search_term;

	/**
	 * Limits the search to a specific field.
	 *
	 * @var string[]
	 */
	private $search_filters;

	/**
	 * Creates the JOIN and WHERE clauses needed to execute a search of orders.
	 *
	 * @internal
	 *
	 * @param OrdersTableQuery $query The order query object.
	 */
	public function __construct( OrdersTableQuery $query ) {
		$this->query          = $query;
		$this->search_term    = $query->get( 's' );
		$this->search_filters = $this->sanitize_search_filters( $query->get( 'search_filter' ) ?? '' );
	}

	/**
	 * Sanitize search filter param.
	 *
	 * @param string $search_filter Search filter param.
	 *
	 * @return array Array of search filters.
	 */
	private function sanitize_search_filters( string $search_filter ): array {
		$core_filters = array(
			'order_id',
			'transaction_id',
			'customer_email',
			'customers', // customers also searches in meta.
			'products',
		);

		if ( 'all' === $search_filter || '' === $search_filter ) {
			return $core_filters;
		} else {
			return array( $search_filter );
		}
	}

	/**
	 * Supplies an array of clauses to be used in an order query.
	 *
	 * @internal
	 * @throws Exception If unable to generate either the JOIN or WHERE SQL fragments.
	 *
	 * @return array {
	 *     @type string $join  JOIN clause.
	 *     @type string $where WHERE clause.
	 * }
	 */
	public function get_sql_clauses(): array {
		return array(
			'join'  => array( $this->generate_join() ),
			'where' => array( $this->generate_where() ),
		);
	}

	/**
	 * Generates the necessary JOIN clauses for the order search to be performed.
	 *
	 * @throws Exception May be triggered if a table name cannot be determined.
	 *
	 * @return string
	 */
	private function generate_join(): string {
		$join = array();

		foreach ( $this->search_filters as $search_filter ) {
			$join[] = $this->generate_join_for_search_filter( $search_filter );
		}

		return implode( ' ', $join );
	}

	/**
	 * Generate JOIN clause for a given search filter.
	 * Right now we only have the products filter that actually does a JOIN, but in the future we may add more -- for example, custom order fields, payment tokens, and so on. This function makes it easier to add more filters in the future.
	 *
	 * If a search filter needs a JOIN, it will also need a WHERE clause.
	 *
	 * @param string $search_filter Name of the search filter.
	 *
	 * @return string JOIN clause.
	 */
	private function generate_join_for_search_filter( $search_filter ): string {
		/**
		 * Filter to support adding a custom order search filter.
		 * Provide a JOIN clause for a new search filter. This should be used along with `woocommerce_hpos_admin_search_filters`
		 * to declare a new custom filter, and `woocommerce_hpos_generate_where_for_search_filter` to generate the WHERE
		 * clause.
		 *
		 * Hardcoded JOINS (products) cannot be modified using this filter for consistency.
		 *
		 * @since 8.9.0
		 *
		 * @param string $join The JOIN clause.
		 * @param string $search_term The search term.
		 * @param string $search_filter The search filter. Use this to bail early if this is not filter you are interested in.
		 * @param OrdersTableQuery $query The order query object.
		 */
		return apply_filters(
			'woocommerce_hpos_generate_join_for_search_filter',
			'',
			$this->search_term,
			$search_filter,
			$this->query
		);
	}

	/**
	 * Generates the necessary WHERE clauses for the order search to be performed.
	 *
	 * @throws Exception May be triggered if a table name cannot be determined.
	 *
	 * @return string
	 */
	private function generate_where(): string {
		$where             = array();
		$possible_order_id = (string) absint( $this->search_term );
		$order_table       = $this->query->get_table_name( 'orders' );

		// Support the passing of an order ID as the search term.
		if ( (string) $this->query->get( 's' ) === $possible_order_id ) {
			$where[] = "`$order_table`.id = $possible_order_id";
		}

		foreach ( $this->search_filters as $search_filter ) {
			$search_where = trim( $this->generate_where_for_search_filter( $search_filter ) );
			if ( strlen( $search_where ) > 0 ) {
				$where[] = $search_where;
			}
		}

		$where_statement = implode( ' OR ', $where );

		return ( strlen( $where_statement ) > 0 ) ? " ( $where_statement ) " : '';
	}

	/**
	 * Generates WHERE clause for a given search filter. Right now we only have the products and customers filters that actually use WHERE, but in the future we may add more -- for example, custom order fields, payment tokens and so on. This function makes it easier to add more filters in the future.
	 *
	 * @param string $search_filter Name of the search filter.
	 *
	 * @return string WHERE clause.
	 */
	private function generate_where_for_search_filter( string $search_filter ): string {
		global $wpdb;

		$order_table = $this->query->get_table_name( 'orders' );

		if ( 'customer_email' === $search_filter ) {
			return $wpdb->prepare(
				"`$order_table`.billing_email LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $order_table is hardcoded.
				$wpdb->esc_like( $this->search_term ) . '%'
			);
		}

		if ( 'order_id' === $search_filter && is_numeric( $this->search_term ) ) {
			return $wpdb->prepare(
				"`$order_table`.id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $order_table is hardcoded.
				absint( $this->search_term )
			);
		}

		if ( 'transaction_id' === $search_filter ) {
			return $wpdb->prepare(
				"`$order_table`.transaction_id LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $order_table is hardcoded.
				'%' . $wpdb->esc_like( $this->search_term ) . '%'
			);
		}

		if ( 'products' === $search_filter ) {
			return $this->get_where_for_products();
		}

		if ( 'customers' === $search_filter ) {
			return $this->get_where_for_customers();
		}

		/**
		 * Filter to support adding a custom order search filter.
		 * Provide a WHERE clause for a custom search filter via this filter. This should be used with the
		 * `woocommerce_hpos_admin_search_filters` to declare a new custom filter, and optionally also with the
		 * `woocommerce_hpos_generate_join_for_search_filter` filter if a join is also needed.
		 *
		 * Hardcoded filters (products, customers, ID and email) cannot be modified using this filter for consistency.
		 *
		 * @since 8.9.0
		 *
		 * @param string $where WHERE clause to add to the search query.
		 * @param string $search_term The search term.
		 * @param string $search_filter Name of the search filter. Use this to bail early if this is not the filter you are looking for.
		 * @param OrdersTableQuery $query The order query object.
		 */
		return apply_filters(
			'woocommerce_hpos_generate_where_for_search_filter',
			'',
			$this->search_term,
			$search_filter,
			$this->query
		);
	}

	/**
	 * Helper function to generate the WHERE clause for products search. Uses FTS when available.
	 *
	 * @return string|null WHERE clause for products search.
	 */
	private function get_where_for_products() {
		global $wpdb;
		$db_util      = wc_get_container()->get( DatabaseUtil::class );
		$items_table  = $this->query->get_table_name( 'items' );
		$orders_table = $this->query->get_table_name( 'orders' );
		$fts_enabled  = get_option( CustomOrdersTableController::HPOS_FTS_INDEX_OPTION ) === 'yes' && get_option( CustomOrdersTableController::HPOS_FTS_ORDER_ITEM_INDEX_CREATED_OPTION ) === 'yes';

		if ( $fts_enabled ) {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $orders_table and $items_table are hardcoded.
			return $wpdb->prepare(
				"
$orders_table.id in (
	SELECT order_id FROM $items_table search_query_items WHERE
	MATCH ( search_query_items.order_item_name ) AGAINST ( %s IN BOOLEAN MODE )
)
",
				$wpdb->esc_like( $db_util->sanitise_boolean_fts_search_term( $this->search_term ) ),
			);
			// phpcs:enable
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $orders_table and $items_table are hardcoded.
		return $wpdb->prepare(
			"
$orders_table.id in (
	SELECT order_id FROM $items_table search_query_items WHERE
	search_query_items.order_item_name LIKE %s
)
",
			'%' . $wpdb->esc_like( $this->search_term ) . '%'
		);
		// phpcs:enable
	}

	/**
	 * Helper function to generate the WHERE clause for customers search. Uses FTS when available.
	 *
	 * @return string|null WHERE clause for customers search.
	 */
	private function get_where_for_customers() {
		global $wpdb;
		$order_table   = $this->query->get_table_name( 'orders' );
		$address_table = $this->query->get_table_name( 'addresses' );

		$db_util = wc_get_container()->get( DatabaseUtil::class );

		$fts_enabled = get_option( CustomOrdersTableController::HPOS_FTS_INDEX_OPTION ) === 'yes' && get_option( CustomOrdersTableController::HPOS_FTS_ADDRESS_INDEX_CREATED_OPTION ) === 'yes';

		if ( $fts_enabled ) {
			$matchers = "$address_table.first_name, $address_table.last_name, $address_table.company, $address_table.address_1, $address_table.address_2, $address_table.city, $address_table.state, $address_table.postcode, $address_table.country, $address_table.email";

			// Support for phone was added in 9.4.
			if ( version_compare( get_option( 'woocommerce_db_version' ), '9.4.0', '>=' ) ) {
				$matchers .= ", $address_table.phone";
			}

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $order_table and $address_table are hardcoded.
			return $wpdb->prepare(
				"
$order_table.id IN (
	SELECT order_id FROM $address_table WHERE
	MATCH( $matchers ) AGAINST ( %s IN BOOLEAN MODE )
)
",
				$wpdb->esc_like( $db_util->sanitise_boolean_fts_search_term( $this->search_term ) )
			);
			// phpcs:enable
		}

		$meta_sub_query = $this->generate_where_for_meta_table();
		return "`$order_table`.id IN ( $meta_sub_query ) ";
	}

	/**
	 * Generates where clause for meta table.
	 *
	 * Note we generate the where clause as a subquery to be used by calling function inside the IN clause. This is against the general wisdom for performance, but in this particular case, a subquery is able to use the order_id-meta_key-meta_value index, which is not possible with a join.
	 *
	 * Since it can use the index, which otherwise would not be possible, it is much faster than both LEFT JOIN or SQL_CALC approach that could have been used.
	 *
	 * @return string The where clause for meta table.
	 */
	private function generate_where_for_meta_table(): string {
		global $wpdb;
		$meta_table  = $this->query->get_table_name( 'meta' );
		$meta_fields = $this->get_meta_fields_to_be_searched();

		if ( '' === $meta_fields ) {
			return '-1';
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $meta_fields is already escaped before imploding, $meta_table is hardcoded.
		return $wpdb->prepare(
			"
SELECT search_query_meta.order_id
FROM $meta_table as search_query_meta
WHERE search_query_meta.meta_key IN ( $meta_fields )
AND search_query_meta.meta_value LIKE %s
GROUP BY search_query_meta.order_id
",
			'%' . $wpdb->esc_like( $this->search_term ) . '%'
		);
		// phpcs:enable
	}

	/**
	 * Returns the order meta field keys to be searched.
	 *
	 * These will be returned as a single string, where the meta keys have been escaped, quoted and are
	 * comma-separated (ie, "'abc', 'foo'" - ready for inclusion in a SQL IN() clause).
	 *
	 * @return string
	 */
	private function get_meta_fields_to_be_searched(): string {
		$meta_fields_to_search = array(
			'_billing_address_index',
			'_shipping_address_index',
		);

		/**
		 * Controls the order meta keys to be included in search queries.
		 *
		 * This hook is used when Custom Order Tables are in use: the corresponding hook when CPT-orders are in use
		 * is 'woocommerce_shop_order_search_fields'.
		 *
		 * @since 7.0.0
		 *
		 * @param array
		 */
		$meta_keys = apply_filters(
			'woocommerce_order_table_search_query_meta_keys',
			$meta_fields_to_search
		);

		$meta_keys = (array) array_map(
			function ( string $meta_key ): string {
				return "'" . esc_sql( wc_clean( $meta_key ) ) . "'";
			},
			$meta_keys
		);

		return implode( ',', $meta_keys );
	}
}
