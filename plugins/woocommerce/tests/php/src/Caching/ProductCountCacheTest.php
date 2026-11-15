<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Tests\Caching;

use Automattic\WooCommerce\Caches\ProductCountCache;
use Automattic\WooCommerce\Enums\ProductStatus;

/**
 * Class ProductCountCacheTest.
 */
final class ProductCountCacheTest extends \WC_Unit_Test_Case {

	/**
	 * ProductCountCache instance.
	 *
	 * @var ProductCountCache
	 */
	private ProductCountCache $product_cache;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->product_cache = new ProductCountCache();
		$this->product_cache->flush( 'product' );
	}

	/**
	 * @testdox Known and third-party statuses can be set and retrieved by product type.
	 */
	public function test_cache_product_counts(): void {
		$unregistered_status = 'third-party-unregistered-status';
		$counts              = array(
			ProductStatus::PUBLISH => 5,
			ProductStatus::DRAFT   => 10,
			$unregistered_status   => 20,
		);

		foreach ( $counts as $status => $count ) {
			$this->product_cache->set( 'product', $status, $count );
		}

		$this->assertTrue( $this->product_cache->is_cached( 'product', ProductStatus::PUBLISH ) );
		$this->assertTrue( $this->product_cache->is_cached( 'product', ProductStatus::DRAFT ) );
		$this->assertTrue( $this->product_cache->is_cached( 'product', $unregistered_status ) );

		$this->assertSame( 5, $this->product_cache->get( 'product', array( ProductStatus::PUBLISH ) )[ ProductStatus::PUBLISH ] );
		$this->assertSame( 10, $this->product_cache->get( 'product', array( ProductStatus::DRAFT ) )[ ProductStatus::DRAFT ] );
		$this->assertSame( 20, $this->product_cache->get( 'product', array( $unregistered_status ) )[ $unregistered_status ] );

		// verify when a specific set of statuses isn't requested.
		$this->assertSame( 5, $this->product_cache->get( 'product' )[ ProductStatus::PUBLISH ] );
		$this->assertSame( 10, $this->product_cache->get( 'product' )[ ProductStatus::DRAFT ] );
		$this->assertSame( 20, $this->product_cache->get( 'product' )[ $unregistered_status ] );
	}

	/**
	 * @testdox Specific status slots are invalidated when flushed.
	 */
	public function test_flush_cache(): void {
		$this->product_cache->set( 'product', ProductStatus::PUBLISH, 5 );
		$this->product_cache->set( 'product', ProductStatus::DRAFT, 10 );
		$this->product_cache->flush( 'product', array( ProductStatus::PUBLISH, ProductStatus::DRAFT ) );
		$this->assertFalse( $this->product_cache->is_cached( 'product', ProductStatus::PUBLISH ) );
		$this->assertFalse( $this->product_cache->is_cached( 'product', ProductStatus::DRAFT ) );
	}

	/**
	 * @testdox get() returns null when any of the requested status slots has been evicted.
	 */
	public function test_get_returns_null_when_any_status_evicted(): void {
		$this->product_cache->set( 'product', ProductStatus::PUBLISH, 5 );
		$this->product_cache->set( 'product', ProductStatus::DRAFT, 10 );

		// Evict one slot; the multi-status query must return null so callers fall back to DB.
		$this->product_cache->flush( 'product', array( ProductStatus::DRAFT ) );

		$this->assertNull( $this->product_cache->get( 'product', array( ProductStatus::PUBLISH, ProductStatus::DRAFT ) ) );
	}

	/**
	 * @testdox All registered statuses are returned when no specific statuses are requested.
	 */
	public function test_cache_gets_all_statuses_when_no_statuses_are_provided(): void {
		foreach ( array_keys( get_post_stati() ) as $status ) {
			$this->product_cache->set( 'product', $status, 5 );
		}

		$cached = $this->product_cache->get( 'product' );
		foreach ( ProductStatus::get_all() as $status ) {
			$this->assertSame( 5, $cached[ $status ] );
		}
	}

	/**
	 * Data provider for corrupted statuses values scenarios.
	 *
	 * @return array
	 */
	public function provider_corrupted_statuses_values(): array {
		return array(
			'integers mixed with strings' => array( array( 'publish', 0, 'draft', 42 ) ),
			'all integers'                => array( array( 0, 1, 2 ) ),
			'non-array string'            => array( 'not-an-array' ),
			'non-array integer'           => array( 123 ),
		);
	}

	/**
	 * @testdox Corrupted saved statuses cache is purged and get() falls back to null.
	 * @dataProvider provider_corrupted_statuses_values
	 *
	 * @param mixed $corrupted_value The corrupted value to inject into the statuses cache.
	 */
	public function test_corrupted_statuses_cache_is_purged( $corrupted_value ): void {
		$this->product_cache->set( 'product', ProductStatus::PUBLISH, 5 );
		$this->assertNotNull( $this->product_cache->get( 'product' ) );

		wp_cache_set( 'product-count_product_statuses', $corrupted_value );

		$this->assertNull( $this->product_cache->get( 'product' ) );
		$this->assertFalse( wp_cache_get( 'product-count_product_statuses' ) );
	}

	/**
	 * Data provider for corrupted count value scenarios.
	 *
	 * @return array
	 */
	public function provider_corrupted_count_values(): array {
		return array(
			'string value'  => array( 'not-an-integer' ),
			'boolean value' => array( true ),
			'array value'   => array( array( 1 ) ),
		);
	}

	/**
	 * @testdox Corrupted count value is purged and get() falls back to null.
	 * @dataProvider provider_corrupted_count_values
	 *
	 * @param mixed $corrupted_value The corrupted value to inject into a count cache slot.
	 */
	public function test_corrupted_count_value_is_purged( $corrupted_value ): void {
		$this->product_cache->set( 'product', ProductStatus::PUBLISH, 5 );
		$this->product_cache->set( 'product', ProductStatus::DRAFT, 10 );

		wp_cache_set( 'product-count_product_publish', $corrupted_value );

		$result = $this->product_cache->get( 'product', array( ProductStatus::PUBLISH, ProductStatus::DRAFT ) );
		$this->assertNull( $result );

		$this->assertFalse( wp_cache_get( 'product-count_product_publish' ) );
		$this->assertSame( 10, wp_cache_get( 'product-count_product_draft' ) );
	}
}
