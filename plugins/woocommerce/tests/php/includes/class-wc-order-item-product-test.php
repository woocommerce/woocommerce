<?php
/**
 * Unit tests for the WC_Order_Item_Product class functionalities.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareUnitTestSuiteTrait;

/**
 * WC_Order_Item_Product unit tests.
 */
class WC_Order_Item_Product_Test extends WC_Unit_Test_Case {
	use CogsAwareUnitTestSuiteTrait;

	/**
	 * The product used for the tests.
	 *
	 * @var WC_Product_Simple
	 */
	private WC_Product_Simple $product;

	/**
	 * The order item used for the tests.
	 *
	 * @var WC_Order_Item_Product
	 */
	private WC_Order_Item_Product $item;

	/**
	 * The order used for the tests.
	 *
	 * @var WC_Order
	 */
	private WC_Order $order;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->product = WC_Helper_Product::create_simple_product();
		$this->order   = WC_Helper_Order::create_order();

		$this->item = new WC_Order_Item_Product();
		$this->item->set_product( $this->product );
		$this->item->set_quantity( 1 );
		$this->item->set_order_id( $this->order->get_id() );
		$this->item->save();

		$this->order->add_item( $this->item );
		$this->order->save();
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->disable_cogs_feature();
	}

	/**
	 * Test that backorder meta is excluded from formatted meta data
	 * for completed orders.
	 */
	public function test_get_formatted_meta_data_excludes_backorder_on_completed() {
		// 1. Set backorders allowed for the product.
		$this->product->set_manage_stock( true );
		$this->product->set_stock_quantity( 0 );
		$this->product->set_backorders( 'notify' ); // Allow backorders with notification.
		$this->product->save();

		// 2. Add backorder meta.
		$backorder_meta_key = 'Backordered';
		$this->item->add_meta_data( $backorder_meta_key, 1, true ); // Value typically is the backordered quantity.
		$this->item->save();

		// 3. Assert: Check meta exists before completion.
		$item_id                = $this->item->get_id();
		$order_item             = $this->order->get_item( $item_id ); // Re-fetch item from order.
		$formatted_meta_before  = $order_item->get_formatted_meta_data();
		$found_backorder_before = false;
		foreach ( $formatted_meta_before as $meta ) {
			if ( $meta->key === $backorder_meta_key ) {
				$found_backorder_before = true;
				break;
			}
		}
		$this->assertTrue( $found_backorder_before, 'Backorder meta should exist before order completion.' );

		// 4. Complete the order.
		$this->order->update_status( OrderStatus::COMPLETED );
		$this->order->save();

		// 5. Assert: Check meta is excluded after completion.
		$order_item_after_complete = $this->order->get_item( $item_id ); // Re-fetch item again after status change.
		$formatted_meta_after      = $order_item_after_complete->get_formatted_meta_data();
		$found_backorder_after     = false;
		foreach ( $formatted_meta_after as $meta ) {
			if ( $meta->key === $backorder_meta_key ) {
				$found_backorder_after = true;
				break;
			}
		}
		$this->assertFalse( $found_backorder_after, 'Backorder meta should be excluded after order completion.' );

		// Clean up.
		WC_Helper_Product::delete_product( $this->product->get_id() );
		WC_Helper_Order::delete_order( $this->order->get_id() );
	}

	/**
	 * @testdox 'doing it wrong' is thrown, if the Cost of Goods Sold feature is disabled.
	 */
	public function test_get_refund_html_with_cogs_disabled() {
		$this->expect_doing_it_wrong_cogs_disabled( 'WC_Order_Item::get_cogs_refund_value_html' );

		$this->item->get_cogs_refund_value_html( -12.34 );
	}

	/**
	 * @testdox Test get_cogs_refund_value_html with implicit WC_Price and order arguments.
	 *
	 * @param bool $negative_refund_argument True to pass a negative refund amount, false to pass a positive amount.
	 *
	 * @testWith [true]
	 *           [false]
	 */
	public function test_get_refund_html_with_implicit_arguments( bool $negative_refund_argument ) {
		$this->enable_cogs_feature();

		$refund_amount = 12.34;
		$actual        = $this->item->get_cogs_refund_value_html( $negative_refund_argument ? -12.34 : 12.34 );
		$expected      = sprintf( '<small class="refunded">%s</small>', wc_price( -12.34, array( 'currency' => $this->order->get_currency() ) ) );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox Test get_cogs_refund_value_html with explicit WC_Price and order arguments.
	 */
	public function test_get_refund_html_with_explicit_arguments() {
		$this->enable_cogs_feature();

		$wc_price_args = array( 'currency' => '!' );
		$actual        = $this->item->get_cogs_refund_value_html( -12.34, $wc_price_args, $this->order );
		$expected      = sprintf( '<small class="refunded">%s</small>', wc_price( -12.34, array( 'currency' => '!' ) ) );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox Test the woocommerce_order_item_cogs_refunded_html filter invoked by get_cogs_refund_value_html.
	 */
	public function test_get_refund_html_with_filter() {
		$this->enable_cogs_feature();

		$refunded_cost = -12.34;
		add_filter(
			'woocommerce_order_item_cogs_refunded_html',
			function ( $html, $refunded_cost, $item, $order ) {
				return sprintf( 'cost: %s, item: %s, order: %s', $refunded_cost, $item->get_id(), $order->get_id() );
			},
			10,
			4
		);

		$actual = $this->item->get_cogs_refund_value_html( $refunded_cost, );
		remove_all_filters( 'woocommerce_order_item_cogs_refunded_html' );
		$expected = sprintf( 'cost: %s, item: %s, order: %s', $refunded_cost, $this->item->get_id(), $this->order->get_id() );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox calculate_cogs_value_core recalculates COGS from product even for saved line items.
	 */
	public function test_calculate_cogs_value_core_recalculates_from_product() {
		$this->enable_cogs_feature();

		// Create a line item with 3 units of a product with COGS = 10.

		$this->product->set_cogs_value( 10.00 );
		$this->product->save();

		$item = new WC_Order_Item_Product();
		$item->set_product( $this->product );
		$item->set_quantity( 3 );
		$item->set_order_id( $this->order->get_id() );
		$item->save();

		$calculated_cogs = $item->calculate_cogs_value_core();
		$this->assertEquals( 30.00, $calculated_cogs );

		// Now change the product's COGS value and recalculate COGS for the line.

		$this->product->set_cogs_value( 15.00 );
		$this->product->save();

		$reloaded_item = new WC_Order_Item_Product( $item->get_id() );

		$recalculated_cogs = $reloaded_item->calculate_cogs_value_core();
		$this->assertEquals( 45.00, $recalculated_cogs );
	}

	/**
	 * @testdox calculate_cogs_value_core calculates correctly with quantity $quantity and COGS $cogs_value.
	 *
	 * @param float $cogs_value The COGS value per unit.
	 * @param int   $quantity The quantity (positive for regular items, negative for refunds).
	 * @param float $expected The expected calculated COGS value.
	 *
	 * @testWith [12.50, 5, 62.50]
	 *           [20.00, -2, -40.00]
	 */
	public function test_calculate_cogs_value_core_with_various_quantities( float $cogs_value, int $quantity, float $expected ) {
		$this->enable_cogs_feature();

		$this->product->set_cogs_value( $cogs_value );
		$this->product->save();

		$item = new WC_Order_Item_Product();
		$item->set_product( $this->product );
		$item->set_quantity( $quantity );
		$item->set_order_id( $this->order->get_id() );
		$item->save();

		$calculated_cogs = $item->calculate_cogs_value_core();
		$this->assertEquals( $expected, $calculated_cogs );
	}

	/**
	 * @testdox calculate_cogs_value_core returns null when product no longer exists.
	 */
	public function test_calculate_cogs_value_core_returns_null_when_product_missing() {
		$this->enable_cogs_feature();

		$item = new WC_Order_Item_Product();
		$item->set_product( $this->product );
		$item->set_quantity( 2 );
		$item->set_order_id( $this->order->get_id() );
		$item->save();

		$product_id = $this->product->get_id();
		wp_delete_post( $product_id, true );

		$reloaded_item = new WC_Order_Item_Product( $item->get_id() );

		$calculated_cogs = $reloaded_item->calculate_cogs_value_core();
		$this->assertNull( $calculated_cogs );
	}

	/**
	 * @testdox calculate_cogs_value_core returns zero when product has no COGS value.
	 */
	public function test_calculate_cogs_value_core_with_product_without_cogs() {
		$this->enable_cogs_feature();

		$product_without_cogs = WC_Helper_Product::create_simple_product();

		$item = new WC_Order_Item_Product();
		$item->set_product( $product_without_cogs );
		$item->set_quantity( 3 );

		$calculated_cogs = $item->calculate_cogs_value_core();
		$this->assertEquals( 0.0, $calculated_cogs );

		WC_Helper_Product::delete_product( $product_without_cogs->get_id() );
	}

	/**
	 * @testdox set_variation_id throws exception with variation_id in error data for invalid ID.
	 */
	public function test_set_variation_id_throws_exception_with_data_for_invalid_id() {
		$invalid_id = 999999;
		$item       = new WC_Order_Item_Product();

		try {
			$item->set_variation_id( $invalid_id );
			$this->fail( 'Setting an invalid variation ID should have thrown an exception.' );
		} catch ( WC_Data_Exception $e ) {
			$this->assertEquals( 'order_item_product_invalid_variation_id', $e->getErrorCode() );
			$error_data = $e->getErrorData();
			$this->assertEquals( 400, $error_data['status'] );
			$this->assertArrayHasKey( 'variation_id', $error_data );
			$this->assertEquals( $invalid_id, $error_data['variation_id'] );
		}
	}

	/**
	 * @testdox set_taxes handles proper array format correctly.
	 */
	public function test_set_taxes_with_valid_array_format() {
		$item = new WC_Order_Item_Product();

		// Valid tax data format: arrays keyed by tax rate ID.
		$valid_tax_data = array(
			'total'    => array(
				1 => '24.00',
				2 => '12.00',
			),
			'subtotal' => array(
				1 => '24.00',
				2 => '12.00',
			),
		);

		$item->set_taxes( $valid_tax_data );
		$taxes = $item->get_taxes();

		$this->assertIsArray( $taxes );
		$this->assertArrayHasKey( 'total', $taxes );
		$this->assertArrayHasKey( 'subtotal', $taxes );
		$this->assertEquals( '24.00', $taxes['total'][1] );
		$this->assertEquals( '12.00', $taxes['total'][2] );
	}

	/**
	 * @testdox set_taxes handles serialized array format correctly.
	 */
	public function test_set_taxes_with_serialized_array_format() {
		$item = new WC_Order_Item_Product();

		// Serialized tax data (as it would be stored in database).
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Testing legacy serialized data format.
		$serialized_tax_data = serialize(
			array(
				'total'    => array( 1 => '24.00' ),
				'subtotal' => array( 1 => '24.00' ),
			)
		);

		$item->set_taxes( $serialized_tax_data );
		$taxes = $item->get_taxes();

		$this->assertIsArray( $taxes );
		$this->assertEquals( '24.00', $taxes['total'][1] );
	}

	/**
	 * @testdox set_taxes handles legacy float values for total/subtotal without fatal error.
	 *
	 * This test reproduces the issue from order #20924 (June 2021) where old orders
	 * may have tax data stored as floats instead of arrays, causing:
	 * "TypeError: array_map(): Argument #2 ($array) must be of type array, float given"
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/60233
	 */
	public function test_set_taxes_with_legacy_float_values_does_not_throw_error() {
		$order = WC_Helper_Order::create_order();
		$order->save();

		$item = new WC_Order_Item_Product();
		$item->set_order_id( $order->get_id() );

		// Legacy/corrupted tax data: floats instead of arrays.
		// This format may exist in old orders due to data corruption or legacy data formats.
		$legacy_tax_data = array(
			'total'    => 24.00,    // Should be array( rate_id => amount ).
			'subtotal' => 24.00,    // Should be array( rate_id => amount ).
		);

		// This should NOT throw a TypeError.
		$item->set_taxes( $legacy_tax_data );

		// Taxes should be converted to arrays and values preserved.
		$taxes = $item->get_taxes();
		$this->assertIsArray( $taxes );
		$this->assertIsArray( $taxes['total'] );
		$this->assertIsArray( $taxes['subtotal'] );
		$this->assertCount( 1, $taxes['total'] );
		$this->assertCount( 1, $taxes['subtotal'] );
		// The tax value should be preserved (converted from float to array).
		// wc_format_decimal() may strip trailing zeros, so we compare as floats.
		// Use reset() to get first value regardless of key (rate_id may be inferred from order context).
		$this->assertEquals( 24.00, (float) reset( $taxes['total'] ) );
		$this->assertEquals( 24.00, (float) reset( $taxes['subtotal'] ) );

		$order->delete( true );
	}

	/**
	 * @testdox set_taxes handles serialized legacy float values without fatal error.
	 *
	 * Simulates the exact scenario from the production error where _line_tax_data
	 * metadata contains serialized floats instead of arrays.
	 */
	public function test_set_taxes_with_serialized_legacy_float_values_does_not_throw_error() {
		$order = WC_Helper_Order::create_order();
		$order->save();

		$item = new WC_Order_Item_Product();
		$item->set_order_id( $order->get_id() );

		// Serialized legacy data with floats (as stored in wp_woocommerce_order_itemmeta).
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Testing legacy serialized data format.
		$serialized_legacy_data = serialize(
			array(
				'total'    => 144.00,
				'subtotal' => 144.00,
			)
		);

		// This should NOT throw a TypeError.
		$item->set_taxes( $serialized_legacy_data );

		$taxes = $item->get_taxes();
		$this->assertIsArray( $taxes );
		$this->assertIsArray( $taxes['total'] );
		$this->assertIsArray( $taxes['subtotal'] );
		$this->assertCount( 1, $taxes['total'] );
		$this->assertCount( 1, $taxes['subtotal'] );
		// The tax value should be preserved (converted from float to array).
		// wc_format_decimal() may strip trailing zeros, so we compare as floats.
		// Use reset() to get first value regardless of key (rate_id may be inferred from order context).
		$this->assertEquals( 144.00, (float) reset( $taxes['total'] ) );
		$this->assertEquals( 144.00, (float) reset( $taxes['subtotal'] ) );

		$order->delete( true );
	}

	/**
	 * @testdox set_taxes handles mixed format (one array, one float) without fatal error.
	 */
	public function test_set_taxes_with_mixed_array_and_float_does_not_throw_error() {
		$order = WC_Helper_Order::create_order();
		$order->save();

		$item = new WC_Order_Item_Product();
		$item->set_order_id( $order->get_id() );

		// Mixed format: subtotal is array, total is float.
		$mixed_tax_data = array(
			'total'    => 24.00,                        // Float.
			'subtotal' => array( 1 => '24.00' ),        // Array.
		);

		// This should NOT throw a TypeError.
		$item->set_taxes( $mixed_tax_data );

		$taxes = $item->get_taxes();
		$this->assertIsArray( $taxes );
		$this->assertIsArray( $taxes['total'] );
		$this->assertIsArray( $taxes['subtotal'] );
		$this->assertCount( 1, $taxes['total'] );
		$this->assertCount( 1, $taxes['subtotal'] );
		// Float total converted to array, array subtotal preserves key.
		// wc_format_decimal() may strip trailing zeros, so we compare as floats.
		// Use reset() to get first value regardless of key (rate_id may be inferred from order context).
		$this->assertEquals( 24.00, (float) reset( $taxes['total'] ) );
		// Subtotal was already an array with key 1, so it should preserve that key.
		$this->assertEquals( 24.00, (float) $taxes['subtotal'][1] );

		$order->delete( true );
	}

	/**
	 * @testdox set_taxes handles empty/null values gracefully.
	 */
	public function test_set_taxes_with_empty_values() {
		$item = new WC_Order_Item_Product();

		// Empty tax data.
		$item->set_taxes( array() );
		$taxes = $item->get_taxes();
		$this->assertIsArray( $taxes );
		$this->assertEmpty( $taxes['total'] );
		$this->assertEmpty( $taxes['subtotal'] );

		// Null value.
		$item->set_taxes( null );
		$taxes = $item->get_taxes();
		$this->assertIsArray( $taxes );

		// False value.
		$item->set_taxes( false );
		$taxes = $item->get_taxes();
		$this->assertIsArray( $taxes );
	}

	/**
	 * @testdox set_taxes handles string values for total/subtotal without fatal error.
	 */
	public function test_set_taxes_with_string_values_does_not_throw_error() {
		$order = WC_Helper_Order::create_order();
		$order->save();

		$item = new WC_Order_Item_Product();
		$item->set_order_id( $order->get_id() );

		// String values instead of arrays.
		$string_tax_data = array(
			'total'    => '24.00',
			'subtotal' => '24.00',
		);

		// This should NOT throw a TypeError.
		$item->set_taxes( $string_tax_data );

		$taxes = $item->get_taxes();
		$this->assertIsArray( $taxes );
		$this->assertIsArray( $taxes['total'] );
		$this->assertIsArray( $taxes['subtotal'] );
		$this->assertCount( 1, $taxes['total'] );
		$this->assertCount( 1, $taxes['subtotal'] );
		// String values converted to arrays and preserved.
		// wc_format_decimal() may strip trailing zeros, so we compare as floats.
		// Use reset() to get first value regardless of key (rate_id may be inferred from order context).
		$this->assertEquals( 24.00, (float) reset( $taxes['total'] ) );
		$this->assertEquals( 24.00, (float) reset( $taxes['subtotal'] ) );

		$order->delete( true );
	}

	/**
	 * @testdox set_taxes infers tax rate ID from order context when handling legacy data.
	 */
	public function test_set_taxes_with_legacy_data_infers_rate_id_from_order() {
		// Create a real order with a tax item.
		$order = WC_Helper_Order::create_order();
		$order->save();

		// Add a tax item to the order to provide context.
		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate_id( 42 );
		$tax_item->set_name( 'Test Tax' );
		$tax_item->set_tax_total( 10 );
		$order->add_item( $tax_item );
		$order->save();

		// Create a product item associated with this order.
		$item = new WC_Order_Item_Product();
		$item->set_order_id( $order->get_id() );

		// Legacy tax data as float.
		$legacy_tax_data = array(
			'total'    => 24.00,
			'subtotal' => 24.00,
		);

		$item->set_taxes( $legacy_tax_data );

		$taxes = $item->get_taxes();
		$this->assertIsArray( $taxes );
		$this->assertIsArray( $taxes['total'] );

		// The rate_id should be inferred from the order's tax item.
		// Key should be 42 (the rate_id from the tax item we added).
		$this->assertArrayHasKey( 42, $taxes['total'] );
		$this->assertEquals( 24.00, (float) $taxes['total'][42] );

		// Clean up.
		$order->delete( true );
	}

	/**
	 * @testdox set_taxes uses rate_id 0 when order is null or false for legacy data.
	 */
	public function test_set_taxes_with_legacy_data_uses_rate_id_zero_when_order_is_null() {
		// Create an item NOT associated with any order (get_order() will return false).
		$item = new WC_Order_Item_Product();

		// Legacy tax data as float.
		$legacy_tax_data = array(
			'total'    => 24.00,
			'subtotal' => 24.00,
		);

		// This should NOT throw an error even though order is null/false.
		$item->set_taxes( $legacy_tax_data );

		$taxes = $item->get_taxes();
		$this->assertIsArray( $taxes );
		$this->assertIsArray( $taxes['total'] );
		$this->assertIsArray( $taxes['subtotal'] );

		// Without order context, the rate_id should default to 0.
		$this->assertArrayHasKey( 0, $taxes['total'] );
		$this->assertArrayHasKey( 0, $taxes['subtotal'] );
		$this->assertEquals( 24.00, (float) $taxes['total'][0] );
		$this->assertEquals( 24.00, (float) $taxes['subtotal'][0] );
	}

	/**
	 * @testdox set_taxes filter allows plugins to customize legacy tax conversion.
	 */
	public function test_set_taxes_legacy_conversion_filter() {
		// Create an order - the filter is only called when an order context exists.
		$order = WC_Helper_Order::create_order();
		$order->save();

		$item = new WC_Order_Item_Product();
		$item->set_order_id( $order->get_id() );

		// Add a filter to customize the conversion.
		$filter_callback = function ( $converted, $value ) {
			// Custom rate ID mapping - we only need $value to create our custom conversion.
			unset( $converted );
			return array( 999 => $value );
		};
		add_filter( 'woocommerce_order_item_legacy_tax_conversion', $filter_callback, 10, 2 );

		// Legacy tax data as float.
		$legacy_tax_data = array(
			'total'    => 50.00,
			'subtotal' => 50.00,
		);

		$item->set_taxes( $legacy_tax_data );

		$taxes = $item->get_taxes();

		// The filter should have used rate ID 999.
		$this->assertArrayHasKey( 999, $taxes['total'] );
		$this->assertEquals( 50.00, (float) $taxes['total'][999] );

		// Clean up filter.
		remove_filter( 'woocommerce_order_item_legacy_tax_conversion', $filter_callback );

		// Clean up order.
		$order->delete( true );
	}
}
