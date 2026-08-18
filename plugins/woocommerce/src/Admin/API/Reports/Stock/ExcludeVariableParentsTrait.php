<?php
/**
 * API\Reports\Stock\ExcludeVariableParentsTrait trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\API\Reports\Stock;

defined( 'ABSPATH' ) || exit;

/**
 * Shared SQL for keeping variable parent products out of the stock report.
 *
 * A variable product holds no sellable stock of its own. Every figure the report can show for
 * it is either derived from its variations or, when stock is managed at product level, a copy
 * of what each variation already reports. Listing the parent next to its variations therefore
 * duplicates rows, and when the two disagree it contradicts them.
 *
 * @internal
 */
trait ExcludeVariableParentsTrait {

	/**
	 * Get a WHERE fragment that drops products having at least one variation.
	 *
	 * Products without variations are kept, so a variable product that has none is still
	 * reported instead of disappearing from the report altogether.
	 *
	 * @param string $posts_alias Table or alias the outer query uses for the posts table. Defaults to the posts table name.
	 * @return string SQL fragment starting with AND, or an empty string when the exclusion is filtered off.
	 */
	protected static function get_variable_parents_exclusion_clause( $posts_alias = '' ) {
		/**
		 * Filters whether variable parent products are excluded from the stock report and its summary.
		 *
		 * Variations are reported individually and carry the stock that can actually be sold, so the
		 * parent row duplicates them. Return false to list parents as well, as WooCommerce did before
		 * 11.2.0.
		 *
		 * @param bool $exclude Whether to exclude variable parent products. Default true.
		 *
		 * @since 11.2.0
		 */
		if ( ! apply_filters( 'woocommerce_analytics_stock_report_exclude_variable_parents', true ) ) {
			return '';
		}

		global $wpdb;

		// $posts_alias is a hardcoded table name or alias supplied by the calling report query, never user input.
		$posts_alias = $posts_alias ? $posts_alias : $wpdb->posts;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return " AND NOT EXISTS (
			SELECT 1 FROM {$wpdb->posts} AS stock_report_variations
			WHERE stock_report_variations.post_parent = {$posts_alias}.ID
			AND stock_report_variations.post_type = 'product_variation'
			AND stock_report_variations.post_status IN ( 'publish', 'private' )
		) ";
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
