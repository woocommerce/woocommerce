<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\CacheController;
use Automattic\WooCommerce\Internal\ProductFilters\FilterDataProvider;
use Automattic\WooCommerce\Internal\ProductFilters\QueryClauses;

/**
 * Tests for the filter-combination cache entry cap in FilterData.
 */
class FilterDataCacheCapTest extends AbstractProductFiltersTest {

	/**
	 * System under test.
	 *
	 * @var \Automattic\WooCommerce\Internal\ProductFilters\FilterData
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$container = wc_get_container();
		$this->sut = $container->get( FilterDataProvider::class )->with( $container->get( QueryClauses::class ) );
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_product_filter_cache_max_entries' );
		delete_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT );
		parent::tearDown();
	}

	/**
	 * @testdox When the cache cap is reached, new combinations are skipped.
	 */
	public function test_cache_cap_skips_new_entries_when_limit_reached() {
		add_filter( 'woocommerce_product_filter_cache_max_entries', fn() => 2 );

		$vars_1 = array_filter(
			( new \WP_Query(
				array(
					'post_type' => 'product',
					'max_price' => 10,
				)
			) )->query_vars
		);
		$vars_2 = array_filter(
			( new \WP_Query(
				array(
					'post_type' => 'product',
					'max_price' => 20,
				)
			) )->query_vars
		);
		$vars_3 = array_filter(
			( new \WP_Query(
				array(
					'post_type' => 'product',
					'max_price' => 30,
				)
			) )->query_vars
		);

		$this->sut->get_stock_status_counts( $vars_1, array( 'instock', 'outofstock', 'onbackorder' ) );
		$this->sut->get_stock_status_counts( $vars_2, array( 'instock', 'outofstock', 'onbackorder' ) );

		$this->assertSame( 2, (int) get_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT ) );

		// Third call should be skipped — counter stays at 2.
		$this->sut->get_stock_status_counts( $vars_3, array( 'instock', 'outofstock', 'onbackorder' ) );

		$this->assertSame( 2, (int) get_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT ) );
	}

	/**
	 * @testdox When the cap is 0 (disabled), no counter transient is written.
	 */
	public function test_cache_cap_disabled_when_max_entries_is_zero() {
		add_filter( 'woocommerce_product_filter_cache_max_entries', '__return_zero' );

		$vars = array_filter( ( new \WP_Query( array( 'post_type' => 'product' ) ) )->query_vars );
		$this->sut->get_stock_status_counts( $vars, array( 'instock', 'outofstock', 'onbackorder' ) );

		$this->assertFalse( get_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT ) );
	}

	/**
	 * @testdox Cache invalidation resets the entry counter.
	 */
	public function test_invalidation_resets_entry_counter() {
		add_filter( 'woocommerce_product_filter_cache_max_entries', fn() => 100 );

		$vars = array_filter( ( new \WP_Query( array( 'post_type' => 'product' ) ) )->query_vars );
		$this->sut->get_stock_status_counts( $vars, array( 'instock', 'outofstock', 'onbackorder' ) );

		$this->assertGreaterThan( 0, (int) get_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT ) );

		wc_get_container()->get( CacheController::class )->invalidate_filter_data_cache();

		$this->assertFalse( get_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT ) );
	}

	/**
	 * @testdox Counter increments for each filter type, not just each unique combo.
	 */
	public function test_counter_increments_per_filter_type_per_combo() {
		add_filter( 'woocommerce_product_filter_cache_max_entries', fn() => 100 );

		$vars = array_filter( ( new \WP_Query( array( 'post_type' => 'product' ) ) )->query_vars );

		$this->sut->get_filtered_price( $vars );
		$this->sut->get_stock_status_counts( $vars, array( 'instock', 'outofstock', 'onbackorder' ) );
		$this->sut->get_rating_counts( $vars );

		// 3 filter types on the same query vars = 3 cache entries.
		$this->assertSame( 3, (int) get_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT ) );
	}

	/**
	 * @testdox Cap is reached after fewer unique combos when multiple filter types are used.
	 */
	public function test_cap_reached_faster_with_multiple_filter_types() {
		add_filter( 'woocommerce_product_filter_cache_max_entries', fn() => 5 );

		$vars_1 = array_filter(
			( new \WP_Query(
				array(
					'post_type' => 'product',
					'max_price' => 10,
				)
			) )->query_vars
		);
		$vars_2 = array_filter(
			( new \WP_Query(
				array(
					'post_type' => 'product',
					'max_price' => 20,
				)
			) )->query_vars
		);

		// First combo: 3 filter types = 3 entries.
		$this->sut->get_filtered_price( $vars_1 );
		$this->sut->get_stock_status_counts( $vars_1, array( 'instock', 'outofstock', 'onbackorder' ) );
		$this->sut->get_rating_counts( $vars_1 );

		$this->assertSame( 3, (int) get_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT ) );

		// Second combo: 2 more entries hits the cap (5), third is skipped.
		$this->sut->get_filtered_price( $vars_2 );
		$this->sut->get_stock_status_counts( $vars_2, array( 'instock', 'outofstock', 'onbackorder' ) );
		$this->sut->get_rating_counts( $vars_2 );

		// Counter stops at 5, not 6.
		$this->assertSame( 5, (int) get_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT ) );
	}
}
