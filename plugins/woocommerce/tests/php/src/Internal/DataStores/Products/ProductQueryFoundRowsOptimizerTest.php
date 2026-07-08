<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\DataStores\Products;

use Automattic\WooCommerce\Internal\DataStores\Products\ProductQueryFoundRowsOptimizer;
use WC_Helper_Product;
use wpdb;

/**
 * Tests for ProductQueryFoundRowsOptimizer, exercised through the main product archive query that
 * WC_Query wires it into.
 */
class ProductQueryFoundRowsOptimizerTest extends \WC_Unit_Test_Case {

	use RunsMainProductQueryTrait;

	/**
	 * Reset the memoized is_supported() result so each stubbed server version is re-probed.
	 */
	public function tearDown(): void {
		$is_supported = new \ReflectionProperty( ProductQueryFoundRowsOptimizer::class, 'is_supported' );
		$is_supported->setAccessible( true );
		$is_supported->setValue( null, null );

		parent::tearDown();
	}

	/**
	 * @testdox The product archive drops SQL_CALC_FOUND_ROWS but still reports the correct pagination total.
	 */
	public function test_product_query_uses_separate_count_for_pagination(): void {
		for ( $i = 0; $i < 3; $i++ ) {
			WC_Helper_Product::create_simple_product();
		}

		$per_page = 2;

		// Baseline: the native SQL_CALC_FOUND_ROWS total/pagination.
		add_filter( 'woocommerce_product_query_use_separate_count_query', '__return_false' );
		$baseline = $this->run_main_product_query( $per_page );
		remove_filter( 'woocommerce_product_query_use_separate_count_query', '__return_false' );

		// Optimized: force the gate on (independent of the feature flag and DB engine) so the dedicated
		// COUNT path runs over the same clauses.
		add_filter( 'woocommerce_product_query_use_separate_count_query', '__return_true' );
		$optimized = $this->run_main_product_query( $per_page );
		remove_filter( 'woocommerce_product_query_use_separate_count_query', '__return_true' );

		$this->assertNotNull( $optimized['used_sql_calc_found_rows'], 'The main product query should have run.' );
		$this->assertTrue( $baseline['used_sql_calc_found_rows'], 'The baseline query should use SQL_CALC_FOUND_ROWS.' );
		$this->assertFalse( $optimized['used_sql_calc_found_rows'], 'The optimized query should drop SQL_CALC_FOUND_ROWS.' );
		$this->assertGreaterThanOrEqual( 3, $optimized['found_posts'], 'The created products should be counted.' );
		$this->assertSame( $baseline['found_posts'], $optimized['found_posts'], 'The separate COUNT must match the SQL_CALC_FOUND_ROWS total.' );
		$this->assertSame( $baseline['max_num_pages'], $optimized['max_num_pages'], 'max_num_pages must match the baseline, computed from the separate COUNT.' );

		// Regression guard: the strip must not disable WordPress's split-query optimization. The optimized
		// data query must still be the ID-only split form (SELECT wp_posts.ID), exactly as the baseline is.
		$this->assertTrue( $baseline['split_used'], 'The baseline query should use WordPress split-query.' );
		$this->assertTrue( $optimized['split_used'], 'The optimization must keep WordPress split-query enabled.' );
		$this->assertTrue( $optimized['selects_ids_only'], 'The optimized main query must still select wp_posts.ID, not wp_posts.*.' );
	}

	/**
	 * @testdox On the non-split path the optimization leaves native SQL_CALC_FOUND_ROWS in place (no double count).
	 */
	public function test_non_split_query_falls_back_to_native_count(): void {
		for ( $i = 0; $i < 3; $i++ ) {
			WC_Helper_Product::create_simple_product();
		}

		$per_page = 2;

		add_filter( 'woocommerce_product_query_use_separate_count_query', '__return_true' );
		add_filter( 'split_the_query', '__return_false' );
		$result = $this->run_main_product_query( $per_page );
		remove_filter( 'split_the_query', '__return_false' );
		remove_filter( 'woocommerce_product_query_use_separate_count_query', '__return_true' );

		$this->assertFalse( $result['split_used'], 'split_the_query was forced off, so no ID request should run.' );
		$this->assertTrue( $result['used_sql_calc_found_rows'], 'Without the split path the request keeps native SQL_CALC_FOUND_ROWS (posts_request_ids never fires).' );
		$this->assertGreaterThanOrEqual( 3, $result['found_posts'], 'The native count must still report the created products.' );
	}

	/**
	 * @testdox The woocommerce_product_query_use_separate_count_query filter can restore SQL_CALC_FOUND_ROWS.
	 */
	public function test_product_query_separate_count_can_be_disabled(): void {
		for ( $i = 0; $i < 3; $i++ ) {
			WC_Helper_Product::create_simple_product();
		}

		$per_page = 2;

		add_filter( 'woocommerce_product_query_use_separate_count_query', '__return_true' );
		$optimized = $this->run_main_product_query( $per_page );
		remove_filter( 'woocommerce_product_query_use_separate_count_query', '__return_true' );

		add_filter( 'woocommerce_product_query_use_separate_count_query', '__return_false' );
		$disabled = $this->run_main_product_query( $per_page );
		remove_filter( 'woocommerce_product_query_use_separate_count_query', '__return_false' );

		$this->assertFalse( $optimized['used_sql_calc_found_rows'], 'Returning true from the filter removes SQL_CALC_FOUND_ROWS.' );
		$this->assertTrue( $disabled['used_sql_calc_found_rows'], 'Returning false from the filter restores SQL_CALC_FOUND_ROWS.' );
		$this->assertSame( $optimized['found_posts'], $disabled['found_posts'], 'The reported total is unchanged whether or not the optimization is enabled.' );
	}

	/**
	 * @testdox is_supported() only accepts MySQL 8.0+, rejecting MariaDB and older MySQL.
	 * @dataProvider data_provider_is_supported
	 *
	 * @param string $server_version The version string SELECT VERSION() reports.
	 * @param bool   $expected       Whether the separate-COUNT rewrite should be used on that server.
	 */
	public function test_is_supported_gates_on_mysql_8( string $server_version, bool $expected ): void {
		global $wpdb;
		$real_wpdb = $wpdb;

		// Stub the pieces wc_get_server_database_version() reads: is_mysql, use_mysqli (via the
		// magic accessors, __isset included since empty() checks it first) and the SELECT VERSION() result.
		$mock           = $this->createMock( wpdb::class );
		$mock->is_mysql = true;
		$mock->method( '__isset' )->willReturnCallback( fn( $name ) => 'use_mysqli' === $name );
		$mock->method( '__get' )->willReturnCallback( fn( $name ) => 'use_mysqli' === $name ? true : null );
		$mock->method( 'get_var' )->willReturn( $server_version );

		$wpdb = $mock; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		try {
			$this->assertSame( $expected, ProductQueryFoundRowsOptimizer::is_supported() );
		} finally {
			$wpdb = $real_wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * Server version strings and whether the separate-COUNT rewrite wins on them.
	 *
	 * @return array<string, array{0:string, 1:bool}>
	 */
	public function data_provider_is_supported(): array {
		return array(
			'MariaDB 10.11'         => array( '10.11.18-MariaDB-ubu2204', false ),
			'MariaDB legacy prefix' => array( '5.5.5-10.6.2-MariaDB', false ),
			'MySQL 5.7'             => array( '5.7.44', false ),
			'MySQL 8.0'             => array( '8.0.46', true ),
			'MySQL 8.4'             => array( '8.4.0', true ),
		);
	}
}
