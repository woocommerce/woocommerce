<?php
/**
 * API\Reports\Stock\ExcludeMirroredStockTrait trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\API\Reports\Stock;

defined( 'ABSPATH' ) || exit;

/**
 * Shared SQL for keeping rows that only mirror another row's stock out of the stock report.
 *
 * Within a variable product the stock lives in exactly one place, and which place that is depends on
 * `_manage_stock` rather than on post type:
 *
 * - When the parent manages stock, it holds the quantity and WooCommerce syncs its stock status onto
 *   every variation that does not manage its own. Those variations repeat the same units.
 * - When the parent does not manage stock, its status is derived from its variations, so listing it
 *   repeats, or contradicts, the rows below it.
 *
 * Both are dropped only in favour of a row the report can actually show: each clause checks the
 * other row's post status against the ones the calling query returns. The variations of a draft
 * parent stay listed for that reason, as do the variations of a private parent the current user
 * may not read.
 *
 * Ownership is read from `wc_product_meta_lookup.stock_quantity`, which the lookup table leaves NULL
 * for anything not managing its own stock.
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

		// This file declares strict types, and $sql arrives from a filter any third party can return.
		$sql = is_string( $sql ) ? $sql : '';

		if ( strstr( $sql, 'wc_product_meta_lookup' ) ) {
			return $sql;
		}

		$posts_alias = $posts_alias ? $posts_alias : $wpdb->posts;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $posts_alias is a hardcoded table name or alias supplied by the calling report query, never user input.
		return $sql . " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup
			ON {$posts_alias}.ID = wc_product_meta_lookup.product_id ";
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Append every join the exclusion clause reads, in the one order that works.
	 *
	 * The parent joins name the lookup table as well, so adding them first would leave the guard in
	 * self::append_stock_lookup_join() unable to tell them from the row's own join, and the query
	 * would go on to read an alias nothing bound.
	 *
	 * @param string $sql         Join clause the calling report query has built so far.
	 * @param string $posts_alias Table or alias the outer query uses for the posts table. Defaults to the posts table name.
	 * @return string The join clause, with every table the exclusion reads joined.
	 */
	protected static function append_mirrored_stock_joins( $sql, $posts_alias = '' ) {
		global $wpdb;

		$sql = self::append_stock_lookup_join( $sql, $posts_alias );

		$posts_alias = $posts_alias ? $posts_alias : $wpdb->posts;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $posts_alias is a hardcoded table name or alias supplied by the calling report query, never user input.
		return $sql . " LEFT JOIN {$wpdb->wc_product_meta_lookup} stock_report_parent_lookup
			ON stock_report_parent_lookup.product_id = {$posts_alias}.post_parent
			LEFT JOIN {$wpdb->posts} stock_report_parent
			ON stock_report_parent.ID = {$posts_alias}.post_parent ";
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Get the post statuses the report query returns for every row it can reach.
	 *
	 * This mirrors the clause WP_Query builds for a query that names no status of its own, so the
	 * exclusion below only ever defers to a row the same query could return. WP_Query reads the
	 * capability off each post type it queries; both post types the report queries register theirs
	 * against `product`, so that one answers for both.
	 *
	 * A private post readable only by its author is left out, since authorship is a property of the
	 * row rather than of its status. Erring that way lists a row twice; erring the other way reports
	 * no stock for it at all.
	 *
	 * @return array Post statuses, in the order WP_Query considers them.
	 */
	protected static function get_reportable_post_statuses() {
		$statuses  = array_values( get_post_stati( array( 'public' => true ) ) );
		$post_type = get_post_type_object( 'product' );

		if ( is_user_logged_in() && $post_type instanceof \WP_Post_Type && current_user_can( $post_type->cap->read_private_posts ) ) {
			$statuses = array_merge( $statuses, array_values( get_post_stati( array( 'private' => true ) ) ) );
		}

		return $statuses;
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
	 * @param array  $statuses    Post statuses the calling query returns. Defaults to the ones its own WHERE names.
	 * @return string SQL fragment starting with AND.
	 */
	protected static function get_mirrored_stock_exclusion_clause( $posts_alias = '', $statuses = array() ) {
		global $wpdb;

		$posts_alias = $posts_alias ? $posts_alias : $wpdb->posts;
		$statuses    = $statuses ? $statuses : array( 'publish', 'private' );
		$status_list = "'" . implode( "', '", array_map( 'sanitize_key', $statuses ) ) . "'";

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $posts_alias is a hardcoded table name or alias supplied by the calling report query. $status_list holds post status names, which carry the same characters sanitize_key() leaves behind. Neither is user input.
		return " AND NOT (
			{$posts_alias}.post_type = 'product'
			AND wc_product_meta_lookup.stock_quantity IS NULL
			AND EXISTS (
				SELECT 1 FROM {$wpdb->posts} AS stock_report_variations
				WHERE stock_report_variations.post_parent = {$posts_alias}.ID
				AND stock_report_variations.post_type = 'product_variation'
				AND stock_report_variations.post_status IN ( {$status_list} )
			)
		)
		AND NOT (
			{$posts_alias}.post_type = 'product_variation'
			AND wc_product_meta_lookup.stock_quantity IS NULL
			AND stock_report_parent_lookup.stock_quantity IS NOT NULL
			AND IFNULL( stock_report_parent.post_status, '' ) IN ( {$status_list} )
		) ";
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
