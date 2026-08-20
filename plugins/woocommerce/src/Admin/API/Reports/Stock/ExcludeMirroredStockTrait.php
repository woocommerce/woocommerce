<?php
/**
 * API\Reports\Stock\ExcludeMirroredStockTrait trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\API\Reports\Stock;

defined( 'ABSPATH' ) || exit;

/**
 * Shared SQL for keeping rows that only mirror someone else's stock out of the stock report.
 *
 * Within a variable product the stock lives in exactly one place, and which place that is depends on
 * `_manage_stock` rather than on post type:
 *
 * - When the parent manages stock, it holds the quantity and WooCommerce syncs its stock status onto
 *   every variation that does not manage its own. Those variations report the parent's numbers back,
 *   so listing them repeats the same units once per variation.
 * - When the parent does not manage stock, it holds no quantity of its own and its stock status is
 *   derived from its variations. Listing it repeats, or contradicts, the rows below it.
 *
 * Both of those are dropped here, but only in favour of a row the report can actually show. Each
 * clause checks the other row's post status against the ones the report queries, so nothing drops
 * out of the report altogether. The variations of a draft parent stay listed for that reason: the
 * parent is out of the report on its status alone, whether or not it manages the stock.
 *
 * Everything that owns its stock is kept: a parent managing stock at product level, a variation that
 * manages its own, a variation whose parent manages none (its stock status is its own), and a
 * variable product with no variations to speak for it.
 *
 * Ownership is read from `wc_product_meta_lookup.stock_quantity`, which the lookup table leaves NULL
 * for anything not managing its own stock. The rest of this report already filters on that table.
 *
 * @internal
 */
trait ExcludeMirroredStockTrait {

	/**
	 * Join `wc_product_meta_lookup` for the row itself, unless the query already carries it.
	 *
	 * The exclusion clause reads the row's own stock off that table under this exact alias, so a
	 * query that joined it already must not join it a second time.
	 *
	 * @param string $sql         Join clause the calling report query has built so far.
	 * @param string $posts_alias Table or alias the outer query uses for the posts table. Defaults to the posts table name.
	 * @return string The join clause, with the lookup table joined for the row.
	 */
	protected static function append_stock_lookup_join( $sql, $posts_alias = '' ) {
		global $wpdb;

		// $sql reaches this file, which is strict about types, from a filter any third party can return.
		$sql = is_string( $sql ) ? $sql : '';

		if ( strstr( $sql, 'wc_product_meta_lookup' ) ) {
			return $sql;
		}

		// $posts_alias is a hardcoded table name or alias supplied by the calling report query, never user input.
		$posts_alias = $posts_alias ? $posts_alias : $wpdb->posts;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $sql . " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup
			ON {$posts_alias}.ID = wc_product_meta_lookup.product_id ";
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Append every join the exclusion clause reads, in the one order that works.
	 *
	 * The parent joins name the lookup table as well, so adding them first would leave the guard in
	 * self::append_stock_lookup_join() unable to tell them from the row's own join, and the query
	 * would go on to read an alias nothing bound. Callers take the whole set from here rather than
	 * ordering the pieces themselves.
	 *
	 * @param string $sql         Join clause the calling report query has built so far.
	 * @param string $posts_alias Table or alias the outer query uses for the posts table. Defaults to the posts table name.
	 * @return string The join clause, with every table the exclusion reads joined.
	 */
	protected static function append_mirrored_stock_joins( $sql, $posts_alias = '' ) {
		global $wpdb;

		$sql = self::append_stock_lookup_join( $sql, $posts_alias );

		// $posts_alias is a hardcoded table name or alias supplied by the calling report query, never user input.
		$posts_alias = $posts_alias ? $posts_alias : $wpdb->posts;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $sql . " LEFT JOIN {$wpdb->wc_product_meta_lookup} stock_report_parent_lookup
			ON stock_report_parent_lookup.product_id = {$posts_alias}.post_parent
			LEFT JOIN {$wpdb->posts} stock_report_parent
			ON stock_report_parent.ID = {$posts_alias}.post_parent ";
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Get a WHERE fragment that drops rows mirroring stock owned by another row in the report.
	 *
	 * Requires the joins from self::append_mirrored_stock_joins().
	 *
	 * A query that already matches on a stock quantity has no use for this: only a row that owns its
	 * stock carries one, so every row this would drop is filtered out by the quantity alone.
	 *
	 * @param string $posts_alias Table or alias the outer query uses for the posts table. Defaults to the posts table name.
	 * @return string SQL fragment starting with AND.
	 */
	protected static function get_mirrored_stock_exclusion_clause( $posts_alias = '' ) {
		global $wpdb;

		// $posts_alias is a hardcoded table name or alias supplied by the calling report query, never user input.
		$posts_alias = $posts_alias ? $posts_alias : $wpdb->posts;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return " AND NOT (
			{$posts_alias}.post_type = 'product'
			AND wc_product_meta_lookup.stock_quantity IS NULL
			AND EXISTS (
				SELECT 1 FROM {$wpdb->posts} AS stock_report_variations
				WHERE stock_report_variations.post_parent = {$posts_alias}.ID
				AND stock_report_variations.post_type = 'product_variation'
				AND stock_report_variations.post_status IN ( 'publish', 'private' )
			)
		)
		AND NOT (
			{$posts_alias}.post_type = 'product_variation'
			AND wc_product_meta_lookup.stock_quantity IS NULL
			AND stock_report_parent_lookup.stock_quantity IS NOT NULL
			AND stock_report_parent.post_status IN ( 'publish', 'private' )
		) ";
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
