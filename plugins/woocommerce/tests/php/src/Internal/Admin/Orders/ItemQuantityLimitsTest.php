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
		remove_filter( 'woocommerce_quantity_input_min_admin', $callback );

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
		remove_filter( 'woocommerce_quantity_input_min_admin', $callback );

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

		$this->assertSame( '1', $this->sut->get_quantity_input_step( $items[0] ) );
	}

	/**
	 * @testdox get_quantity_input_step defaults to any when the stored quantity does not align with the step.
	 */
	public function test_step_defaults_to_any_for_stored_decimal_quantity(): void {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );
		try {
			$order = WC_Helper_Order::create_order();
			$items = array_values( $order->get_items() );
			$item  = $items[0];
			$item->set_quantity( 2.5 );
			$item->save();

			$this->assertSame( 'any', $this->sut->get_quantity_input_step( $item ) );
		} finally {
			remove_filter( 'woocommerce_stock_amount', 'floatval' );
			add_filter( 'woocommerce_stock_amount', 'intval' );
		}
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
		$step = $this->sut->get_quantity_input_step( $items[0] );
		remove_filter( 'woocommerce_quantity_input_step_admin', $callback );

		$this->assertSame( '0.5', $step );
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
		$step = $this->sut->get_quantity_input_step( $items[0] );
		remove_filter( 'woocommerce_quantity_input_step_admin', $callback );

		$this->assertSame( '1', $step );
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
		remove_filter( 'woocommerce_quantity_input_step_admin', $callback );

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
		try {
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
		} finally {
			remove_filter( 'woocommerce_stock_amount', 'floatval' );
			add_filter( 'woocommerce_stock_amount', 'intval' );
		}
	}
}
