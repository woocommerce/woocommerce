<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Tests\Caching;

use WC_Helper_Order;
use Automattic\WooCommerce\Caches\OrderCountCache;
use Automattic\WooCommerce\Enums\OrderInternalStatus;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Class OrderCountCacheTest.
 */
class OrderCountCacheTest extends \WC_Unit_Test_Case {

	/**
	 * OrderCache instance.
	 *
	 * @var OrderCache
	 */
	private $order_cache;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->order_cache = new OrderCountCache();
		$this->order_cache->flush( 'shop_order' );
	}

	/**
	 * Test that a valid status and order type can be cached.
	 */
	public function test_cache_order_counts() {
		$counts = array(
			OrderInternalStatus::PENDING   => 5,
			OrderInternalStatus::COMPLETED => 10,
		);

		foreach ( $counts as $status => $count ) {
			$this->order_cache->set( 'shop_order', $status, $count );
		}

		$this->assertTrue( $this->order_cache->is_cached( 'shop_order', OrderInternalStatus::PENDING ) );
		$this->assertTrue( $this->order_cache->is_cached( 'shop_order', OrderInternalStatus::COMPLETED ) );
		$this->assertEquals( 5, $this->order_cache->get( 'shop_order', array( OrderInternalStatus::PENDING ) )[ OrderInternalStatus::PENDING ] );
		$this->assertEquals( 10, $this->order_cache->get( 'shop_order', array( OrderInternalStatus::COMPLETED ) )[ OrderInternalStatus::COMPLETED ] );
	}

	/**
	 * Test that invalid statuses throw exceptions.
	 */
	public function test_invalid_order_status() {
		$this->expectException( \Exception::class );
		$this->order_cache->set( 'shop_order', 'bad-status', 1 );
	}

	/**
	 * Test that the cache can be flushed.
	 */
	public function test_flush_cache() {
		$this->order_cache->set( 'shop_order', OrderInternalStatus::PENDING, 5 );
		$this->order_cache->set( 'shop_order', OrderInternalStatus::COMPLETED, 10 );
		$this->order_cache->flush( 'shop_order', array( OrderInternalStatus::PENDING, OrderInternalStatus::COMPLETED ) );
		$this->assertFalse( $this->order_cache->is_cached( 'shop_order', OrderInternalStatus::PENDING ) );
		$this->assertFalse( $this->order_cache->is_cached( 'shop_order', OrderInternalStatus::COMPLETED ) );
	}

	/**
	 * Test that the cache gets default statuses when no statuses are provided.
	 */
	public function test_cache_gets_default_statuses_when_no_statuses_are_provided() {
		$default_statuses = array_merge( array_keys( wc_get_order_statuses() ), array( OrderStatus::TRASH ) );
		foreach ( $default_statuses as $status ) {
			$this->order_cache->set( 'shop_order', $status, 5 );
		}

		$this->assertEquals( 5, $this->order_cache->get( 'shop_order' )[ OrderInternalStatus::PENDING ] );
		$this->assertEquals( 5, $this->order_cache->get( 'shop_order' )[ OrderInternalStatus::COMPLETED ] );
		$this->assertEquals( 5, $this->order_cache->get( 'shop_order' )[ OrderInternalStatus::PROCESSING ] );
		$this->assertEquals( 5, $this->order_cache->get( 'shop_order' )[ OrderInternalStatus::ON_HOLD ] );
		$this->assertEquals( 5, $this->order_cache->get( 'shop_order' )[ OrderInternalStatus::CANCELLED ] );
		$this->assertEquals( 5, $this->order_cache->get( 'shop_order' )[ OrderInternalStatus::REFUNDED ] );
		$this->assertEquals( 5, $this->order_cache->get( 'shop_order' )[ OrderInternalStatus::FAILED ] );
	}
}
