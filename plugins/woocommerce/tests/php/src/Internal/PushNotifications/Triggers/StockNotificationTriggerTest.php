<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Triggers;

use Automattic\WooCommerce\Internal\PushNotifications\Dispatchers\InternalNotificationDispatcher;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\StockNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Services\PendingNotificationStore;
use Automattic\WooCommerce\Internal\PushNotifications\Triggers\StockNotificationTrigger;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the StockNotificationTrigger class.
 */
class StockNotificationTriggerTest extends WC_Unit_Test_Case {
	/**
	 * An instance of StockNotificationTrigger.
	 *
	 * @var StockNotificationTrigger
	 */
	private $trigger;

	/**
	 * The notification store used by the trigger.
	 *
	 * @var PendingNotificationStore
	 */
	private $store;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$dispatcher  = $this->createMock( InternalNotificationDispatcher::class );
		$this->store = new PendingNotificationStore();

		$this->store->init( $dispatcher );
		$this->store->register();

		wc_get_container()->replace( PendingNotificationStore::class, $this->store );
		wc_get_container()->reset_all_resolved();

		$this->trigger = new StockNotificationTrigger();
		$this->trigger->register();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_action( 'woocommerce_low_stock', array( $this->trigger, 'on_low_stock' ) );
		remove_action( 'woocommerce_no_stock', array( $this->trigger, 'on_no_stock' ) );
		remove_action( 'woocommerce_product_on_backorder', array( $this->trigger, 'on_backorder' ) );
		remove_action( 'shutdown', array( $this->store, 'dispatch_all' ) );

		$this->reset_container_replacements();
		wc_get_container()->reset_all_resolved();

		parent::tearDown();
	}

	/**
	 * @testdox Should add a notification when the low stock hook fires and capture the stock quantity at trigger time.
	 */
	public function test_low_stock_hook_adds_notification(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 2,
			)
		);

		$this->trigger->on_low_stock( $product );

		$this->assertSame( 1, $this->store->count() );

		$notifications = $this->store->get_all();
		$this->assertInstanceOf( StockNotification::class, $notifications[0] );
		$this->assertSame( StockNotification::EVENT_LOW_STOCK, $notifications[0]->get_event_type() );

		// The stock snapshot is captured at trigger time so the dispatcher (which runs in a separate process)
		// doesn't read a stale value if cache invalidation hasn't propagated.
		$this->assertSame( 2, $notifications[0]->to_array()['stock_quantity_at_trigger'] );
	}

	/**
	 * @testdox Should add a notification when the no stock hook fires.
	 */
	public function test_no_stock_hook_adds_notification(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 0,
			)
		);

		$this->trigger->on_no_stock( $product );

		$this->assertSame( 1, $this->store->count() );

		$notifications = $this->store->get_all();
		$this->assertSame( StockNotification::EVENT_OUT_OF_STOCK, $notifications[0]->get_event_type() );
	}

	/**
	 * @testdox Should add a notification when the backorder hook fires with a valid product.
	 */
	public function test_backorder_hook_adds_notification(): void {
		$product = WC_Helper_Product::create_simple_product();

		$this->trigger->on_backorder(
			array(
				'product'  => $product,
				'order_id' => 1,
				'quantity' => 3,
			)
		);

		$this->assertSame( 1, $this->store->count() );

		$notifications = $this->store->get_all();
		$this->assertSame( StockNotification::EVENT_ON_BACKORDER, $notifications[0]->get_event_type() );
	}

	/**
	 * @testdox Should ignore the backorder hook when the product is not a WC_Product.
	 */
	public function test_backorder_hook_ignores_invalid_product(): void {
		$this->trigger->on_backorder(
			array(
				'product'  => 'not-a-product',
				'order_id' => 1,
				'quantity' => 3,
			)
		);

		$this->assertSame( 0, $this->store->count() );
	}

	/**
	 * @testdox Should ignore the backorder hook when the product key is missing.
	 */
	public function test_backorder_hook_ignores_missing_product(): void {
		$this->trigger->on_backorder( array( 'order_id' => 1 ) );

		$this->assertSame( 0, $this->store->count() );
	}

	/**
	 * @testdox Should store different event types for the same product separately.
	 */
	public function test_different_events_same_product_stored_separately(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 0,
			)
		);

		$this->trigger->on_low_stock( $product );
		$this->trigger->on_no_stock( $product );

		$this->assertSame( 2, $this->store->count() );
	}

	/**
	 * @testdox Should deduplicate the same event type for the same product.
	 */
	public function test_same_event_same_product_deduplicated(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 2,
			)
		);

		$this->trigger->on_low_stock( $product );
		$this->trigger->on_low_stock( $product );

		$this->assertSame( 1, $this->store->count() );
	}
}
