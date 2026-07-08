<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\DataStores\Products;

use WP_Query;

/**
 * Test helper that runs a synthetic main product archive query, so tests can observe how WC_Query
 * and ProductQueryFoundRowsOptimizer shape the resulting SQL and pagination totals.
 */
trait RunsMainProductQueryTrait {

	/**
	 * Run the main product archive query and report how it was shaped.
	 *
	 * @param int $posts_per_page Posts per page for the query (forces a LIMIT so the found-rows path runs).
	 * @return array{found_posts:int, max_num_pages:int, used_sql_calc_found_rows:?bool, split_used:bool, selects_ids_only:bool}
	 *   used_sql_calc_found_rows: whether the *executed* main data query still carried SQL_CALC_FOUND_ROWS.
	 *   split_used: whether WordPress's split-query ID request ran (posts_request_ids fired).
	 *   selects_ids_only: whether that executed data query selected wp_posts.ID only (split preserved),
	 *   rather than the full wp_posts.* row form.
	 */
	private function run_main_product_query( int $posts_per_page ): array {
		global $wpdb;

		$main_request = null;
		$ids_request  = null;

		$is_product_main = function ( $query ) {
			return $query->is_main_query() && 'product_query' === $query->get( 'wc_query' );
		};

		// posts_request is the non-split full request; posts_request_ids is WP's split-query ID request.
		// Capture both at a very low priority so we see the final SQL after the optimizer's filters.
		$capture_request = function ( $request, $query ) use ( &$main_request, $is_product_main ) {
			if ( $is_product_main( $query ) ) {
				$main_request = $request;
			}
			return $request;
		};
		$capture_ids     = function ( $request, $query ) use ( &$ids_request, $is_product_main ) {
			if ( $is_product_main( $query ) ) {
				$ids_request = $request;
			}
			return $request;
		};

		// Make the synthetic main query resolve as the product archive (a raw WP_Query is not flagged
		// as one), so WC_Query::pre_get_posts() runs product_query() instead of returning early. Runs
		// at priority 5, before WC_Query::pre_get_posts() at the default priority 10.
		$as_product_archive = function ( $query ) use ( $posts_per_page ) {
			if ( $query->is_main_query() ) {
				$query->is_archive           = true;
				$query->is_post_type_archive = true;
				$query->set( 'posts_per_page', $posts_per_page );
			}
		};
		add_action( 'pre_get_posts', $as_product_archive, 5 );
		add_filter( 'posts_request', $capture_request, 99999, 2 );
		add_filter( 'posts_request_ids', $capture_ids, 99999, 2 );

		// Start cold: a previous run's primed query-result / post caches would let an identical query
		// short-circuit WordPress's split_the_query path, so each run observes the real executed SQL.
		wp_cache_flush();

		global $wp_the_query, $wp_query;
		$previous_wp_the_query = $wp_the_query;
		$previous_wp_query     = $wp_query;

		$query        = new WP_Query();
		$wp_the_query = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query     = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$query->query( array( 'post_type' => 'product' ) );

		// The data query WordPress actually ran: the split ID request when split fired, else the full request.
		$split_used = null !== $ids_request;
		$executed   = $split_used ? (string) $ids_request : (string) $main_request;
		// Collapse whitespace: WordPress spreads the SELECT across lines (SELECT ... wp_posts.ID\n FROM ...).
		$normalized = (string) preg_replace( '/\s+/', ' ', $executed );
		$result     = array(
			'found_posts'              => (int) $query->found_posts,
			'max_num_pages'            => (int) $query->max_num_pages,
			'used_sql_calc_found_rows' => null === $main_request && null === $ids_request ? null : ( false !== stripos( $normalized, 'SQL_CALC_FOUND_ROWS' ) ),
			'split_used'               => $split_used,
			'selects_ids_only'         => false !== strpos( $normalized, "{$wpdb->posts}.ID FROM" ) && false === strpos( $normalized, "{$wpdb->posts}.* FROM" ),
		);

		remove_filter( 'posts_request', $capture_request, 99999 );
		remove_filter( 'posts_request_ids', $capture_ids, 99999 );
		remove_action( 'pre_get_posts', $as_product_archive, 5 );
		$wp_the_query = $previous_wp_the_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query     = $previous_wp_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		return $result;
	}
}
