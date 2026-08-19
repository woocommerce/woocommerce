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
 * Both of those are dropped here. Everything that owns its stock is kept: a parent managing stock at
 * product level, a variation that manages its own, a variation whose parent manages none (its stock
 * status is its own), and a variable product with no variations to speak for it.
 *
 * Ownership is read from `wc_product_meta_lookup.stock_quantity`, which the lookup table leaves NULL
 * for anything not managing its own stock. The rest of this report already filters on that table.
 *
 * @internal
 */
trait ExcludeMirroredStockTrait {

	/**
	 * Get a JOIN fragment giving the exclusion clause access to the parent's stock.
	 *
	 * The caller is responsible for joining `wc_product_meta_lookup` for the row itself, under that
	 * same alias.
	 *
	 * @param string $posts_alias Table or alias the outer query uses for the posts table. Defaults to the posts table name.
	 * @return string SQL fragment starting with LEFT JOIN.
	 */
	protected static function get_parent_stock_lookup_join( $posts_alias = '' ) {
		global $wpdb;

		// $posts_alias is a hardcoded table name or alias supplied by the calling report query, never user input.
		$posts_alias = $posts_alias ? $posts_alias : $wpdb->posts;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return " LEFT JOIN {$wpdb->wc_product_meta_lookup} stock_report_parent_lookup
			ON stock_report_parent_lookup.product_id = {$posts_alias}.post_parent ";
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Get a WHERE fragment that drops rows mirroring stock owned by another row in the report.
	 *
	 * Requires the joins from self::get_parent_stock_lookup_join(), plus `wc_product_meta_lookup`
	 * joined for the row itself.
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
		) ";
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
