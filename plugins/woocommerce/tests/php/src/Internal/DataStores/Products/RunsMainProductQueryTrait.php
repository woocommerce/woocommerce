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
	 * Run the main product archive query and return its found_posts, max_num_pages and whether the SQL
	 * request still used SQL_CALC_FOUND_ROWS.
	 *
	 * @param int $posts_per_page Posts per page for the query (forces a LIMIT so the found-rows path runs).
	 * @return array{found_posts:int, max_num_pages:int, used_sql_calc_found_rows:?bool}
	 */
	private function run_main_product_query( int $posts_per_page ): array {
		$used_sql_calc_found_rows = null;
		$capture                  = function ( $request, $query ) use ( &$used_sql_calc_found_rows ) {
			if ( $query->is_main_query() && 'product_query' === $query->get( 'wc_query' ) ) {
				$used_sql_calc_found_rows = false !== stripos( $request, 'SQL_CALC_FOUND_ROWS' );
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
		add_filter( 'posts_request', $capture, 99999, 2 );

		global $wp_the_query, $wp_query;
		$previous_wp_the_query = $wp_the_query;
		$previous_wp_query     = $wp_query;

		$query        = new WP_Query();
		$wp_the_query = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query     = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$query->query( array( 'post_type' => 'product' ) );

		$result = array(
			'found_posts'              => (int) $query->found_posts,
			'max_num_pages'            => (int) $query->max_num_pages,
			'used_sql_calc_found_rows' => $used_sql_calc_found_rows,
		);

		remove_filter( 'posts_request', $capture, 99999 );
		remove_action( 'pre_get_posts', $as_product_archive, 5 );
		$wp_the_query = $previous_wp_the_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query     = $previous_wp_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		return $result;
	}
}
