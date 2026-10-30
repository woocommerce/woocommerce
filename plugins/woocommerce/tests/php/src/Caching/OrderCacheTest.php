<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Tests\Caching;

use Automattic\WooCommerce\Caches\OrderCache;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Helper_Order;

/**
 * Class OrderCacheTest.
 */
class OrderCacheTest extends \WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var OrderCache
	 */
	private $sut;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new OrderCache();
	}

	/**
	 * Test that the order cache does not cause duplicate data storage.
	 */
	public function test_meta_is_not_duplicated_when_cached() {
		global $wpdb;
		if ( ! OrderUtil::orders_cache_usage_is_enabled() ) {
			// tip: add HPOS=1 env variable to run this test.
			$this->markTestSkipped( 'HPOS based caching is not enabled.' );
		}
		$order = WC_Helper_Order::create_order();
		$order->add_meta_data( 'test', 'test' );
		$order->save_meta_data();

		$order = wc_get_order( $order->get_id() );
		$this->assertTrue( $this->sut->is_cached( $order->get_id() ), 'Order was not cached, but it was expected to be cached. Are you sure that HPOS based caching is enabled.' );

		$order2 = wc_get_order( $order->get_id() );
		$order2->save_meta_data();

		$orders_meta_table = OrdersTableDataStore::get_meta_table_name();
		$query             = $wpdb->prepare( "SELECT id FROM $orders_meta_table WHERE order_id = %d AND meta_key = %s", $order->get_id(), 'test' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertEquals( 1, count( $wpdb->get_col( $query ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Already prepared query.
	}

	/**
	 * @testdox Should keep an unsaved meta change on one order instance away from another instance from the cache.
	 */
	public function test_unsaved_meta_change_does_not_reach_other_instances(): void {
		if ( ! OrderUtil::orders_cache_usage_is_enabled() ) {
			$this->markTestSkipped( 'HPOS based caching is not enabled.' );
		}
		$order = WC_Helper_Order::create_order();
		$order->add_meta_data( 'test', 'saved' );
		$order->save_meta_data();

		$first = wc_get_order( $order->get_id() );
		$first->update_meta_data( 'test', 'unsaved' );
		$second = wc_get_order( $order->get_id() );

		$this->assertSame( 'saved', $second->get_meta( 'test' ) );
		$this->assertSame( 'unsaved', $first->get_meta( 'test' ) );
	}

	/**
	 * @testdox Should keep an unsaved change to an item or its meta on one order copy away from another copy.
	 */
	public function test_unsaved_item_change_does_not_reach_cloned_order(): void {
		$order = WC_Helper_Order::create_order();
		$item  = current( $order->get_items() );
		$item->add_meta_data( 'test', 'saved' );
		$item->save();

		$first = wc_get_order( $order->get_id() );
		$first->get_items();
		$second      = clone $first;
		$first_item  = current( $first->get_items() );
		$second_item = current( $second->get_items() );
		$first_item->set_quantity( 99 );
		$first_item->update_meta_data( 'test', 'unsaved' );

		$this->assertNotSame( $first_item, $second_item );
		$this->assertSame( $first_item->get_id(), $second_item->get_id() );
		$this->assertSame( 'saved', $second_item->get_meta( 'test' ) );
		$this->assertNotEmpty( current( $second_item->get_meta_data() )->id );
		$this->assertNotSame( 99, $second_item->get_quantity() );
		$this->assertSame( \WC_Data::CLONE_MODE_DUPLICATE, $second_item->get_clone_mode() );
		$this->assertSame( \WC_Data::CLONE_MODE_DUPLICATE, $first_item->get_clone_mode() );
		$this->assertSame( $second, $second_item->get_order() );
		$this->assertSame( $first, $first_item->get_order() );
		$this->assertEmpty( $second_item->get_changes() );
	}

	/**
	 * @testdox Should restore the clone mode of the source item when cloning the item throws.
	 */
	public function test_item_clone_mode_is_restored_when_item_clone_throws(): void {
		$item = new class() extends \WC_Order_Item_Product {
			/**
			 * Fail like an extension's item could.
			 *
			 * @throws \RuntimeException Always.
			 */
			public function __clone() {
				throw new \RuntimeException( 'Item clone failed.' );
			}
		};

		$order = new \WC_Order();
		$order->add_item( $item );

		try {
			clone $order;
			$this->fail( 'Expected the item clone to throw.' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'Item clone failed.', $e->getMessage() );
		}

		$this->assertSame( \WC_Data::CLONE_MODE_DUPLICATE, $item->get_clone_mode() );
	}

	/**
	 * @testdox Should keep a change to a saved or pending date on an order clone away from the original.
	 */
	public function test_date_change_does_not_reach_cloned_order(): void {
		$order = WC_Helper_Order::create_order();
		$order->set_date_paid( '2026-01-15 10:00:00' );
		$this->assertArrayHasKey( 'date_paid', $order->get_changes() );
		$created = $order->get_date_created()->getTimestamp();
		$paid    = $order->get_date_paid()->getTimestamp();

		$copy = clone $order;
		$copy->get_date_created()->modify( '+1 day' );
		$copy->get_date_paid()->modify( '+1 day' );

		$this->assertSame( $created, $order->get_date_created()->getTimestamp() );
		$this->assertSame( $paid, $order->get_date_paid()->getTimestamp() );
		$this->assertGreaterThan( $created, $copy->get_date_created()->getTimestamp() );
		$this->assertGreaterThan( $paid, $copy->get_date_paid()->getTimestamp() );
	}

	/**
	 * @testdox Should save only the clone's item changes, once, on the same item and meta rows.
	 */
	public function test_saved_cloned_item_keeps_its_rows_and_changes(): void {
		global $wpdb;
		if ( ! OrderUtil::orders_cache_usage_is_enabled() ) {
			$this->markTestSkipped( 'HPOS based caching is not enabled.' );
		}
		$order = WC_Helper_Order::create_order();
		$item  = current( $order->get_items() );
		$item->add_meta_data( 'test', 'saved' );
		$item->save();
		$meta_id = current( $item->get_meta_data() )->id;

		$source = wc_get_order( $order->get_id() );
		$source->get_items();
		$clone       = clone $source;
		$source_item = current( $source->get_items() );
		$clone_item  = current( $clone->get_items() );
		$source_item->set_quantity( 99 );
		$source_item->update_meta_data( 'test', 'source' );
		$clone_item->set_quantity( 7 );
		$clone_item->update_meta_data( 'test', 'clone' );

		$clone->save();
		$clone->save();

		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT meta_id, meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = %d AND meta_key = 'test'", $item->get_id() ) );
		$this->assertCount( 1, $rows );
		$this->assertSame( (string) $meta_id, $rows[0]->meta_id );
		$this->assertSame( 'clone', $rows[0]->meta_value );

		$reloaded = new \WC_Order_Item_Product( $item->get_id() );
		$this->assertSame( 7, $reloaded->get_quantity() );
		$this->assertSame( 'clone', $reloaded->get_meta( 'test' ) );
		$this->assertSame( $meta_id, current( $reloaded->get_meta_data() )->id );

		$this->assertSame( $item->get_id(), $clone_item->get_id() );
		$this->assertSame( 99, $source_item->get_quantity() );
		$this->assertSame( 'source', $source_item->get_meta( 'test' ) );
	}
}
