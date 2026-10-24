<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Triggers;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\StockNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationProcessor;
use Automattic\WooCommerce\Internal\PushNotifications\Triggers\StockNotificationRecoveryHandler;
use WC_Helper_Product;
use WC_Product;
use WC_Product_Simple;
use WC_Unit_Test_Case;

/**
 * Tests for the StockNotificationRecoveryHandler class.
 */
class StockNotificationRecoveryHandlerTest extends WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var StockNotificationRecoveryHandler
	 */
	private $sut;

	/**
	 * @var string|false
	 */
	private $original_no_stock_amount;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_no_stock_amount = get_option( 'woocommerce_notify_no_stock_amount' );
		update_option( 'woocommerce_notify_no_stock_amount', 0 );

		$this->sut = new StockNotificationRecoveryHandler();
		$this->sut->register();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_action( 'woocommerce_product_set_stock', array( $this->sut, 'on_stock_change' ) );
		remove_action( 'woocommerce_variation_set_stock', array( $this->sut, 'on_stock_change' ) );

		if ( false === $this->original_no_stock_amount ) {
			delete_option( 'woocommerce_notify_no_stock_amount' );
		} else {
			update_option( 'woocommerce_notify_no_stock_amount', $this->original_no_stock_amount );
		}

		parent::tearDown();
	}

	/**
	 * Creates a managed-stock product with the given quantity.
	 *
	 * @param int|null $stock_quantity Stock quantity, or null for unmanaged stock.
	 * @return WC_Product_Simple
	 */
	private function create_product( ?int $stock_quantity ): WC_Product_Simple {
		$props = null === $stock_quantity
			? array( 'manage_stock' => false )
			: array(
				'manage_stock'   => true,
				'stock_quantity' => $stock_quantity,
			);

		return WC_Helper_Product::create_simple_product( true, $props );
	}

	/**
	 * Seeds the namespaced sent-meta on a product, mirroring what
	 * StockNotification::write_meta() does.
	 *
	 * @param WC_Product $product    The product.
	 * @param string     $event_type The event subtype.
	 */
	private function seed_meta( WC_Product $product, string $event_type ): void {
		$product->update_meta_data( $this->meta_key( $event_type ), (string) time() );
		$product->save_meta_data();
	}

	/**
	 * Returns the namespaced sent-meta key for a given event subtype.
	 *
	 * @param string $event_type The event subtype.
	 * @return string
	 */
	private function meta_key( string $event_type ): string {
		return NotificationProcessor::SENT_META_KEY . '_' . $event_type;
	}

	/**
	 * Asserts that the namespaced sent-meta exists for a product.
	 *
	 * @param int    $product_id The product ID.
	 * @param string $event_type The event subtype.
	 */
	private function assert_meta_exists( int $product_id, string $event_type ): void {
		$fresh = wc_get_product( $product_id );
		$this->assertInstanceOf( WC_Product::class, $fresh );
		$this->assertTrue(
			$fresh->meta_exists( $this->meta_key( $event_type ) ),
			"Expected sent-meta for '{$event_type}' to exist."
		);
	}

	/**
	 * Asserts that the namespaced sent-meta has been cleared for a product.
	 *
	 * @param int    $product_id The product ID.
	 * @param string $event_type The event subtype.
	 */
	private function assert_meta_cleared( int $product_id, string $event_type ): void {
		$fresh = wc_get_product( $product_id );
		$this->assertInstanceOf( WC_Product::class, $fresh );
		$this->assertFalse(
			$fresh->meta_exists( $this->meta_key( $event_type ) ),
			"Expected sent-meta for '{$event_type}' to be cleared."
		);
	}

	/**
	 * @testdox Clears low_stock meta when stock recovers above threshold.
	 */
	public function test_clears_low_stock_meta_when_stock_recovers_above_threshold(): void {
		update_option( 'woocommerce_notify_low_stock_amount', 2 );

		$product = $this->create_product( 5 );
		$this->seed_meta( $product, StockNotification::EVENT_LOW_STOCK );

		$this->sut->on_stock_change( $product );

		$this->assert_meta_cleared( $product->get_id(), StockNotification::EVENT_LOW_STOCK );
	}

	/**
	 * @testdox Preserves low_stock meta when stock is at threshold (recovery is strict >).
	 */
	public function test_preserves_low_stock_meta_when_stock_at_threshold(): void {
		update_option( 'woocommerce_notify_low_stock_amount', 2 );

		$product = $this->create_product( 2 );
		$this->seed_meta( $product, StockNotification::EVENT_LOW_STOCK );

		$this->sut->on_stock_change( $product );

		$this->assert_meta_exists( $product->get_id(), StockNotification::EVENT_LOW_STOCK );
	}

	/**
	 * @testdox Honours the per-product low-stock threshold override.
	 */
	public function test_respects_per_product_low_stock_override(): void {
		update_option( 'woocommerce_notify_low_stock_amount', 2 );

		$product = $this->create_product( 5 );
		$product->set_low_stock_amount( 10 );
		$product->save();
		$this->seed_meta( $product, StockNotification::EVENT_LOW_STOCK );

		$this->sut->on_stock_change( $product );

		$this->assert_meta_exists( $product->get_id(), StockNotification::EVENT_LOW_STOCK );
	}

	/**
	 * @testdox Clears out_of_stock meta when stock is above the no-stock-amount option.
	 */
	public function test_clears_out_of_stock_meta_when_stock_above_no_stock_amount(): void {
		update_option( 'woocommerce_notify_no_stock_amount', 0 );

		$product = $this->create_product( 1 );
		$this->seed_meta( $product, StockNotification::EVENT_OUT_OF_STOCK );

		$this->sut->on_stock_change( $product );

		$this->assert_meta_cleared( $product->get_id(), StockNotification::EVENT_OUT_OF_STOCK );
	}

	/**
	 * @testdox Preserves out_of_stock meta when stock equals the no-stock-amount option.
	 */
	public function test_preserves_out_of_stock_meta_at_no_stock_amount(): void {
		update_option( 'woocommerce_notify_no_stock_amount', 0 );

		$product = $this->create_product( 0 );
		$this->seed_meta( $product, StockNotification::EVENT_OUT_OF_STOCK );

		$this->sut->on_stock_change( $product );

		$this->assert_meta_exists( $product->get_id(), StockNotification::EVENT_OUT_OF_STOCK );
	}

	/**
	 * @testdox Clears on_backorder meta when stock is non-negative.
	 */
	public function test_clears_on_backorder_meta_when_stock_non_negative(): void {
		$product = $this->create_product( 0 );
		$this->seed_meta( $product, StockNotification::EVENT_ON_BACKORDER );

		$this->sut->on_stock_change( $product );

		$this->assert_meta_cleared( $product->get_id(), StockNotification::EVENT_ON_BACKORDER );
	}

	/**
	 * @testdox Preserves on_backorder meta when stock is still negative.
	 */
	public function test_preserves_on_backorder_meta_when_stock_negative(): void {
		$product = $this->create_product( -1 );
		$this->seed_meta( $product, StockNotification::EVENT_ON_BACKORDER );

		$this->sut->on_stock_change( $product );

		$this->assert_meta_exists( $product->get_id(), StockNotification::EVENT_ON_BACKORDER );
	}

	/**
	 * @testdox Clears each meta independently based on its own threshold.
	 */
	public function test_independent_meta_clearing(): void {
		update_option( 'woocommerce_notify_low_stock_amount', 5 );
		update_option( 'woocommerce_notify_no_stock_amount', 0 );

		$product = $this->create_product( 2 );
		$this->seed_meta( $product, StockNotification::EVENT_LOW_STOCK );
		$this->seed_meta( $product, StockNotification::EVENT_OUT_OF_STOCK );
		$this->seed_meta( $product, StockNotification::EVENT_ON_BACKORDER );

		$this->sut->on_stock_change( $product );

		$this->assert_meta_exists( $product->get_id(), StockNotification::EVENT_LOW_STOCK );
		$this->assert_meta_cleared( $product->get_id(), StockNotification::EVENT_OUT_OF_STOCK );
		$this->assert_meta_cleared( $product->get_id(), StockNotification::EVENT_ON_BACKORDER );
	}

	/**
	 * @testdox Clears all metas when stock jumps above all thresholds in one step.
	 */
	public function test_clears_all_metas_when_stock_jumps_above_all_thresholds(): void {
		update_option( 'woocommerce_notify_low_stock_amount', 2 );
		update_option( 'woocommerce_notify_no_stock_amount', 0 );

		$product = $this->create_product( 10 );
		$this->seed_meta( $product, StockNotification::EVENT_LOW_STOCK );
		$this->seed_meta( $product, StockNotification::EVENT_OUT_OF_STOCK );
		$this->seed_meta( $product, StockNotification::EVENT_ON_BACKORDER );

		$this->sut->on_stock_change( $product );

		$this->assert_meta_cleared( $product->get_id(), StockNotification::EVENT_LOW_STOCK );
		$this->assert_meta_cleared( $product->get_id(), StockNotification::EVENT_OUT_OF_STOCK );
		$this->assert_meta_cleared( $product->get_id(), StockNotification::EVENT_ON_BACKORDER );
	}

	/**
	 * @testdox Is a no-op when no sent-meta exists.
	 */
	public function test_idempotent_when_no_meta_exists(): void {
		$product = $this->create_product( 10 );

		$this->sut->on_stock_change( $product );

		$this->assert_meta_cleared( $product->get_id(), StockNotification::EVENT_LOW_STOCK );
		$this->assert_meta_cleared( $product->get_id(), StockNotification::EVENT_OUT_OF_STOCK );
		$this->assert_meta_cleared( $product->get_id(), StockNotification::EVENT_ON_BACKORDER );
	}

	/**
	 * @testdox Is a no-op for products with null stock quantity.
	 */
	public function test_no_op_for_null_stock_quantity(): void {
		$product = $this->create_product( null );
		$this->seed_meta( $product, StockNotification::EVENT_LOW_STOCK );
		$this->seed_meta( $product, StockNotification::EVENT_OUT_OF_STOCK );
		$this->seed_meta( $product, StockNotification::EVENT_ON_BACKORDER );

		$this->sut->on_stock_change( $product );

		$this->assert_meta_exists( $product->get_id(), StockNotification::EVENT_LOW_STOCK );
		$this->assert_meta_exists( $product->get_id(), StockNotification::EVENT_OUT_OF_STOCK );
		$this->assert_meta_exists( $product->get_id(), StockNotification::EVENT_ON_BACKORDER );
	}

	/**
	 * @testdox Is a no-op for products with non-positive IDs (defensive).
	 */
	public function test_no_op_for_invalid_product_id(): void {
		$product = $this->getMockBuilder( WC_Product_Simple::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_id', 'get_stock_quantity' ) )
			->getMock();

		$product->method( 'get_id' )->willReturn( 0 );
		$product->expects( $this->never() )->method( 'get_stock_quantity' );

		$this->sut->on_stock_change( $product );
	}

	/**
	 * @testdox Recovery fires through the woocommerce_product_set_stock action.
	 */
	public function test_handler_fires_via_action(): void {
		update_option( 'woocommerce_notify_low_stock_amount', 2 );

		$product = $this->create_product( 0 );
		$this->seed_meta( $product, StockNotification::EVENT_LOW_STOCK );
		$this->seed_meta( $product, StockNotification::EVENT_OUT_OF_STOCK );

		wc_update_product_stock( $product->get_id(), 10, 'set' );

		$this->assert_meta_cleared( $product->get_id(), StockNotification::EVENT_LOW_STOCK );
		$this->assert_meta_cleared( $product->get_id(), StockNotification::EVENT_OUT_OF_STOCK );
	}

	/**
	 * @testdox Recovery fires through the woocommerce_variation_set_stock action when a variation's stock changes.
	 */
	public function test_handler_fires_for_variation_via_action(): void {
		update_option( 'woocommerce_notify_low_stock_amount', 2 );

		$variable_product = WC_Helper_Product::create_variation_product();
		$variation_ids    = $variable_product->get_children();
		$this->assertNotEmpty( $variation_ids );

		$variation = wc_get_product( $variation_ids[0] );
		$this->assertInstanceOf( \WC_Product_Variation::class, $variation );

		$variation->set_manage_stock( true );
		$variation->set_stock_quantity( 0 );
		$variation->save();

		$this->seed_meta( $variation, StockNotification::EVENT_LOW_STOCK );

		// `wc_update_product_stock` routes to `woocommerce_variation_set_stock`
		// for variations (see `wc-stock-functions.php:68-76`).
		wc_update_product_stock( $variation->get_id(), 10, 'set' );

		$this->assert_meta_cleared( $variation->get_id(), StockNotification::EVENT_LOW_STOCK );
	}
}
