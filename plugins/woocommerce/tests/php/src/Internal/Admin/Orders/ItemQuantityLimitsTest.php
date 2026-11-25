<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Orders;

use Automattic\WooCommerce\Internal\Admin\Orders\ItemQuantityLimits;
use WC_Helper_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the ItemQuantityLimits class.
 */
class ItemQuantityLimitsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ItemQuantityLimits
	 */
	private $sut;

	/**
	 * Set up the system under test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( ItemQuantityLimits::class );
	}

	/**
	 * @testdox get_quantity_input_min returns 0 for an item with a positive quantity.
	 */
	public function test_min_is_zero_for_positive_quantity_item(): void {
		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );

		$this->assertSame( '0', $this->sut->get_quantity_input_min( $items[0] ) );
	}

	/**
	 * @testdox get_quantity_input_min floors at the stored quantity when it is negative, so existing negative orders stay editable.
	 */
	public function test_min_floors_at_stored_negative_quantity(): void {
		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );
		$item  = $items[0];
		$item->set_quantity( -5 );
		$item->save();

		$this->assertSame( '-5', $this->sut->get_quantity_input_min( $item ) );
	}

	/**
	 * @testdox get_quantity_input_min applies the woocommerce_quantity_input_min_admin filter.
	 */
	public function test_min_is_filterable(): void {
		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );

		$callback = function () {
			return '-9999';
		};
		add_filter( 'woocommerce_quantity_input_min_admin', $callback );
		$min = $this->sut->get_quantity_input_min( $items[0] );
		remove_filter( 'woocommerce_quantity_input_min_admin', $callback );

		$this->assertSame( '-9999', $min );
	}

	/**
	 * @testdox validate_posted_item_quantities throws when a posted quantity is below the minimum.
	 */
	public function test_validate_posted_throws_below_min(): void {
		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );
		$item  = $items[0];

		$this->expectException( \Exception::class );
		$this->sut->validate_posted_item_quantities(
			$order,
			array(
				'order_item_qty' => array( $item->get_id() => '-1' ),
			)
		);
	}

	/**
	 * @testdox validate_posted_item_quantities accepts a negative quantity when the stored quantity is already that negative.
	 */
	public function test_validate_posted_accepts_existing_negative_quantity(): void {
		$this->expectNotToPerformAssertions();

		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );
		$item  = $items[0];
		$item->set_quantity( -5 );
		$item->save();

		$this->sut->validate_posted_item_quantities(
			$order,
			array(
				'order_item_qty' => array( $item->get_id() => '-5' ),
			)
		);
	}

	/**
	 * @testdox validate_posted_item_quantities ignores item ids that do not belong to the order.
	 */
	public function test_validate_posted_ignores_foreign_item_ids(): void {
		$this->expectNotToPerformAssertions();

		$order        = WC_Helper_Order::create_order();
		$other_order  = WC_Helper_Order::create_order();
		$foreign_item = array_values( $other_order->get_items() )[0];

		$this->sut->validate_posted_item_quantities(
			$order,
			array(
				'order_item_qty' => array( $foreign_item->get_id() => '-1' ),
			)
		);
	}

	/**
	 * @testdox validate_posted_item_quantities ignores non-product items such as fees.
	 */
	public function test_validate_posted_ignores_non_product_items(): void {
		$this->expectNotToPerformAssertions();

		$order = WC_Helper_Order::create_order();
		$fee   = new \WC_Order_Item_Fee();
		$fee->set_name( 'Handling' );
		$fee->set_total( '5' );
		$order->add_item( $fee );
		$order->save();

		$this->sut->validate_posted_item_quantities(
			$order,
			array(
				'order_item_qty' => array( $fee->get_id() => '-1' ),
			)
		);
	}

	/**
	 * @testdox validate_new_item_quantity throws for a negative quantity on a new item.
	 */
	public function test_validate_new_item_throws_for_negative_quantity(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$this->expectException( \Exception::class );
		$this->sut->validate_new_item_quantity( -2.0, $product );
	}

	/**
	 * @testdox validate_new_item_quantity accepts zero and positive quantities.
	 */
	public function test_validate_new_item_accepts_non_negative_quantity(): void {
		$this->expectNotToPerformAssertions();

		$product = \WC_Helper_Product::create_simple_product();

		$this->sut->validate_new_item_quantity( 0.0, $product );
		$this->sut->validate_new_item_quantity( 3.0, $product );
	}

	/**
	 * @testdox get_quantity_input_min falls back to the default when the filter returns a non-numeric value.
	 */
	public function test_min_falls_back_on_non_numeric_filter_value(): void {
		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );

		$callback = function () {
			return array( 'not-a-number' );
		};
		add_filter( 'woocommerce_quantity_input_min_admin', $callback );
		$min = $this->sut->get_quantity_input_min( $items[0] );

		$this->assertSame( '0', $min );
	}

	/**
	 * @testdox validate_new_item_quantity runs the filter with the add context and the product.
	 */
	public function test_validate_new_item_passes_add_context_to_filter(): void {
		$product  = \WC_Helper_Product::create_simple_product();
		$captured = array();

		$callback = function ( $min, $filter_product, $context ) use ( &$captured ) {
			$captured = array( $filter_product, $context );
			return $min;
		};
		add_filter( 'woocommerce_quantity_input_min_admin', $callback, 10, 3 );
		$this->sut->validate_new_item_quantity( 1.0, $product );

		$this->assertSame( $product->get_id(), $captured[0]->get_id() );
		$this->assertSame( 'add', $captured[1] );
	}

	/**
	 * @testdox validate_posted_item_quantities keeps decimal precision on stores that allow decimal stock.
	 */
	public function test_validate_posted_rejects_decimal_below_min(): void {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );

		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );

		$this->expectException( \Exception::class );
		$this->sut->validate_posted_item_quantities(
			$order,
			array(
				'order_item_qty' => array( $items[0]->get_id() => '-0.5' ),
			)
		);
	}

	/**
	 * @testdox get_quantity_input_step returns 1 for an item with an integer quantity and default product step.
	 */
	public function test_step_is_one_by_default_for_integer_quantity_item(): void {
		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );

		$this->assertSame( 1.0, $this->sut->get_quantity_input_step( $items[0] ) );
	}

	/**
	 * @testdox get_quantity_input_step returns null (no step) by default on stores that allow decimal quantities, even for integer quantity items.
	 */
	public function test_step_is_any_by_default_on_decimal_stores_even_for_integer_quantity_item(): void {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );

		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );

		$this->assertNull( $this->sut->get_quantity_input_step( $items[0] ) );
	}

	/**
	 * @testdox get_quantity_input_step allows plugins to narrow the step on decimal stores with woocommerce_quantity_input_step_admin.
	 */
	public function test_step_is_narrowed_by_filter_on_decimal_stores(): void {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );

		$callback = function () {
			return '0.5';
		};
		add_filter( 'woocommerce_quantity_input_step_admin', $callback );

		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );

		$this->assertSame( 0.5, $this->sut->get_quantity_input_step( $items[0] ) );
	}

	/**
	 * @testdox get_quantity_input_step returns null (no step) for a decimal quantity item on decimal stores with the default step.
	 */
	public function test_step_defaults_to_any_for_stored_decimal_quantity(): void {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );

		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );
		$item  = $items[0];
		$item->set_quantity( 2.5 );
		$item->save();

		$this->assertNull( $this->sut->get_quantity_input_step( $item ) );
	}

	/**
	 * @testdox get_quantity_input_step returns the step on integer stores when the stored quantity does not match it.
	 */
	public function test_step_is_kept_for_stored_decimal_quantity_on_integer_stores(): void {
		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );
		$item  = $items[0];

		// Store a decimal quantity (as the REST API or an extension could), then restore integer behaviour.
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );
		$item->set_quantity( 2.5 );
		remove_filter( 'woocommerce_stock_amount', 'floatval' );
		add_filter( 'woocommerce_stock_amount', 'intval' );

		$this->assertTrue( wc_is_stock_amount_integer() );
		$this->assertSame( 1.0, $this->sut->get_quantity_input_step( $item ) );
		$this->assertFalse( $this->sut->is_step_aligned( 2.5, 0.0, 1.0 ) );
	}

	/**
	 * @testdox get_quantity_input_step applies the woocommerce_quantity_input_step_admin filter.
	 */
	public function test_step_is_filterable(): void {
		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );

		$callback = function () {
			return '0.5';
		};
		add_filter( 'woocommerce_quantity_input_step_admin', $callback );

		$this->assertSame( 0.5, $this->sut->get_quantity_input_step( $items[0] ) );
	}

	/**
	 * @testdox get_quantity_input_step falls back to the default when the filter returns an invalid value.
	 */
	public function test_step_falls_back_on_invalid_filter_value(): void {
		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );

		$callback = function () {
			return array( 'not-a-step' );
		};
		add_filter( 'woocommerce_quantity_input_step_admin', $callback );

		$this->assertSame( 1.0, $this->sut->get_quantity_input_step( $items[0] ) );
	}

	/**
	 * @testdox validate_new_item_quantity throws for a quantity that violates the step.
	 */
	public function test_validate_new_item_throws_for_step_mismatch(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$this->expectException( \Exception::class );
		$this->sut->validate_new_item_quantity( 2.5, $product );
	}

	/**
	 * @testdox validate_new_item_quantity accepts a quantity matching the product step.
	 */
	public function test_validate_new_item_accepts_valid_step_quantity(): void {
		$this->expectNotToPerformAssertions();

		$product = \WC_Helper_Product::create_simple_product();

		$this->sut->validate_new_item_quantity( 2.0, $product );
	}

	/**
	 * @testdox validate_new_item_quantity accepts decimal quantities on decimal stores.
	 */
	public function test_validate_new_item_accepts_decimal_quantity_on_decimal_stores(): void {
		$this->expectNotToPerformAssertions();

		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );

		$product = \WC_Helper_Product::create_simple_product();
		$this->sut->validate_new_item_quantity( 2.5, $product );
	}

	/**
	 * @testdox validate_new_item_quantity validates narrowed step on decimal stores.
	 */
	public function test_validate_new_item_validates_narrowed_step_on_decimal_stores(): void {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );

		$callback = function () {
			return '0.5';
		};
		add_filter( 'woocommerce_quantity_input_step_admin', $callback );

		$product = \WC_Helper_Product::create_simple_product();
		$this->sut->validate_new_item_quantity( 1.5, $product );

		$this->expectException( \Exception::class );
		$this->sut->validate_new_item_quantity( 1.2, $product );
	}

	/**
	 * @testdox validate_new_item_quantity runs the step filter with the add context and the product.
	 */
	public function test_validate_new_item_passes_add_context_to_step_filter(): void {
		$product  = \WC_Helper_Product::create_simple_product();
		$captured = array();

		$callback = function ( $step, $filter_product, $context ) use ( &$captured ) {
			$captured = array( $filter_product, $context );
			return $step;
		};
		add_filter( 'woocommerce_quantity_input_step_admin', $callback, 10, 3 );
		$this->sut->validate_new_item_quantity( 1.0, $product );

		$this->assertSame( $product->get_id(), $captured[0]->get_id() );
		$this->assertSame( 'add', $captured[1] );
	}

	/**
	 * @testdox validate_posted_item_quantities throws when a posted quantity violates the step.
	 */
	public function test_validate_posted_throws_for_step_mismatch(): void {
		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );
		$item  = $items[0];

		$this->expectException( \Exception::class );
		$this->sut->validate_posted_item_quantities(
			$order,
			array(
				'order_item_qty' => array( $item->get_id() => '2.5' ),
			)
		);
	}

	/**
	 * @testdox validate_posted_item_quantities accepts a valid step multiple.
	 */
	public function test_validate_posted_accepts_valid_step_quantity(): void {
		$this->expectNotToPerformAssertions();

		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );
		$item  = $items[0];

		$this->sut->validate_posted_item_quantities(
			$order,
			array(
				'order_item_qty' => array( $item->get_id() => '3' ),
			)
		);
	}

	/**
	 * @testdox validate_posted_item_quantities accepts an existing non-conforming decimal quantity.
	 */
	public function test_validate_posted_accepts_existing_decimal_quantity(): void {
		$this->expectNotToPerformAssertions();

		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );

		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );
		$item  = $items[0];
		$item->set_quantity( 2.5 );
		$item->save();

		$this->sut->validate_posted_item_quantities(
			$order,
			array(
				'order_item_qty' => array( $item->get_id() => '2.5' ),
			)
		);
	}

	/**
	 * @testdox validate_posted_item_quantities accepts decimal quantity updates on items holding integers on decimal stores.
	 */
	public function test_validate_posted_accepts_decimal_quantity_for_integer_item_on_decimal_stores(): void {
		$this->expectNotToPerformAssertions();

		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );

		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );
		$item  = $items[0];

		// Item currently holds an integer (1).
		$this->sut->validate_posted_item_quantities(
			$order,
			array(
				'order_item_qty' => array( $item->get_id() => '2.5' ),
			)
		);
	}

	/**
	 * @testdox validate_posted_item_quantities respects narrowed step on decimal stores.
	 */
	public function test_validate_posted_respects_narrowed_step_on_decimal_stores(): void {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );

		$callback = function () {
			return '0.5';
		};
		add_filter( 'woocommerce_quantity_input_step_admin', $callback );

		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );
		$item  = $items[0];

		$this->sut->validate_posted_item_quantities(
			$order,
			array(
				'order_item_qty' => array( $item->get_id() => '2.5' ),
			)
		);

		$this->expectException( \Exception::class );
		$this->sut->validate_posted_item_quantities(
			$order,
			array(
				'order_item_qty' => array( $item->get_id() => '2.3' ),
			)
		);
	}

	/**
	 * @testdox woocommerce_quantity_input_step_admin receives a numeric step on decimal stores and supports arithmetic in callbacks.
	 */
	public function test_step_filter_receives_numeric_step_on_decimal_stores(): void {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );

		$captured_step = null;
		add_filter(
			'woocommerce_quantity_input_step_admin',
			function ( $step ) use ( &$captured_step ) {
				$captured_step = $step;
				// Arithmetic operation such as $step * 2 should not throw a TypeError in PHP 8.
				return $step * 2;
			}
		);

		$order = WC_Helper_Order::create_order();
		$items = array_values( $order->get_items() );

		$step = $this->sut->get_quantity_input_step( $items[0] );

		$this->assertIsNumeric( $captured_step );
		$this->assertEquals( 1.0, $captured_step );
		$this->assertSame( 2.0, $step );
	}

	/**
	 * @testdox Products with custom purchase step enforce that step on decimal stores across render, add, and save.
	 */
	public function test_custom_product_step_enforced_on_decimal_stores(): void {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );

		$product = \WC_Helper_Product::create_simple_product();

		add_filter(
			'woocommerce_quantity_input_step',
			function ( $step, $filter_product ) use ( $product ) {
				if ( $filter_product && $filter_product->get_id() === $product->get_id() ) {
					return 0.5;
				}
				return $step;
			},
			10,
			2
		);

		$order = WC_Helper_Order::create_order();
		$order->add_product( $product, 1.5 );
		$order->save();
		$items = array_values( $order->get_items() );
		$item  = end( $items );

		// Step is rendered as the custom step on decimal stores.
		$this->assertSame( 0.5, $this->sut->get_quantity_input_step( $item, $product ) );

		// Adding matching step quantity succeeds.
		$this->sut->validate_new_item_quantity( 1.5, $product );

		// Saving matching step quantity succeeds.
		$this->sut->validate_posted_item_quantities(
			$order,
			array(
				'order_item_qty' => array( $item->get_id() => '2.5' ),
			)
		);

		// Adding non-matching step quantity throws.
		$this->expectException( \Exception::class );
		$this->sut->validate_new_item_quantity( 1.2, $product );
	}

	/**
	 * @testdox Products with custom purchase step reject invalid posted quantity on decimal stores.
	 */
	public function test_custom_product_step_rejects_invalid_posted_quantity_on_decimal_stores(): void {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );

		$product = \WC_Helper_Product::create_simple_product();

		add_filter(
			'woocommerce_quantity_input_step',
			function ( $step, $filter_product ) use ( $product ) {
				if ( $filter_product && $filter_product->get_id() === $product->get_id() ) {
					return 0.5;
				}
				return $step;
			},
			10,
			2
		);

		$order = WC_Helper_Order::create_order();
		$order->add_product( $product, 1.5 );
		$order->save();
		$items = array_values( $order->get_items() );
		$item  = end( $items );

		$this->expectException( \Exception::class );
		$this->sut->validate_posted_item_quantities(
			$order,
			array(
				'order_item_qty' => array( $item->get_id() => '2.3' ),
			)
		);
	}

	/**
	 * @testdox get_quantity_input_step returns the product step when the stored quantity does not match it.
	 */
	public function test_step_is_kept_for_a_stored_quantity_that_does_not_match_it(): void {
		list( , $item ) = $this->create_order_with_off_step_item();

		$this->assertSame( 0.5, $this->sut->get_quantity_input_step( $item ) );
		$this->assertFalse( $this->sut->is_step_aligned( (float) $item->get_quantity(), 0.0, 0.5 ) );
	}

	/**
	 * @testdox is_step_aligned accepts any quantity when there is no step.
	 */
	public function test_is_step_aligned_accepts_any_quantity_without_a_step(): void {
		$this->assertTrue( $this->sut->is_step_aligned( 1.3, 0.0, null ) );
	}

	/**
	 * @testdox validate_posted_item_quantities accepts a stored quantity that does not match the step when it is posted unchanged.
	 */
	public function test_validate_posted_accepts_unchanged_quantity_that_does_not_match_step(): void {
		$this->expectNotToPerformAssertions();

		list( $order, $item ) = $this->create_order_with_off_step_item();

		$this->sut->validate_posted_item_quantities(
			$order,
			array(
				'order_item_qty' => array( $item->get_id() => '1.3' ),
			)
		);
	}

	/**
	 * @testdox validate_posted_item_quantities rejects a changed quantity that does not match the step, on an item stored off-step.
	 */
	public function test_validate_posted_rejects_changed_quantity_that_does_not_match_step(): void {
		list( $order, $item ) = $this->create_order_with_off_step_item();

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'must be a multiple of 0.5' );

		$this->sut->validate_posted_item_quantities(
			$order,
			array(
				'order_item_qty' => array( $item->get_id() => '1.7' ),
			)
		);
	}

	/**
	 * @testdox validate_posted_item_quantities accepts a changed quantity that matches the step, on an item stored off-step.
	 */
	public function test_validate_posted_accepts_changed_quantity_that_matches_step(): void {
		$this->expectNotToPerformAssertions();

		list( $order, $item ) = $this->create_order_with_off_step_item();

		$this->sut->validate_posted_item_quantities(
			$order,
			array(
				'order_item_qty' => array( $item->get_id() => '1.5' ),
			)
		);
	}

	/**
	 * Create an order on a decimal store with one item of a step-0.5 product stored at 1.3.
	 *
	 * @return array{0: \WC_Order, 1: \WC_Order_Item_Product} The order and the item.
	 */
	private function create_order_with_off_step_item(): array {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );

		$product = \WC_Helper_Product::create_simple_product();

		add_filter(
			'woocommerce_quantity_input_step',
			function ( $step, $filter_product ) use ( $product ) {
				if ( $filter_product && $filter_product->get_id() === $product->get_id() ) {
					return 0.5;
				}
				return $step;
			},
			10,
			2
		);

		$order = WC_Helper_Order::create_order();
		$order->add_product( $product, 1.3 );
		$order->save();
		$items = array_values( $order->get_items() );

		return array( $order, end( $items ) );
	}
}
