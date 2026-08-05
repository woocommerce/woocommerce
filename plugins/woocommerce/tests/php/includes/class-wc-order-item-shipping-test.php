<?php
/**
 * Unit tests for the WC_Order_Item_Shipping class functionalities.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

/**
 * WC_Order_Item_Shipping unit tests.
 */
class WC_Order_Item_Shipping_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox set_taxes handles valid array format correctly.
	 */
	public function test_set_taxes_with_valid_array_format() {
		$order = WC_Helper_Order::create_order();
		$order->save();

		$item = new WC_Order_Item_Shipping();
		$item->set_order_id( $order->get_id() );

		$valid_tax_data = array(
			'total' => array(
				1 => '24.00',
				2 => '12.00',
			),
		);

		$item->set_taxes( $valid_tax_data );
		$taxes = $item->get_taxes();

		$this->assertIsArray( $taxes );
		$this->assertArrayHasKey( 'total', $taxes );
		$this->assertIsArray( $taxes['total'] );
		$this->assertEquals( '24.00', $taxes['total'][1] );
		$this->assertEquals( '12.00', $taxes['total'][2] );

		$order->delete( true );
	}

	/**
	 * @testdox set_taxes handles serialized array format correctly.
	 */
	public function test_set_taxes_with_serialized_array_format() {
		$order = WC_Helper_Order::create_order();
		$order->save();

		$item = new WC_Order_Item_Shipping();
		$item->set_order_id( $order->get_id() );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Testing legacy serialized data format.
		$serialized_tax_data = serialize(
			array(
				'total' => array( 1 => '24.00' ),
			)
		);

		$item->set_taxes( $serialized_tax_data );
		$taxes = $item->get_taxes();

		$this->assertIsArray( $taxes );
		$this->assertEquals( '24.00', $taxes['total'][1] );

		$order->delete( true );
	}

	/**
	 * @testdox set_taxes handles legacy float values for total without fatal error.
	 *
	 * This test reproduces the issue where old orders may have tax data stored
	 * as floats instead of arrays, causing:
	 * "TypeError: array_map(): Argument #2 ($array) must be of type array, float given"
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/60233
	 */
	public function test_set_taxes_with_legacy_float_values_does_not_throw_error() {
		$order = WC_Helper_Order::create_order();
		$order->save();

		$item = new WC_Order_Item_Shipping();
		$item->set_order_id( $order->get_id() );

		// Legacy/corrupted tax data: float instead of array.
		// This format may exist in old orders due to data corruption or legacy data formats.
		$legacy_tax_data = array(
			'total' => 24.00,    // Should be array( rate_id => amount ).
		);

		// This should NOT throw a TypeError.
		$item->set_taxes( $legacy_tax_data );

		// Taxes should be converted to arrays and values preserved.
		$taxes = $item->get_taxes();
		$this->assertIsArray( $taxes );
		$this->assertIsArray( $taxes['total'] );
		$this->assertCount( 1, $taxes['total'] );
		// The tax value should be preserved (converted from float to array).
		// wc_format_decimal() may strip trailing zeros, so we compare as floats.
		// Use reset() to get first value regardless of key (rate_id may be inferred from order context).
		$this->assertEquals( 24.00, (float) reset( $taxes['total'] ) );

		$order->delete( true );
	}

	/**
	 * @testdox set_taxes handles serialized legacy float values without fatal error.
	 *
	 * Simulates the exact scenario from the production error where tax data
	 * metadata contains serialized floats instead of arrays.
	 */
	public function test_set_taxes_with_serialized_legacy_float_values_does_not_throw_error() {
		$order = WC_Helper_Order::create_order();
		$order->save();

		$item = new WC_Order_Item_Shipping();
		$item->set_order_id( $order->get_id() );

		// Serialized legacy data with float (as stored in wp_woocommerce_order_itemmeta).
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Testing legacy serialized data format.
		$serialized_legacy_data = serialize(
			array(
				'total' => 144.00,
			)
		);

		// This should NOT throw a TypeError.
		$item->set_taxes( $serialized_legacy_data );

		$taxes = $item->get_taxes();
		$this->assertIsArray( $taxes );
		$this->assertIsArray( $taxes['total'] );
		$this->assertCount( 1, $taxes['total'] );
		// The tax value should be preserved (converted from float to array).
		// wc_format_decimal() may strip trailing zeros, so we compare as floats.
		// Use reset() to get first value regardless of key (rate_id may be inferred from order context).
		$this->assertEquals( 144.00, (float) reset( $taxes['total'] ) );

		$order->delete( true );
	}

	/**
	 * @testdox set_taxes handles string values for total without fatal error.
	 */
	public function test_set_taxes_with_string_values_does_not_throw_error() {
		$order = WC_Helper_Order::create_order();
		$order->save();

		$item = new WC_Order_Item_Shipping();
		$item->set_order_id( $order->get_id() );

		// String value instead of array.
		$string_tax_data = array(
			'total' => '24.00',
		);

		// This should NOT throw a TypeError.
		$item->set_taxes( $string_tax_data );

		$taxes = $item->get_taxes();
		$this->assertIsArray( $taxes );
		$this->assertIsArray( $taxes['total'] );
		$this->assertCount( 1, $taxes['total'] );
		// String values converted to arrays and preserved.
		// wc_format_decimal() may strip trailing zeros, so we compare as floats.
		// Use reset() to get first value regardless of key (rate_id may be inferred from order context).
		$this->assertEquals( 24.00, (float) reset( $taxes['total'] ) );

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

		// Create a shipping item associated with this order.
		$item = new WC_Order_Item_Shipping();
		$item->set_order_id( $order->get_id() );

		// Legacy tax data as float.
		$legacy_tax_data = array(
			'total' => 24.00,
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
		$item = new WC_Order_Item_Shipping();

		// Legacy tax data as float.
		$legacy_tax_data = array(
			'total' => 24.00,
		);

		// This should NOT throw an error even though order is null/false.
		$item->set_taxes( $legacy_tax_data );

		$taxes = $item->get_taxes();
		$this->assertIsArray( $taxes );
		$this->assertIsArray( $taxes['total'] );

		// Without order context, the rate_id should default to 0.
		$this->assertArrayHasKey( 0, $taxes['total'] );
		$this->assertEquals( 24.00, (float) $taxes['total'][0] );
	}

	/**
	 * @testdox set_taxes filter allows plugins to customize legacy tax conversion.
	 */
	public function test_set_taxes_legacy_conversion_filter() {
		// Create an order - the filter is only called when an order context exists.
		$order = WC_Helper_Order::create_order();
		$order->save();

		$item = new WC_Order_Item_Shipping();
		$item->set_order_id( $order->get_id() );

		// Add a filter to customize the conversion.
		$filter_callback = function ( $converted, $value ) {
			// Custom rate ID mapping.
			unset( $converted );
			return array( 999 => $value );
		};
		add_filter( 'woocommerce_order_item_legacy_tax_conversion', $filter_callback, 10, 2 );

		// Legacy tax data as float.
		$legacy_tax_data = array(
			'total' => 50.00,
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

	/**
	 * @testdox set_taxes handles older format where raw_tax_data is directly an array.
	 */
	public function test_set_taxes_with_older_array_format() {
		$order = WC_Helper_Order::create_order();
		$order->save();

		$item = new WC_Order_Item_Shipping();
		$item->set_order_id( $order->get_id() );

		// Older format: raw_tax_data is directly an array (not nested under 'total').
		$older_format_data = array(
			1 => '24.00',
			2 => '12.00',
		);

		$item->set_taxes( $older_format_data );
		$taxes = $item->get_taxes();

		$this->assertIsArray( $taxes );
		$this->assertIsArray( $taxes['total'] );
		$this->assertEquals( '24.00', $taxes['total'][1] );
		$this->assertEquals( '12.00', $taxes['total'][2] );

		$order->delete( true );
	}

	/**
	 * @testdox set_taxes handles empty/null values gracefully.
	 */
	public function test_set_taxes_with_empty_values() {
		$item = new WC_Order_Item_Shipping();

		// Empty tax data.
		$item->set_taxes( array() );
		$taxes = $item->get_taxes();
		$this->assertIsArray( $taxes );
		$this->assertEmpty( $taxes['total'] );

		// Null value.
		$item->set_taxes( null );
		$taxes = $item->get_taxes();
		$this->assertIsArray( $taxes );

		// False value.
		$item->set_taxes( false );
		$taxes = $item->get_taxes();
		$this->assertIsArray( $taxes );
	}
}
