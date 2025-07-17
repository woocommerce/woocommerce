<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Internal\DataStores\Fulfillments\FulfillmentsDataStore;
use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsManager;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Tests\Internal\Fulfillments\Helpers\FulfillmentsHelper;
use WC_Order;

/**
 * Tests for Fulfillment refund handling.
 */
class FulfillmentsRefundTest extends \WC_Unit_Test_Case {

	/**
	 * FulfillmentsDataStore instance.
	 *
	 * @var FulfillmentsDataStore
	 */
	private $data_store;

	/**
	 * FulfillmentsManager instance.
	 *
	 * @var FulfillmentsManager
	 */
	private FulfillmentsManager $manager;

	/**
	 * Set up the test environment.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$container               = wc_get_container();
		$fulfillments_controller = $container->get( \Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsController::class );
		$fulfillments_controller->register();
	}

	/**
	 * Tear down the test environment.
	 */
	public static function tearDownAfterClass(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		parent::tearDownAfterClass();
	}

	/**
	 * Set up each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->data_store = wc_get_container()->get( FulfillmentsDataStore::class );
		$this->manager    = new FulfillmentsManager();
	}

	/**
	 * Helper method to create an order with products.
	 *
	 * @param int $product_count Number of products to add.
	 * @param int $quantity      Quantity per product.
	 * @return WC_Order
	 */
	private function create_test_order( int $product_count = 2, int $quantity = 5 ): WC_Order {
		$order = OrderHelper::create_order();

		// Remove existing items to start fresh.
		foreach ( $order->get_items() as $item ) {
			$order->remove_item( $item->get_id() );
		}

		// Add specific test products.
		for ( $i = 0; $i < $product_count; $i++ ) {
			$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
			$product->set_regular_price( 10 );
			$product->save();

			$item = new \WC_Order_Item_Product();
			$item->set_props(
				array(
					'product'  => $product,
					'quantity' => $quantity,
					'subtotal' => $quantity * 10,
					'total'    => $quantity * 10,
				)
			);
			$order->add_item( $item );
		}

		$order->calculate_totals();
		$order->save();

		return $order;
	}

