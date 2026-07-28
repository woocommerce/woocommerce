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
	 * @testdox Bulk cache writes normalize numeric status keys to strings.
	 */
	public function test_set_multiple_normalizes_numeric_status_keys(): void {
		$result = $this->product_cache->set_multiple(
			'product',
			array(
				123                    => 10,
				ProductStatus::PUBLISH => 5,
			)
		);

		$this->assertCount( 2, $result, 'All status keys should be cached.' );
		$this->assertSame(
			array(
				123                    => 10,
				ProductStatus::PUBLISH => 5,
			),
			$this->product_cache->get( 'product' ),
			'Numeric status keys should retain their original value.'
		);
	}

	/**
	 * @testdox Previously cached numeric statuses are normalized when reading all statuses.
	 */
	public function test_get_normalizes_previously_cached_numeric_statuses(): void {
		wp_cache_set( 'product-count_product_statuses', array( 123, ProductStatus::PUBLISH ) );
		$this->product_cache->set( 'product', '123', 10 );
		$this->product_cache->set( 'product', ProductStatus::PUBLISH, 5 );

		$this->assertSame(
			array(
				123                    => 10,
				ProductStatus::PUBLISH => 5,
			),
			$this->product_cache->get( 'product' ),
			'Numeric statuses from an existing cache should not cause a type error.'
		);
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
}
