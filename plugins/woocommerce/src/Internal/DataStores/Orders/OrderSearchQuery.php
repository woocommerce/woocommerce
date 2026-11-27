<?php
/**
 * Order search on posts storage.
 *
 * @package WooCommerce
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the order ID matches used by posts-storage searches.
 *
 * @internal
 */
class OrderSearchQuery {
	/**
	 * Search term passed to the results filter.
	 *
	 * @var string
	 */
	private $term;

	/**
	 * Filtered order metadata keys.
	 *
	 * @var string[]
	 */
	private $fields;

	/**
	 * Exact ID match for numeric search terms.
	 *
	 * @var int|null
	 */
	private $id;

	/**
	 * Prepared statements returning matching IDs in legacy search order.
	 *
	 * @var string[]
	 */
	private $queries = array();

	/**
	 * Prepare the searches for a cleaned order search term.
	 *
	 * @param string $term Cleaned search term.
	 */
	public function __construct( $term ) {
		global $wpdb;

		$this->term = $term;
		$this->id   = is_numeric( $term ) ? absint( $term ) : null;
		/**
		 * Filters the order meta keys searched on posts storage.
		 *
		 * @since 1.5.6
		 * @param string[] $fields Order meta keys.
		 */
		$fields       = apply_filters(
			'woocommerce_shop_order_search_fields',
			array( '_billing_address_index', '_shipping_address_index', '_billing_last_name', '_billing_email', '_billing_phone' )
		);
		$this->fields = array_map( 'sanitize_text_field', $fields );
		if ( empty( $this->fields ) ) {
			return;
		}

		$term         = wc_clean( $term );
		$like         = '%' . $wpdb->esc_like( $term ) . '%';
		$placeholders = implode( ', ', array_fill( 0, count( $this->fields ), '%s' ) );

		$this->queries = array(
			$wpdb->prepare(
				"SELECT DISTINCT post_id AS order_id FROM {$wpdb->postmeta} WHERE meta_value LIKE %s AND meta_key IN ( $placeholders )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Only %s placeholders.
				array_merge( array( $like ), array_values( $this->fields ) )
			),
			$wpdb->prepare(
				"SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_name LIKE %s",
				$like
			),
			$wpdb->prepare(
				"SELECT DISTINCT os.order_id FROM {$wpdb->prefix}wc_order_stats os
				INNER JOIN {$wpdb->prefix}wc_customer_lookup cl ON os.customer_id = cl.customer_id
				INNER JOIN {$wpdb->usermeta} um ON cl.user_id = um.user_id
				WHERE um.meta_key IN ( 'billing_phone', 'shipping_phone' ) AND um.meta_value = %s",
				$term
			),
		);
	}

	/**
	 * Returns a single-column subquery for use before sorting and pagination.
	 *
	 * @return string
	 */
	public function get_subquery() {
		global $wpdb;
		$queries = $this->queries;
		if ( null !== $this->id ) {
			$queries[] = $wpdb->prepare( 'SELECT %d AS order_id', $this->id );
		}
		return $queries ? implode( ' UNION ', $queries ) : 'SELECT NULL AS order_id';
	}

	/**
	 * Returns the full list for callers of wc_order_search().
	 *
	 * @return int[]
	 */
	public function get_ids() {
		global $wpdb;
		$matches = array( null === $this->id ? array() : array( $this->id ) );
		foreach ( $this->queries as $query ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Each statement is prepared in the constructor.
			$matches[] = $wpdb->get_col( $query );
		}
		$order_ids = array_unique( array_merge( ...$matches ) );
		unset( $matches );

		/**
		 * Filter the order ids to be returned.
		 *
		 * @since 3.0.0
		 * @param array $order_ids The order ids.
		 * @param string $term The search term.
		 * @param array $search_fields The search fields.
		 * @return array
		 */
		$order_ids = apply_filters( 'woocommerce_shop_order_search_results', $order_ids, $this->term, $this->fields );

		return array_map( 'absint', $order_ids );
	}
}