	/**
	 * Helper method to create a fulfillment with specific items.
	 *
	 * @param WC_Order $order Order to create fulfillment for.
	 * @param array    $items Items to include in fulfillment.
	 * @param bool     $is_fulfilled Whether the fulfillment is completed.
	 * @return Fulfillment
	 */
	private function create_test_fulfillment( WC_Order $order, array $items, bool $is_fulfilled = false ): Fulfillment {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type'  => WC_Order::class,
				'entity_id'    => $order->get_id(),
				'status'       => $is_fulfilled ? 'fulfilled' : 'unfulfilled',
				'is_fulfilled' => $is_fulfilled,
			)
		);

		$fulfillment->set_items( $items );
		$fulfillment->save();

		return $fulfillment;
	}

	/**
	 * Helper method to create a refund.
	 *
	 * @param WC_Order $order Order to refund.
	 * @param array    $refund_items Items to refund with quantities.
	 * @param float    $amount Refund amount.
	 * @param string   $reason Refund reason.
	 * @return \WC_Order_Refund
	 */
	private function create_test_refund( WC_Order $order, array $refund_items, float $amount = 10.0, string $reason = 'Test refund' ): \WC_Order_Refund {
		$refund = wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => $amount,
				'reason'     => $reason,
				'line_items' => $refund_items,
			)
		);

		if ( is_wp_error( $refund ) ) {
			return $refund;
		}

		$refund->save();
		return $refund;
	}

	/**
	 * Test that fulfilled fulfillments are not affected by refunds.
	 */
	public function test_fulfilled_fulfillments_not_affected_by_refunds() {
		// Create order with 1 product, 10 quantity.
		$order   = $this->create_test_order( 1, 10 );
		$items   = $order->get_items();
		$item_id = array_key_first( $items );

		// Create fulfilled fulfillment with 3 items.
		$this->create_test_fulfillment(
			$order,
			array(
				array(
					'item_id' => $item_id,
					'qty'     => 3,
				),
			),
			true
		);

		// Refund 5 items.
		$refund_items = array(
			$item_id => array(
				'qty'          => 5,
				'refund_total' => 50,
			),
		);
		$this->create_test_refund( $order, $refund_items, 50.0 );

		// Check fulfillments after refund.
		$all_fulfillments = $this->data_store->read_fulfillments( WC_Order::class, (string) $order->get_id() );

		// Should still have 1 fulfillment with 3 items (unchanged).
		$this->assertCount( 1, $all_fulfillments );
		$remaining_fulfillment = $all_fulfillments[0];
		$remaining_items       = $remaining_fulfillment->get_items();
		$this->assertEquals( 3, $remaining_items[0]['qty'] );
		$this->assertTrue( $remaining_fulfillment->get_is_fulfilled() );
	}

	/**
	 * Test basic unfulfilled fulfillment reduction.
	 */
	public function test_unfulfilled_fulfillments_partial_refund_reduces_items_correctly() {
		// Create order with 1 product, 10 quantity.
		$order   = $this->create_test_order( 1, 10 );
		$items   = $order->get_items();
		$item_id = array_key_first( $items );

		$hook_called = 0;
		add_action(
			'woocommerce_refund_created',
			function () use ( &$hook_called ) {
				$hook_called++;
			},
			10,
			0
		);

		// Create unfulfilled fulfillment with 7 items (3 pending remain).
		$this->create_test_fulfillment(
			$order,
			array(
				array(
					'item_id' => $item_id,
					'qty'     => 7,
				),
			),
			false
		);

		// Refund 5 items - should reduce unfulfilled to 5 items.
		$refund_items = array(
			$item_id => array(
				'qty'          => 5,
				'refund_total' => 50,
			),
		);
		$this->create_test_refund( $order, $refund_items, 50.0 );

		$this->assertEquals( 1, $hook_called, 'Refund hook should be called once.' );

		// Check fulfillments after refund.
		$all_fulfillments = $this->data_store->read_fulfillments( WC_Order::class, (string) $order->get_id() );

		// Should have 1 fulfillment with 5 items.
		$this->assertCount( 1, $all_fulfillments );
		$remaining_fulfillment = $all_fulfillments[0];
		$remaining_items       = $remaining_fulfillment->get_items();
		$this->assertEquals( 5, $remaining_items[0]['qty'] );
	}

	/**
	 * Test refunds with only pending items (no fulfillments).
	 */
	public function test_refund_with_only_pending_items() {
		// Create order with 1 product, 10 quantity.
		$order   = $this->create_test_order( 1, 10 );
		$items   = $order->get_items();
		$item_id = array_key_first( $items );

		// No fulfillments created - all items are pending.

		// Refund 3 items.
		$refund_items = array(
			$item_id => array(
				'qty'          => 3,
				'refund_total' => 30,
			),
		);
		$this->create_test_refund( $order, $refund_items, 30.0 );

		// Check that no fulfillments exist (all were pending).
		$all_fulfillments = $this->data_store->read_fulfillments( WC_Order::class, (string) $order->get_id() );
		$this->assertCount( 0, $all_fulfillments );

		// Verify the order item quantity is correctly updated after refund.
		$order->get_data_store()->read( $order );
		$updated_items = $order->get_items();
		$updated_item  = $updated_items[ $item_id ];
		$this->assertEquals( 10, $updated_item->get_quantity() ); // Original quantity unchanged.
	}

	/**
	 * Test refunds with only fulfilled fulfillments.
	 */
	public function test_refund_with_only_fulfilled_fulfillments() {
		// Create order with 1 product, 10 quantity.
		$order   = $this->create_test_order( 1, 10 );
		$items   = $order->get_items();
		$item_id = array_key_first( $items );

		// Create fulfilled fulfillment with all 10 items.
		$this->create_test_fulfillment(
			$order,
			array(
				array(
					'item_id' => $item_id,
					'qty'     => 10,
				),
			),
			true
		);

		// Refund 3 items - should not affect fulfilled fulfillment.
		$refund_items = array(
			$item_id => array(
				'qty'          => 3,
				'refund_total' => 30,
			),
		);
		$this->create_test_refund( $order, $refund_items, 30.0 );

		// Check fulfillments after refund.
		$all_fulfillments = $this->data_store->read_fulfillments( WC_Order::class, (string) $order->get_id() );

		// Should still have 1 fulfillment with 10 items (unchanged).
		$this->assertCount( 1, $all_fulfillments );
		$remaining_fulfillment = $all_fulfillments[0];
		$remaining_items       = $remaining_fulfillment->get_items();
		$this->assertEquals( 10, $remaining_items[0]['qty'] );
		$this->assertTrue( $remaining_fulfillment->get_is_fulfilled() );
	}

	/**
	 * Test refunds with only unfulfilled fulfillments.
	 */
	public function test_refund_with_only_unfulfilled_fulfillments() {
		// Create order with 1 product, 10 quantity.
		$order   = $this->create_test_order( 1, 10 );
		$items   = $order->get_items();
		$item_id = array_key_first( $items );

		// Create unfulfilled fulfillment with all 10 items.
		$this->create_test_fulfillment(
			$order,
			array(
				array(
					'item_id' => $item_id,
					'qty'     => 10,
				),
			),
			false
		);

		// Refund 3 items - should reduce unfulfilled fulfillment to 7 items.
		$refund_items = array(
			$item_id => array(
				'qty'          => 3,
				'refund_total' => 30,
			),
		);
		$this->create_test_refund( $order, $refund_items, 30.0 );

		// Check fulfillments after refund.
		$all_fulfillments = $this->data_store->read_fulfillments( WC_Order::class, (string) $order->get_id() );

		// Should have 1 fulfillment with 7 items.
		$this->assertCount( 1, $all_fulfillments );
		$remaining_fulfillment = $all_fulfillments[0];
		$remaining_items       = $remaining_fulfillment->get_items();
		$this->assertEquals( 7, $remaining_items[0]['qty'] );
		$this->assertFalse( $remaining_fulfillment->get_is_fulfilled() );
	}

	/**
	 * Test refund that completely removes an unfulfilled fulfillment.
	 */
	public function test_refund_completely_removes_unfulfilled_fulfillment() {
		// Create order with 1 product, 10 quantity.
		$order   = $this->create_test_order( 1, 10 );
		$items   = $order->get_items();
		$item_id = array_key_first( $items );

		// Create unfulfilled fulfillment with 5 items.
		$this->create_test_fulfillment(
			$order,
			array(
				array(
					'item_id' => $item_id,
					'qty'     => 5,
				),
			),
			false
		);

		// Create another unfulfilled fulfillment with 3 items (2 pending remain).
		$this->create_test_fulfillment(
			$order,
			array(
				array(
					'item_id' => $item_id,
					'qty'     => 3,
				),
			),
			true
		);

		// Refund 8 items - should remove the entire unfulfilled fulfillment and the pending items.
		$refund_items = array(
			$item_id => array(
				'qty'          => 8,
				'refund_total' => 80,
			),
		);
		$this->create_test_refund( $order, $refund_items, 80.0 );

		// Check fulfillments after refund.
		$all_fulfillments = $this->data_store->read_fulfillments( WC_Order::class, (string) $order->get_id() );

		// Should have no fulfillments left.
		$this->assertCount( 1, $all_fulfillments );
		$remaining_fulfillment = $all_fulfillments[0];
		$remaining_items       = $remaining_fulfillment->get_items();
		$this->assertEquals( 3, $remaining_items[0]['qty'] ); // Only the fulfilled fulfillment remains with 3 items.
	}

	/**
	 * Test mixed fulfillments - fulfilled and unfulfilled.
	 */
	public function test_mixed_fulfillments_refund_priority() {
		// Create order with 1 product, 20 quantity.
		$order   = $this->create_test_order( 1, 20 );
		$items   = $order->get_items();
		$item_id = array_key_first( $items );

		// Create fulfilled fulfillment with 8 items.
		$this->create_test_fulfillment(
			$order,
			array(
				array(
					'item_id' => $item_id,
					'qty'     => 8,
				),
			),
			true
		);

		// Create unfulfilled fulfillment with 7 items (5 pending remain).
		$this->create_test_fulfillment(
			$order,
			array(
				array(
					'item_id' => $item_id,
					'qty'     => 7,
				),
			),
			false
		);

		// Refund 10 items - should protect fulfilled (8), reduce unfulfilled to 2.
		$refund_items = array(
			$item_id => array(
				'qty'          => 10,
				'refund_total' => 100,
			),
		);
		$this->create_test_refund( $order, $refund_items, 100.0 );

		// Check fulfillments after refund.
		$all_fulfillments = $this->data_store->read_fulfillments( WC_Order::class, (string) $order->get_id() );

		// Should have 2 fulfillments: 1 fulfilled (8 items), 1 unfulfilled (2 items).
		$this->assertCount( 2, $all_fulfillments );

		foreach ( $all_fulfillments as $fulfillment ) {
			$items = $fulfillment->get_items();
			if ( $fulfillment->get_is_fulfilled() ) {
				$this->assertEquals( 8, $items[0]['qty'] ); // Fulfilled unchanged.
			} else {
				$this->assertEquals( 2, $items[0]['qty'] ); // Unfulfilled reduced from 7 to 2.
			}
		}
	}

	/**
	 * Test multiple unfulfilled fulfillments with refund.
	 */
	public function test_multiple_unfulfilled_fulfillments_refund() {
		// Create order with 1 product, 20 quantity.
		$order   = $this->create_test_order( 1, 20 );
		$items   = $order->get_items();
		$item_id = array_key_first( $items );

		// Create first unfulfilled fulfillment with 8 items.
		$this->create_test_fulfillment(
			$order,
			array(
				array(
					'item_id' => $item_id,
					'qty'     => 8,
				),
			),
			false
		);

		// Create second unfulfilled fulfillment with 7 items (5 pending remain).
		$this->create_test_fulfillment(
			$order,
			array(
				array(
					'item_id' => $item_id,
					'qty'     => 7,
				),
			),
			false
		);

		// Refund 10 items - should reduce unfulfilled fulfillments proportionally.
		$refund_items = array(
			$item_id => array(
				'qty'          => 10,
				'refund_total' => 100,
			),
		);
		$this->create_test_refund( $order, $refund_items, 100.0 ); // 10 items refunded from 5 pending and 5 first fulfillment.

		// Check fulfillments after refund.
		$all_fulfillments = $this->data_store->read_fulfillments( WC_Order::class, (string) $order->get_id() );

		// Should have 2 fulfillments left: 1 with 3 items, 1 with 7 items.
		$this->assertCount( 2, $all_fulfillments );
		$this->assertEquals( 3, $all_fulfillments[0]->get_items()[0]['qty'], 'First fulfillment should have 3 items after refund.' );
		$this->assertEquals( 7, $all_fulfillments[1]->get_items()[0]['qty'], 'Second fulfillment should have 7 items after refund.' );
	}

	/**
	 * Test multiple unfulfilled fulfillments with refund.
	 */
	public function test_multiple_unfulfilled_fulfillments_refund_removes_one() {
		// Create order with 1 product, 20 quantity.
		$order   = $this->create_test_order( 1, 20 );
		$items   = $order->get_items();
		$item_id = array_key_first( $items );

		// Create first unfulfilled fulfillment with 8 items.
		$this->create_test_fulfillment(
			$order,
			array(
				array(
					'item_id' => $item_id,
					'qty'     => 8,
				),
			),
			false
		);

		// Create second unfulfilled fulfillment with 7 items (5 pending remain).
		$this->create_test_fulfillment(
			$order,
			array(
				array(
					'item_id' => $item_id,
					'qty'     => 7,
				),
			),
			false
		);

		// Refund 13 items - should reduce unfulfilled fulfillments proportionally.
		$refund_items = array(
			$item_id => array(
				'qty'          => 13,
				'refund_total' => 130,
			),
		);
		$this->create_test_refund( $order, $refund_items, 130.0 );

		// Check fulfillments after refund.
		$all_fulfillments = $this->data_store->read_fulfillments( WC_Order::class, (string) $order->get_id() );
		$this->assertEquals( 1, count( $all_fulfillments ), 'Should have 1 fulfillment left after refund.' );
		$remaining_fulfillment = $all_fulfillments[0];
		$remaining_items       = $remaining_fulfillment->get_items();
		$this->assertEquals( 7, $remaining_items[0]['qty'], 'Remaining fulfillment should have 7 items after refund.' );
	}

	/**
	 * Test refund with multiple products.
	 */
	public function test_refund_with_multiple_products() {
		// Create order with 2 products, 10 quantity each.
		$order = $this->create_test_order( 2, 10 );
		$items = $order->get_items();

		$item_ids  = array_keys( $items );
		$item_id_1 = $item_ids[0];
		$item_id_2 = $item_ids[1];

		$hook_called = 0;
		add_action(
			'woocommerce_refund_created',
			function () use ( &$hook_called ) {
				$hook_called++;
			},
			10,
			0
		);

		// Create unfulfilled fulfillment with both products. (Pending 1:4, Pending 2:2).
		$this->create_test_fulfillment(
			$order,
			array(
				array(
					'item_id' => $item_id_1,
					'qty'     => 6,
				),
				array(
					'item_id' => $item_id_2,
					'qty'     => 8,
				),
			),
			false
		);

		// Refund 3 of product 1 and 5 of product 2.
		$refund_items = array(
			$item_id_1 => array(
				'qty'          => 3,
				'refund_total' => 30,
			),
			$item_id_2 => array(
				'qty'          => 5,
				'refund_total' => 50,
			),
		);
		$this->create_test_refund( $order, $refund_items, 80.0 );

		$this->assertEquals( 1, $hook_called, 'Refund hook should be called once.' );

		// Check fulfillments after refund.
		$all_fulfillments = $this->data_store->read_fulfillments( WC_Order::class, (string) $order->get_id() );

		// Should have 1 fulfillment with reduced quantities.
		$this->assertCount( 1, $all_fulfillments );
		$remaining_fulfillment = $all_fulfillments[0];
		$remaining_items       = $remaining_fulfillment->get_items();

		// Check that both products have correct remaining quantities.
		$quantities_by_item = array();
		foreach ( $remaining_items as $item ) {
			$quantities_by_item[ $item['item_id'] ] = $item['qty'];
		}

		$this->assertEquals( 6, $quantities_by_item[ $item_id_1 ] ); // 10 - 6 = 4 pending, 3 reduced from pending, fulfillment stays the same.
		$this->assertEquals( 5, $quantities_by_item[ $item_id_2 ] ); // 10 - 8 = 2 pending, 2 reduced from pending, 3 reduced from fulfillment, 5.
	}


	/**
	 * Test fulfillment modifications with multiple refunds.
	 */
	public function test_fulfillment_modifications_with_multiple_refunds() {
		// Create order with 1 product, 20 quantity.
		$order       = $this->create_test_order( 1, 20 );
		$items       = $order->get_items();
		$item_id     = array_key_first( $items );
		$hook_called = 0;
		add_action(
			'woocommerce_refund_created',
			function () use ( &$hook_called ) {
				$hook_called++;
			},
			10,
			0
		);
		// Create unfulfilled fulfillment with 15 items.
		$this->create_test_fulfillment(
			$order,
			array(
				array(
					'item_id' => $item_id,
					'qty'     => 15,
				),
			),
			false
		);
		// Refund 7 items - should reduce unfulfilled fulfillment to 13 items (5 was removed from pending items).
		$refund_items = array(
			$item_id => array(
				'qty'          => 7,
				'refund_total' => 70,
			),
		);
		$this->create_test_refund( $order, $refund_items, 70.0 );

		$this->assertEquals( 1, $hook_called, 'Refund hook should be called once.' );

		// Check fulfillments after refund.
		$all_fulfillments = $this->data_store->read_fulfillments( WC_Order::class, (string) $order->get_id() );
		// Should have 1 fulfillment with 13 items.
		$this->assertCount( 1, $all_fulfillments );
		$remaining_fulfillment = $all_fulfillments[0];
		$remaining_items       = $remaining_fulfillment->get_items();
		$this->assertEquals( 13, $remaining_items[0]['qty'] );
		$this->assertFalse( $remaining_fulfillment->get_is_fulfilled() );

		// Now refund 3 more items - should reduce unfulfilled fulfillment to 10 items.
		$refund_items = array(
			$item_id => array(
				'qty'          => 3,
				'refund_total' => 30,
			),
		);
		$this->create_test_refund( $order, $refund_items, 30.0 );
		$this->assertEquals( 2, $hook_called, 'Refund hook should be called again.' );
		// Check fulfillments after second refund.
		$all_fulfillments = $this->data_store->read_fulfillments( WC_Order::class, (string) $order->get_id() );
		// Should have 1 fulfillment with 10 items.
		$this->assertCount( 1, $all_fulfillments );
		$remaining_fulfillment = $all_fulfillments[0];
		$remaining_items       = $remaining_fulfillment->get_items();
		$this->assertEquals( 10, $remaining_items[0]['qty'] );
		$this->assertFalse( $remaining_fulfillment->get_is_fulfilled() );
	}
}
