<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Inventory;

use Automattic\WooCommerce\Internal\Inventory\LocationStockService;
use WC_Product;

require_once __DIR__ . '/LocationStockTestCase.php';

/**
 * Tests for POS location stock.
 *
 * @covers \Automattic\WooCommerce\Internal\Inventory\LocationStockOrderController
 */
class LocationStockOrderControllerTest extends LocationStockTestCase {

	/**
	 * @testdox Should keep orders without POS inventory location on Core's legacy _stock path.
	 *
	 * @dataProvider order_created_via_provider
	 *
	 * @param string $created_via Order created_via value.
	 */
	public function test_orders_without_pos_inventory_location_stay_on_legacy_stock_path( string $created_via ): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );

		$order = $this->create_order_for_product( $product, 2, $created_via );

		wc_reduce_stock_levels( $order );

		$items = $order->get_items();
		$item  = reset( $items );

		$this->assertEquals( 5, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 13, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$this->assertEquals( 2, $item->get_meta( '_reduced_stock', true ) );
		$this->assertEmpty( $item->get_meta( '_reduced_location_stock', true ) );
	}

	/**
	 * Data provider for legacy stock-routing created_via values without explicit POS inventory location.
	 *
	 * @return array<string,array{string}>
	 */
	public function order_created_via_provider(): array {
		return array(
			'block checkout'       => array( 'store-api' ),
			'shortcode checkout'   => array( 'checkout' ),
			'admin order'          => array( 'admin' ),
			'generic REST'         => array( 'rest-api' ),
			'REST integration'     => array( 'square' ),
			'POS created_via'      => array( 'point-of-sale' ),
			'POS REST created_via' => array( 'pos-rest-api' ),
		);
	}

	/**
	 * @testdox Should route existing orders with explicit POS inventory location meta to POS stock.
	 */
	public function test_existing_order_inventory_location_meta_routes_order_to_pos_stock(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );

		$order = $this->create_order_for_product(
			$product,
			2,
			'rest-api',
			array( '_inventory_location' => LocationStockService::LOCATION_POS )
		);

		$this->order_controller->maybe_reduce_location_stock_levels( $order->get_id() );

		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		$this->assertEquals( 3, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$this->assertEquals( 2, $item->get_meta( '_reduced_location_stock', true ) );
		$this->assertEmpty( $item->get_meta( '_reduced_stock', true ) );
	}


	/**
	 * @testdox Should add an error note when stock is no longer available at reduction time.
	 */
	public function test_pos_order_reduce_failure_adds_error_note_without_negative_stock(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 1 );
		$first_order  = $this->create_pos_order_for_product( $product, 1 );
		$second_order = $this->create_pos_order_for_product( $product, 1 );

		$this->order_controller->maybe_reduce_location_stock_levels( $first_order->get_id() );
		$this->order_controller->maybe_reduce_location_stock_levels( $second_order->get_id() );

		$second_order = wc_get_order( $second_order->get_id() );
		$items        = $second_order->get_items();
		$item         = reset( $items );

		$this->assertEquals( 0, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assert_order_has_error_note( $second_order, 'Not enough stock at POS' );
		$this->assertEmpty( $item->get_meta( '_reduced_location_stock', true ) );
		$this->assertEmpty( $item->get_meta( '_reduced_stock', true ) );
	}

	/**
	 * @testdox Should restore POS order stock to the POS bucket only.
	 */
	public function test_pos_order_restore_returns_stock_to_pos_bucket_only(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );
		$order = $this->create_pos_order_for_product( $product, 2 );

		$this->order_controller->maybe_reduce_location_stock_levels( $order->get_id() );
		$this->order_controller->maybe_restore_location_stock_levels( $order->get_id() );

		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		$this->assertEquals( 5, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$this->assertEmpty( $item->get_meta( '_reduced_location_stock', true ) );
	}

	/**
	 * @testdox Should restock refunded POS items to the POS bucket and block Core restock.
	 */
	public function test_pos_refunded_item_restock_returns_stock_to_pos_bucket_only(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );
		$order = $this->create_pos_order_for_product( $product, 2 );

		$this->order_controller->maybe_reduce_location_stock_levels( $order->get_id() );

		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		wc_restock_refunded_items(
			$order,
			array(
				$item->get_id() => array(
					'qty' => 1,
				),
			)
		);

		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		$this->assertEquals( 4, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$this->assertEquals( 1, $item->get_meta( '_reduced_location_stock', true ) );
		$this->assertEmpty( $item->get_meta( '_reduced_stock', true ) );
	}

	/**
	 * @testdox Should not reduce POS stock again after refunded stock is fully restored.
	 */
	public function test_fully_refunded_pos_order_does_not_reduce_pos_stock_again(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );
		$order = $this->create_pos_order_for_product( $product, 2 );

		$this->order_controller->maybe_reduce_location_stock_levels( $order->get_id() );

		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		wc_restock_refunded_items(
			$order,
			array(
				$item->get_id() => array(
					'qty' => 2,
				),
			)
		);

		$this->order_controller->maybe_reduce_location_stock_levels( $order->get_id() );

		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		$this->assertEquals( 5, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$this->assertEquals( 0, $item->get_meta( '_reduced_location_stock', true ) );
		$this->assertEmpty( $item->get_meta( '_reduced_stock', true ) );
	}

	/**
	 * @testdox Should reduce POS stock when a POS order item quantity increases.
	 */
	public function test_pos_order_item_quantity_increase_adjusts_pos_stock_delta(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );
		$order = $this->create_pos_order_for_product( $product, 2 );

		$this->order_controller->maybe_reduce_location_stock_levels( $order->get_id() );
		$item = $this->set_first_order_item_quantity( $order, 3 );

		require_once WC_ABSPATH . 'includes/admin/wc-admin-functions.php';
		$changed_stock = wc_maybe_adjust_line_item_product_stock( $item );

		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		$this->assertFalse( $changed_stock );
		$this->assertEquals( 2, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$this->assertEquals( 3, $item->get_meta( '_reduced_location_stock', true ) );
		$this->assertEmpty( $item->get_meta( '_reduced_stock', true ) );
	}

	/**
	 * @testdox Should keep the edited quantity and add an error note when a POS order item increase exceeds available stock.
	 */
	public function test_pos_order_item_quantity_increase_failure_keeps_edit_and_notes_error(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 2 );
		$order = $this->create_pos_order_for_product( $product, 2 );
		$order->set_status( 'processing' );
		$order->save();
		$this->set_first_order_item_totals( $order, '20.00', '20.00' );

		$this->order_controller->maybe_reduce_location_stock_levels( $order->get_id() );
		$this->save_first_order_item_with_admin_values( $order, 3, '30.00', '30.00' );

		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		// POS stock is never driven negative and Core stock is left untouched.
		$this->assertEquals( 0, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );

		// Like Core, the edit is not rolled back; the shortfall is surfaced via an order note.
		$this->assertEquals( 3, $item->get_quantity() );
		$this->assertEquals( 30.0, (float) $item->get_total() );
		$this->assertEquals( 2, $item->get_meta( '_reduced_location_stock', true ) );
		$this->assert_order_has_error_note( $order, 'Not enough stock at POS' );
	}

	/**
	 * @testdox Should reduce POS stock and leave Core stock untouched when a pending order transitions to completed.
	 */
	public function test_pos_order_status_transition_to_completed_reduces_location_stock(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );
		$order = $this->create_pos_order_for_product( $product, 2 );

		$this->assertSame( 'pending', $order->get_status() );

		$order->set_status( 'completed' );
		$order->save();

		$order   = wc_get_order( $order->get_id() );
		$product = wc_get_product( $product->get_id() );

		$this->assertEquals( 3, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, $product->get_stock_quantity() );
		$this->assertEquals( 'yes', $order->get_meta( '_location_stock_reduced', true ) );
	}

	/**
	 * @testdox Should update parent-managed variation modified dates when POS orders reduce stock.
	 */
	public function test_pos_order_reduction_updates_parent_managed_variation_modified_date(): void {
		$parent    = $this->create_parent_managed_variation_product();
		$variation = wc_get_product( $parent->get_children()[0] );
		$this->assertInstanceOf( WC_Product::class, $variation );
		$this->service->set_location_stock( $parent, LocationStockService::LOCATION_POS, 5 );
		$order = $this->create_pos_order_for_product( $variation, 2 );

		$parent_modified_before    = $this->set_product_modified_date_to_past( $parent );
		$variation_modified_before = $this->set_product_modified_date_to_past( $variation );

		$this->order_controller->maybe_reduce_location_stock_levels( $order->get_id() );

		$this->assertEquals( 3, $this->service->get_location_stock( $parent, LocationStockService::LOCATION_POS ) );
		$this->assert_product_modified_after( $parent->get_id(), $parent_modified_before );
		$this->assert_product_modified_after( $variation->get_id(), $variation_modified_before );
	}

	/**
	 * @testdox Should restore POS stock when a POS order item quantity decreases.
	 */
	public function test_pos_order_item_quantity_decrease_adjusts_pos_stock_delta(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );
		$order = $this->create_pos_order_for_product( $product, 3 );

		$this->order_controller->maybe_reduce_location_stock_levels( $order->get_id() );
		$item = $this->set_first_order_item_quantity( $order, 1 );

		require_once WC_ABSPATH . 'includes/admin/wc-admin-functions.php';
		$changed_stock = wc_maybe_adjust_line_item_product_stock( $item );

		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		$this->assertFalse( $changed_stock );
		$this->assertEquals( 4, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$this->assertEquals( 1, $item->get_meta( '_reduced_location_stock', true ) );
		$this->assertEmpty( $item->get_meta( '_reduced_stock', true ) );
	}
}
