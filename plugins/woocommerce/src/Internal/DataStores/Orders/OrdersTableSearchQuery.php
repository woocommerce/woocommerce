<?php

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

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
	 * Creates the JOIN and WHERE clauses needed to execute a search of orders.
	 *
	 * @internal
	 *
	 * @param OrdersTableQuery $query The order query object.
	 */
	public function __construct( OrdersTableQuery $query ) {
		$this->query       = $query;
		$this->search_term = "'" . esc_sql( '%' . urldecode( $query->get( 's' ) ) . '%' ) . "'";
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
		$orders_table = $this->query->get_table_name( 'orders' );
		$meta_table   = $this->query->get_table_name( 'meta' );
		$items_table  = $this->query->get_table_name( 'items' );

		return "
			LEFT JOIN $meta_table AS search_query_meta ON search_query_meta.order_id = $orders_table.id
			LEFT JOIN $items_table AS search_query_items ON search_query_items.order_id = $orders_table.id
		";
	}

	/**
	 * Generates the necessary WHERE clauses for the order search to be performed.
	 *
	 * @throws Exception May be triggered if a table name cannot be determined.
	 *
	 * @return string
	 */
	private function generate_where(): string {
		$where             = '';
		$meta_fields       = $this->get_meta_fields_to_be_searched();
		$possible_order_id = (string) absint( $this->query->get( 's' ) );

		// Support the passing of an order ID as the search term.
		if ( (string) $this->query->get( 's' ) === $possible_order_id ) {
			$where = $this->query->get_table_name( 'orders' ) . '.id = ' . $possible_order_id . ' OR ';
		}

		$where .= "
			(
				search_query_meta.meta_key IN ( $meta_fields )
				AND search_query_meta.meta_value LIKE $this->search_term
			)
			OR search_query_items.order_item_name LIKE $this->search_term
		";

		return " ( $where ) ";
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
			array(
				'_billing_address_index',
				'_shipping_address_index',
			)
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
