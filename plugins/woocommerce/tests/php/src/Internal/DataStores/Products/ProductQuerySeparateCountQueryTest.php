<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\DataStores\Products;

use WC_Helper_Product;
use WP_Query;

/**
 * Tests for ProductQuerySeparateCountQuery, exercised through the main product archive query that
 * WC_Query wires it into.
 */
class ProductQuerySeparateCountQueryTest extends \WC_Unit_Test_Case {

	/**
	 * Run the main product archive query and return its found_posts, max_num_pages and whether the SQL
	 * request still used SQL_CALC_FOUND_ROWS.
	 *
	 * @param int $posts_per_page Posts per page for the query (forces a LIMIT so the found-rows path runs).
	 * @return array{found_posts:int, max_num_pages:int, used_sql_calc_found_rows:bool}
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

	/**
	 * @testdox The product archive drops SQL_CALC_FOUND_ROWS but still reports the correct pagination total.
	 */
	public function test_product_query_uses_separate_count_for_pagination() {
		for ( $i = 0; $i < 3; $i++ ) {
			WC_Helper_Product::create_simple_product();
		}

		$per_page = 2;

		// Baseline: the native SQL_CALC_FOUND_ROWS total/pagination.
		add_filter( 'woocommerce_product_query_use_separate_count_query', '__return_false' );
		$baseline = $this->run_main_product_query( $per_page );
		remove_filter( 'woocommerce_product_query_use_separate_count_query', '__return_false' );

		// Optimized: a dedicated COUNT query over the same clauses.
		$optimized = $this->run_main_product_query( $per_page );

		$this->assertNotNull( $optimized['used_sql_calc_found_rows'], 'The main product query should have run.' );
		$this->assertTrue( $baseline['used_sql_calc_found_rows'], 'The baseline query should use SQL_CALC_FOUND_ROWS.' );
		$this->assertFalse( $optimized['used_sql_calc_found_rows'], 'The optimized query should drop SQL_CALC_FOUND_ROWS.' );
		$this->assertGreaterThanOrEqual( 3, $optimized['found_posts'], 'The created products should be counted.' );
		$this->assertSame( $baseline['found_posts'], $optimized['found_posts'], 'The separate COUNT must match the SQL_CALC_FOUND_ROWS total.' );
		$this->assertSame( $baseline['max_num_pages'], $optimized['max_num_pages'], 'max_num_pages must match the baseline, computed from the separate COUNT.' );
	}

	/**
	 * @testdox The woocommerce_product_query_use_separate_count_query filter can restore SQL_CALC_FOUND_ROWS.
	 */
	public function test_product_query_separate_count_can_be_disabled() {
		for ( $i = 0; $i < 3; $i++ ) {
			WC_Helper_Product::create_simple_product();
		}

		$per_page = 2;

		$optimized = $this->run_main_product_query( $per_page );

		add_filter( 'woocommerce_product_query_use_separate_count_query', '__return_false' );
		$disabled = $this->run_main_product_query( $per_page );
		remove_filter( 'woocommerce_product_query_use_separate_count_query', '__return_false' );

		$this->assertFalse( $optimized['used_sql_calc_found_rows'], 'By default the optimization removes SQL_CALC_FOUND_ROWS.' );
		$this->assertTrue( $disabled['used_sql_calc_found_rows'], 'Returning false from the filter restores SQL_CALC_FOUND_ROWS.' );
		$this->assertSame( $optimized['found_posts'], $disabled['found_posts'], 'The reported total is unchanged whether or not the optimization is enabled.' );
	}
}
