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
		delete_transient( CacheController::CACHE_KEYS_OPTION );
		parent::tearDown();
	}

	/**
	 * @testdox When the cache cap is reached, the oldest entry is evicted.
	 */
	public function test_cache_cap_evicts_oldest_entry_when_limit_reached() {
		add_filter( 'woocommerce_product_filter_cache_max_entries', fn() => 2 );

		// Three distinct query sets -> three distinct transient keys.
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

		$keys_after_two = get_transient( CacheController::CACHE_KEYS_OPTION );
		$this->assertIsArray( $keys_after_two );
		$this->assertCount( 2, $keys_after_two );

		$first_key = $keys_after_two[0];

		// Third call should evict the first key.
		$this->sut->get_stock_status_counts( $vars_3, array( 'instock', 'outofstock', 'onbackorder' ) );

		$keys_after_three = get_transient( CacheController::CACHE_KEYS_OPTION );
		$this->assertIsArray( $keys_after_three );
		$this->assertCount( 2, $keys_after_three );
		$this->assertNotContains( $first_key, $keys_after_three );
	}

	/**
	 * @testdox When the cap is 0 (disabled), no tracking transient is written.
	 */
	public function test_cache_cap_disabled_when_max_entries_is_zero() {
		add_filter( 'woocommerce_product_filter_cache_max_entries', '__return_zero' );

		$vars = array_filter( ( new \WP_Query( array( 'post_type' => 'product' ) ) )->query_vars );
		$this->sut->get_stock_status_counts( $vars, array( 'instock', 'outofstock', 'onbackorder' ) );

		$this->assertFalse( get_transient( CacheController::CACHE_KEYS_OPTION ) );
	}

	/**
	 * @testdox Cache invalidation clears the tracking transient.
	 */
	public function test_invalidation_clears_cache_keys_tracking_transient() {
		add_filter( 'woocommerce_product_filter_cache_max_entries', fn() => 100 );

		$vars = array_filter( ( new \WP_Query( array( 'post_type' => 'product' ) ) )->query_vars );
		$this->sut->get_stock_status_counts( $vars, array( 'instock', 'outofstock', 'onbackorder' ) );

		// Confirm something was tracked.
		$this->assertNotFalse( get_transient( CacheController::CACHE_KEYS_OPTION ) );

		// Invalidate all filter cache.
		$cache_controller = wc_get_container()->get( CacheController::class );
		$cache_controller->invalidate_filter_data_cache();

		$this->assertFalse( get_transient( CacheController::CACHE_KEYS_OPTION ) );
	}

	/**
	 * @testdox Re-using the same query vars does not add duplicate entries to the tracking list.
	 */
	public function test_same_query_vars_does_not_duplicate_tracking_entry() {
		add_filter( 'woocommerce_product_filter_cache_max_entries', fn() => 100 );

		$vars = array_filter( ( new \WP_Query( array( 'post_type' => 'product' ) ) )->query_vars );

		$this->sut->get_stock_status_counts( $vars, array( 'instock', 'outofstock', 'onbackorder' ) );
		$this->sut->get_stock_status_counts( $vars, array( 'instock', 'outofstock', 'onbackorder' ) );
		$this->sut->get_stock_status_counts( $vars, array( 'instock', 'outofstock', 'onbackorder' ) );

		$keys = get_transient( CacheController::CACHE_KEYS_OPTION );
		$this->assertIsArray( $keys );
		$this->assertCount( 1, $keys );
	}
}
