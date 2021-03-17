<?php


namespace Automattic\WooCommerce\Internal\GraphQL\QueryTypes;

use Automattic\WooCommerce\Internal\GraphQL\BaseQueryListType;

/**
 * Class for the ProductAttributes list query type.
 */
class ProductAttributes extends BaseQueryListType {

	/**
	 * Get the type of the items that the returned list will contain.
	 *
	 * @return string The type of the items that the returned list will contain.
	 */
	public function get_item_type_class_name() {
		return ProductAttribute::class;
	}

	/**
	 * Get the total count of available items.
	 *
	 * @param array $extra_args Any extra arguments passed to the query (other than "offset" and "count").
	 * @return int The total count of available items.
	 */
	public function resolve_total_count( $extra_args ) {
		global $wpdb;

		$sql = "SELECT COUNT(1) FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name != '' ORDER BY attribute_name ASC";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_var( $sql );
	}

	/**
	 * Resolve the query.
	 *
	 * @param int   $offset How many items to skip.
	 * @param int   $count How many items to return.
	 * @param array $extra_args Any extra arguments passed to the query (other than "offset" and "count").
	 * @param array $field_selection The part of the ResolveInfo object wupplied by the GraphQL engine that corresponds to the 'items' object.
	 * @return array The list of items resolved.
	 */
	public function resolve_items( $offset, $count, $extra_args, $field_selection ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name != '' ORDER BY attribute_name ASC LIMIT %d OFFSET %d",
			$count,
			$offset
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $sql, ARRAY_A );

		return array_map(
			function( $row ) {
				return array(
					'id'           => $row['attribute_id'],
					'name'         => $row['attribute_label'],
					'slug'         => wc_attribute_taxonomy_name( $row['attribute_name'] ),
					'type'         => $row['attribute_type'],
					'order_by'     => $row['attribute_orderby'],
					'has_archives' => (bool) $row['attribute_public'],
				);
			},
			$rows
		);
	}
}
