<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\Refunds;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\DataUtils;
use WC_Cache_Helper;
use WC_Helper_Product;
use WC_Order;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;
use WC_Tax;
use WC_Unit_Test_Case;

/**
 * DataUtilsTest class.
 */
class DataUtilsTest extends WC_Unit_Test_Case {

	/**
	 * DataUtils instance.
	 *
	 * @var DataUtils
	 */
	private $data_utils;

	/**
	 * Set up tests.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->data_utils = new DataUtils();
	}

	/**
	 * Tear down tests.
	 */
	public function tearDown(): void {
		// Clean up tax rates.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		wp_cache_flush();
		WC_Cache_Helper::invalidate_cache_group( 'taxes' );
		parent::tearDown();
	}

	/**
	 * Test that tax is automatically extracted when not provided.
	 */
	public function test_convert_line_items_extracts_tax_automatically() {
		// Create a tax rate.
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		// Create an order with product and tax.
		$order = $this->create_order_with_taxes( array( $tax_rate_id ), 100.00 );
		$items = $order->get_items( 'line_item' );
		$item  = reset( $items );

		// Line items WITHOUT explicit refund_tax.
		// refund_total 110.00 includes 10% tax.
		$line_items = array(
			array(
				'line_item_id' => $item->get_id(),
				'quantity'     => 1,
				'refund_total' => 110.00,
			),
		);

		// Convert line items.
		$result = $this->data_utils->convert_line_items_to_internal_format( $line_items, $order );

		// Assertions.
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( $item->get_id(), $result );

		// Check that refund_tax was populated.
		$this->assertArrayHasKey( 'refund_tax', $result[ $item->get_id() ] );
		$this->assertNotEmpty( $result[ $item->get_id() ]['refund_tax'] );

		// Tax should be extracted (approximately 10.00 from 110.00 total).
		$this->assertArrayHasKey( $tax_rate_id, $result[ $item->get_id() ]['refund_tax'] );
		$this->assertEqualsWithDelta( 10.0, $result[ $item->get_id() ]['refund_tax'][ $tax_rate_id ], 0.01 );
	}

	/**
	 * Test that explicit refund_tax is preserved and not overridden.
	 */
	public function test_convert_line_items_preserves_explicit_tax() {
		// Create a tax rate.
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		// Create an order with product and tax.
		$order = $this->create_order_with_taxes( array( $tax_rate_id ), 100.00 );
		$items = $order->get_items( 'line_item' );
		$item  = reset( $items );

		// Line items WITH explicit refund_tax (legacy format).
		// Explicit refund_tax value (7.50) should be preserved by the converter.
		$line_items = array(
			array(
				'line_item_id' => $item->get_id(),
				'quantity'     => 1,
				'refund_total' => 50.00,
				'refund_tax'   => array(
					array(
						'id'           => $tax_rate_id,
						'refund_total' => 7.50,
					),
				),
			),
		);

		// Convert line items.
		$result = $this->data_utils->convert_line_items_to_internal_format( $line_items, $order );

		// Assertions.
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( $item->get_id(), $result );

		// Check that explicit refund_tax was preserved.
		$this->assertArrayHasKey( 'refund_tax', $result[ $item->get_id() ] );
		$this->assertArrayHasKey( $tax_rate_id, $result[ $item->get_id() ]['refund_tax'] );

		// Should use the explicit value (7.50), not auto-calculated.
		$this->assertEquals( 7.50, $result[ $item->get_id() ]['refund_tax'][ $tax_rate_id ] );
	}

	/**
	 * Test that tax extraction is skipped for items with zero tax amounts.
	 *
	 * This tests the scenario where a line item (e.g., shipping) has tax rate IDs
	 * in its taxes array but the actual tax amounts are zero. The API should NOT
	 * attempt to extract taxes from refund_total in this case.
	 */
	public function test_convert_line_items_skips_tax_extraction_for_zero_tax_items() {
		// Create a tax rate that applies to products but NOT shipping (tax_rate_shipping => '0').
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '0',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		// Create an order with shipping that has zero tax.
		$order = $this->create_order_with_zero_tax_shipping( $tax_rate_id );

		$shipping_items = $order->get_items( 'shipping' );
		$shipping_item  = reset( $shipping_items );

		// Verify the shipping item has tax IDs but zero amounts (the bug scenario).
		$shipping_taxes = $shipping_item->get_taxes();
		$this->assertArrayHasKey( 'total', $shipping_taxes );
		$this->assertArrayHasKey( $tax_rate_id, $shipping_taxes['total'] );
		$this->assertEquals( 0, (float) $shipping_taxes['total'][ $tax_rate_id ] );

		// Line items WITHOUT explicit refund_tax for shipping.
		// refund_total 10.00 is the shipping cost (no tax included).
		$line_items = array(
			array(
				'line_item_id' => $shipping_item->get_id(),
				'quantity'     => 1,
				'refund_total' => 10.00,
			),
		);

		// Convert line items.
		$result = $this->data_utils->convert_line_items_to_internal_format( $line_items, $order );

		// Assertions.
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( $shipping_item->get_id(), $result );

		// refund_total should remain unchanged (10.00) since there's no tax to extract.
		$this->assertEquals( 10.00, $result[ $shipping_item->get_id() ]['refund_total'] );

		// refund_tax should be empty since the item has zero taxes.
		$this->assertEmpty( $result[ $shipping_item->get_id() ]['refund_tax'] );
	}

	/**
	 * @testdox Should extract a negative tax split when converting a negative-fee line with stored negative tax.
	 *
	 * Regression guard for the creation/preview tax-filter divergence: an earlier
	 * filter rule of `$amount > 0` dropped the negative tax ID for a discount fee,
	 * so the internal format ended up with refund_total = -$11 and refund_tax = [].
	 * The preview path (build_refund_preview) already keeps non-zero taxes; the
	 * create path must agree, otherwise a refund moved from preview to create loses
	 * the signed split.
	 */
	public function test_convert_line_items_extracts_negative_tax_for_negative_fee() {
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '0',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$order = wc_create_order();
		$fee   = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Loyalty discount',
				'total' => -10.00,
			)
		);
		$fee->set_taxes( array( 'total' => array( $tax_rate_id => -1.00 ) ) );
		$fee->save();
		$order->add_item( $fee );

		$tax_item = new \WC_Order_Item_Tax();
		$tax_item->set_rate( $tax_rate_id );
		$tax_item->set_tax_total( -1.00 );
		$tax_item->save();
		$order->add_item( $tax_item );

		$order->save();

		// refund_total -11.00 is the tax-inclusive amount; the converter should
		// split it into a -10.00 base and -1.00 tax for the matching rate ID.
		$line_items = array(
			array(
				'line_item_id' => $fee->get_id(),
				'quantity'     => 1,
				'refund_total' => -11.00,
			),
		);

		$result = $this->data_utils->convert_line_items_to_internal_format( $line_items, $order );

		$this->assertArrayHasKey( $fee->get_id(), $result );
		$this->assertArrayHasKey( 'refund_tax', $result[ $fee->get_id() ] );
		$this->assertArrayHasKey( $tax_rate_id, $result[ $fee->get_id() ]['refund_tax'] );
		$this->assertEqualsWithDelta( -1.00, $result[ $fee->get_id() ]['refund_tax'][ $tax_rate_id ], 0.01 );
		$this->assertEqualsWithDelta( -10.00, $result[ $fee->get_id() ]['refund_total'], 0.01 );

		$order->delete( true );
	}

	/**
	 * Test that calculate_refund_amount handles floating point precision correctly.
	 *
	 * Values like 43.20 + 19.20 can produce 62.400000000000006 in PHP due to IEEE 754
	 * floating point representation. The method should round the result to avoid false
	 * positives in under-refund validation.
	 */
	public function test_calculate_refund_amount_avoids_floating_point_errors() {
		$line_items = array(
			array(
				'line_item_id' => '62',
				'quantity'     => 2,
				'refund_total' => '43.20',
			),
			array(
				'line_item_id' => '63',
				'quantity'     => 1,
				'refund_total' => '19.20',
			),
		);

		$result = $this->data_utils->calculate_refund_amount( $line_items );

		// Without rounding, 43.20 + 19.20 = 62.400000000000006 in PHP.
		// The method should return exactly 62.40.
		$this->assertSame( 62.40, $result );
	}

	/**
	 * Test that calculate_refund_amount includes tax totals.
	 */
	public function test_calculate_refund_amount_includes_tax() {
		$line_items = array(
			array(
				'line_item_id' => '1',
				'quantity'     => 1,
				'refund_total' => '10.00',
				'refund_tax'   => array(
					array(
						'id'           => 1,
						'refund_total' => '1.50',
					),
				),
			),
		);

		$result = $this->data_utils->calculate_refund_amount( $line_items );

		$this->assertSame( 11.50, $result );
	}

	/**
	 * Test that calculate_refund_amount returns null for empty line items.
	 */
	public function test_calculate_refund_amount_returns_null_for_empty() {
		$this->assertNull( $this->data_utils->calculate_refund_amount( array() ) );
	}

	/**
	 * @testdox Should compute line item refund total for a product based on unit price and quantity.
	 */
	public function test_compute_line_item_refund_total_product(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 25.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 4,
				'subtotal' => 100.00,
				'total'    => 100.00,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->save();

		$this->assertSame( 50.00, $this->data_utils->compute_line_item_refund_total( $item, 2 ) );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox Should return error when preview line item quantity exceeds refundable.
	 */
	public function test_validate_preview_line_items_quantity_exceeds_refundable(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 25.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
				'subtotal' => 50.00,
				'total'    => 50.00,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->set_total( 50.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 25.00,
				'line_items' => array(
					$item->get_id() => array(
						'qty'          => 1,
						'refund_total' => 25.00,
						'refund_tax'   => array(),
					),
				),
			)
		);

		$result = $this->data_utils->validate_preview_line_items(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 2,
				),
			),
			$order
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'quantity_exceeds_refundable', $result->get_error_code() );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox Should return error when order is not refundable.
	 */
	public function test_validate_preview_line_items_order_not_refundable(): void {
		$order = $this->create_order_with_taxes( array(), 50.00 );
		$order->set_status( OrderStatus::CANCELLED );
		$order->save();

		$items = $order->get_items( 'line_item' );
		$item  = reset( $items );

		$result = $this->data_utils->validate_preview_line_items(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
				),
			),
			$order
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'order_not_refundable', $result->get_error_code() );
	}

	/**
	 * @testdox Should return 0.0 for product line item with zero original quantity.
	 */
	public function test_compute_line_item_refund_total_zero_original_quantity(): void {
		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'quantity' => 0,
				'subtotal' => 0,
				'total'    => 0,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->save();

		$this->assertSame( 0.0, $this->data_utils->compute_line_item_refund_total( $item, 1 ) );

		$order->delete( true );
	}

	/**
	 * @testdox Should return full item total + tax for shipping items, ignoring quantity.
	 */
	public function test_compute_line_item_refund_total_shipping(): void {
		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_props(
			array(
				'method_title' => 'Flat Rate',
				'total'        => 10.00,
			)
		);
		$shipping->set_taxes( array( 'total' => array( 1 => 1.50 ) ) );
		$shipping->save();

		$this->assertSame( 11.50, $this->data_utils->compute_line_item_refund_total( $shipping, 1 ) );
	}

	/**
	 * @testdox Should return full item total + tax for fee items.
	 */
	public function test_compute_line_item_refund_total_fee_positive(): void {
		$fee = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Handling',
				'total' => 20.00,
			)
		);
		$fee->set_taxes( array( 'total' => array( 1 => 3.00 ) ) );
		$fee->save();

		$this->assertSame( 23.00, $this->data_utils->compute_line_item_refund_total( $fee, 1 ) );
	}

	/**
	 * @testdox Should preserve negative sign for negative-total fee items (discount fees).
	 */
	public function test_compute_line_item_refund_total_fee_negative(): void {
		$fee = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Loyalty discount',
				'total' => -10.00,
			)
		);
		$fee->set_taxes( array( 'total' => array() ) );
		$fee->save();

		$this->assertSame( -10.00, $this->data_utils->compute_line_item_refund_total( $fee, 1 ) );
	}

	/**
	 * @testdox Should throw InvalidArgumentException when quantity is less than 1.
	 *
	 * @dataProvider provider_invalid_quantities_for_compute
	 *
	 * @param int $quantity Quantity to test.
	 */
	public function test_compute_line_item_refund_total_invalid_quantity( int $quantity ): void {
		$fee = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Fee',
				'total' => 5.00,
			)
		);
		$fee->save();

		$this->expectException( \InvalidArgumentException::class );
		$this->data_utils->compute_line_item_refund_total( $fee, $quantity );
	}

	/**
	 * @return array<string, array<int>>
	 */
	public function provider_invalid_quantities_for_compute(): array {
		return array(
			'zero'     => array( 0 ),
			'negative' => array( -1 ),
		);
	}

	/**
	 * @testdox Should populate breakdown.shipping for orders with only shipping line items.
	 */
	public function test_build_refund_preview_shipping_only(): void {
		$order    = wc_create_order();
		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_props(
			array(
				'method_title' => 'Flat Rate',
				'total'        => 10.00,
			)
		);
		$shipping->save();
		$order->add_item( $shipping );
		$order->save();

		$result = $this->data_utils->build_refund_preview(
			$order,
			array(
				array(
					'line_item_id' => $shipping->get_id(),
					'quantity'     => 1,
				),
			)
		);

		$this->assertCount( 1, $result['breakdown']['shipping']['items'] );
		$this->assertSame( array(), $result['breakdown']['products']['items'] );
		$this->assertSame( array(), $result['breakdown']['fees']['items'] );
		$this->assertEquals( '10.00', $result['breakdown']['shipping']['total'] );
		$this->assertEquals( '10.00', $result['total'] );

		$order->delete( true );
	}

	/**
	 * @testdox Should populate breakdown.fees for orders with only fee line items.
	 */
	public function test_build_refund_preview_fee_only(): void {
		$order = wc_create_order();
		$fee   = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Service fee',
				'total' => 20.00,
			)
		);
		$fee->save();
		$order->add_item( $fee );
		$order->save();

		$result = $this->data_utils->build_refund_preview(
			$order,
			array(
				array(
					'line_item_id' => $fee->get_id(),
					'quantity'     => 1,
				),
			)
		);

		$this->assertCount( 1, $result['breakdown']['fees']['items'] );
		$this->assertSame( array(), $result['breakdown']['products']['items'] );
		$this->assertSame( array(), $result['breakdown']['shipping']['items'] );
		$this->assertEquals( '20.00', $result['breakdown']['fees']['total'] );
		$this->assertEquals( '20.00', $result['total'] );

		$order->delete( true );
	}

	/**
	 * @testdox Should aggregate products, shipping, and fees across all three sections in mixed orders.
	 */
	public function test_build_refund_preview_mixed_sections(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 50.00,
				'total'    => 50.00,
			)
		);
		$item->save();
		$order->add_item( $item );

		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_props(
			array(
				'method_title' => 'Flat Rate',
				'total'        => 10.00,
			)
		);
		$shipping->save();
		$order->add_item( $shipping );

		$fee = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Service fee',
				'total' => 5.00,
			)
		);
		$fee->save();
		$order->add_item( $fee );

		$order->save();

		$result = $this->data_utils->build_refund_preview(
			$order,
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
				),
				array(
					'line_item_id' => $shipping->get_id(),
					'quantity'     => 1,
				),
				array(
					'line_item_id' => $fee->get_id(),
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( '50.00', $result['breakdown']['products']['total'] );
		$this->assertEquals( '10.00', $result['breakdown']['shipping']['total'] );
		$this->assertEquals( '5.00', $result['breakdown']['fees']['total'] );
		$this->assertEquals( '65.00', $result['total'] );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox Section totals should equal the sum of item totals at byte-exact precision across many fractional-price items.
	 */
	public function test_build_refund_preview_multi_item_fractional_aggregation(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$order  = wc_create_order();
		$prices = array( 19.99, 7.33, 12.50, 4.99, 0.01 );
		$ids    = array();
		foreach ( $prices as $price ) {
			$item = new WC_Order_Item_Product();
			$item->set_props(
				array(
					'product'  => $product,
					'quantity' => 1,
					'subtotal' => $price,
					'total'    => $price,
				)
			);
			$item->save();
			$order->add_item( $item );
			$ids[] = $item->get_id();
		}
		$order->save();

		$line_items = array_map(
			fn( $id ) => array(
				'line_item_id' => $id,
				'quantity'     => 1,
			),
			$ids
		);
		$result     = $this->data_utils->build_refund_preview( $order, $line_items );

		$item_total_sum = 0.0;
		foreach ( $result['breakdown']['products']['items'] as $i ) {
			$item_total_sum += (float) $i['total'];
		}
		$this->assertEqualsWithDelta(
			(float) $result['breakdown']['products']['total'],
			$item_total_sum,
			0.0001,
			'Section total should equal sum of item totals without drift.'
		);
		$this->assertEquals( '44.82', $result['breakdown']['products']['total'] );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox Should throw InvalidArgumentException when line_item_id does not resolve to an order item.
	 */
	public function test_build_refund_preview_missing_line_item_id(): void {
		$order = wc_create_order();
		$order->save();

		$this->expectException( \InvalidArgumentException::class );
		$this->data_utils->build_refund_preview(
			$order,
			array(
				array(
					'line_item_id' => 999999,
					'quantity'     => 1,
				),
			)
		);

		$order->delete( true );
	}

	/**
	 * @testdox Should return missing_line_items error for empty line_items array.
	 */
	public function test_validate_preview_line_items_empty(): void {
		$order = $this->create_order_with_taxes( array(), 50.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$result = $this->data_utils->validate_preview_line_items( array(), $order );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'missing_line_items', $result->get_error_code() );
	}

	/**
	 * @testdox Should return order_not_refundable when remaining refund amount is zero.
	 */
	public function test_validate_preview_line_items_no_remaining_amount(): void {
		$order = $this->create_order_with_taxes( array(), 50.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$items = $order->get_items( 'line_item' );
		$item  = reset( $items );

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 50.00,
				'line_items' => array(
					$item->get_id() => array(
						'qty'          => 1,
						'refund_total' => 50.00,
						'refund_tax'   => array(),
					),
				),
			)
		);

		$result = $this->data_utils->validate_preview_line_items(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
				),
			),
			$order
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'order_not_refundable', $result->get_error_code() );
	}

	/**
	 * @testdox Should return missing_line_item_id when line_item_id key is absent.
	 */
	public function test_validate_preview_line_items_missing_id(): void {
		$order = $this->create_order_with_taxes( array(), 50.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$result = $this->data_utils->validate_preview_line_items(
			array( array( 'quantity' => 1 ) ),
			$order
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'missing_line_item_id', $result->get_error_code() );
	}

	/**
	 * @testdox Should return line_item_not_found when line_item_id belongs to a different order.
	 */
	public function test_validate_preview_line_items_cross_order_id(): void {
		$order_a = $this->create_order_with_taxes( array(), 50.00 );
		$order_a->set_status( OrderStatus::COMPLETED );
		$order_a->save();
		$order_b = $this->create_order_with_taxes( array(), 50.00 );
		$order_b->set_status( OrderStatus::COMPLETED );
		$order_b->save();
		$order_b_items = $order_b->get_items( 'line_item' );
		$order_b_item  = reset( $order_b_items );

		$result = $this->data_utils->validate_preview_line_items(
			array(
				array(
					'line_item_id' => $order_b_item->get_id(),
					'quantity'     => 1,
				),
			),
			$order_a
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'line_item_not_found', $result->get_error_code() );
	}

	/**
	 * @testdox Should return unsupported_item_type when line_item_id refers to a tax line.
	 */
	public function test_validate_preview_line_items_unsupported_type(): void {
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);
		$order       = $this->create_order_with_taxes( array( $tax_rate_id ), 50.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$tax_items = $order->get_items( 'tax' );
		$tax_item  = reset( $tax_items );

		$result = $this->data_utils->validate_preview_line_items(
			array(
				array(
					'line_item_id' => $tax_item->get_id(),
					'quantity'     => 1,
				),
			),
			$order
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'unsupported_item_type', $result->get_error_code() );
	}

	/**
	 * @testdox Should return invalid_quantity for missing, zero, negative, string, or float quantity values.
	 *
	 * @dataProvider provider_invalid_quantities_for_validate
	 *
	 * @param array<string, mixed> $line_item_overrides Keys to merge into the test line item.
	 */
	public function test_validate_preview_line_items_invalid_quantity( array $line_item_overrides ): void {
		$order = $this->create_order_with_taxes( array(), 50.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$items = $order->get_items( 'line_item' );
		$item  = reset( $items );

		$line_item = array_merge( array( 'line_item_id' => $item->get_id() ), $line_item_overrides );

		$result = $this->data_utils->validate_preview_line_items( array( $line_item ), $order );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_quantity', $result->get_error_code() );
	}

	/**
	 * @return array<string, array<array<string, mixed>>>
	 */
	public function provider_invalid_quantities_for_validate(): array {
		return array(
			'missing key' => array( array() ),
			'zero'        => array( array( 'quantity' => 0 ) ),
			'negative'    => array( array( 'quantity' => -1 ) ),
			'string'      => array( array( 'quantity' => 'abc' ) ),
			'float'       => array( array( 'quantity' => 1.5 ) ),
			'null'        => array( array( 'quantity' => null ) ),
		);
	}

	/**
	 * @testdox Should reject shipping/fee items with quantity other than 1.
	 */
	public function test_validate_preview_line_items_shipping_quantity_must_be_one(): void {
		$order    = wc_create_order();
		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_props(
			array(
				'method_title' => 'Flat Rate',
				'total'        => 10.00,
			)
		);
		$shipping->save();
		$order->add_item( $shipping );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_total( 10.00 );
		$order->save();

		$result = $this->data_utils->validate_preview_line_items(
			array(
				array(
					'line_item_id' => $shipping->get_id(),
					'quantity'     => 2,
				),
			),
			$order
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_quantity', $result->get_error_code() );
	}

	/**
	 * @testdox Should return order_not_refundable when shipping line is fully refunded.
	 *
	 * Once the shipping line is fully refunded the order's remaining refundable
	 * amount drops to zero, so the order-level guard fires before the per-line
	 * `quantity_exceeds_refundable` check is reached.
	 */
	public function test_validate_preview_line_items_shipping_fully_refunded(): void {
		$order    = wc_create_order();
		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_props(
			array(
				'method_title' => 'Flat Rate',
				'total'        => 10.00,
			)
		);
		$shipping->save();
		$order->add_item( $shipping );
		$order->set_total( 10.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 10.00,
				'line_items' => array(
					$shipping->get_id() => array(
						'qty'          => 0,
						'refund_total' => 10.00,
						'refund_tax'   => array(),
					),
				),
			)
		);

		$result = $this->data_utils->validate_preview_line_items(
			array(
				array(
					'line_item_id' => $shipping->get_id(),
					'quantity'     => 1,
				),
			),
			$order
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		// 'order_not_refundable' is returned first because the order's total refundable amount is now zero.
		$this->assertEquals( 'order_not_refundable', $result->get_error_code() );
	}

	/**
	 * @testdox Should return quantity_exceeds_refundable when a partially-refunded shipping line cannot fit a full preview at its original total.
	 *
	 * Order has a $10 shipping line + a $50 product line so the order is still
	 * refundable after a $5 partial shipping refund. Previewing the shipping
	 * line at qty=1 would refund the full $10 — exceeds the $5 remaining on
	 * that line — so validation must reject with `quantity_exceeds_refundable`.
	 * Without the per-line cap, validate would pass and `build_refund_preview`
	 * would return an oversized total.
	 */
	public function test_validate_preview_line_items_shipping_partial_remaining(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 50.00,
				'total'    => 50.00,
			)
		);
		$item->save();
		$order->add_item( $item );

		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_props(
			array(
				'method_title' => 'Flat Rate',
				'total'        => 10.00,
			)
		);
		$shipping->save();
		$order->add_item( $shipping );

		$order->set_total( 60.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		// Pre-refund $5 of the shipping line, leaving $5 remaining on it but
		// keeping the order overall refundable ($55 of $60 remains).
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 5.00,
				'line_items' => array(
					$shipping->get_id() => array(
						'qty'          => 0,
						'refund_total' => 5.00,
						'refund_tax'   => array(),
					),
				),
			)
		);

		$result = $this->data_utils->validate_preview_line_items(
			array(
				array(
					'line_item_id' => $shipping->get_id(),
					'quantity'     => 1,
				),
			),
			$order
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'quantity_exceeds_refundable', $result->get_error_code() );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox Should allow previewing a full shipping refund when the line carries tax and has no prior refund.
	 *
	 * Regression guard: an earlier implementation compared the tax-inclusive
	 * $requested_total (from compute_line_item_refund_total) against a
	 * tax-exclusive $remaining_total (only get_total()). For a $10 shipping line
	 * with $1.50 of tax that produced 11.50 > 10.00 → wrongly rejected.
	 */
	public function test_validate_preview_line_items_shipping_with_tax_allows_full_refund(): void {
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '15.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 50.00,
				'total'    => 50.00,
			)
		);
		$item->save();
		$order->add_item( $item );

		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_props(
			array(
				'method_title' => 'Flat Rate',
				'total'        => 10.00,
			)
		);
		$shipping->set_taxes( array( 'total' => array( $tax_rate_id => 1.50 ) ) );
		$shipping->save();
		$order->add_item( $shipping );

		$tax_item = new \WC_Order_Item_Tax();
		$tax_item->set_rate( $tax_rate_id );
		$tax_item->set_shipping_tax_total( 1.50 );
		$tax_item->save();
		$order->add_item( $tax_item );

		$order->set_total( 61.50 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$result = $this->data_utils->validate_preview_line_items(
			array(
				array(
					'line_item_id' => $shipping->get_id(),
					'quantity'     => 1,
				),
			),
			$order
		);

		$this->assertTrue( $result, 'Full shipping refund covering line total + tax with no prior refund should pass validation.' );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox build_refund_preview preserves the negative tax split on a fee with a negative stored tax.
	 *
	 * Regression guard: a previous implementation filtered tax IDs by `amount > 0`,
	 * which dropped negative tax entries entirely and emitted `tax: 0.00` on
	 * negative-fee discount lines. The fix keeps any non-zero stored tax so the
	 * preview returns the signed split.
	 */
	public function test_build_refund_preview_negative_fee_with_negative_tax(): void {
		// A 10% rate is needed so WC_Tax::calc_inclusive_tax can split a tax-inclusive total.
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '0',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$order = wc_create_order();
		$fee   = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Loyalty discount',
				'total' => -10.00,
			)
		);
		$fee->set_taxes( array( 'total' => array( $tax_rate_id => -1.00 ) ) );
		$fee->save();
		$order->add_item( $fee );

		$tax_item = new \WC_Order_Item_Tax();
		$tax_item->set_rate( $tax_rate_id );
		$tax_item->set_tax_total( -1.00 );
		$tax_item->save();
		$order->add_item( $tax_item );

		$order->save();

		$result = $this->data_utils->build_refund_preview(
			$order,
			array(
				array(
					'line_item_id' => $fee->get_id(),
					'quantity'     => 1,
				),
			)
		);

		// Total stays at the tax-inclusive -$11. The split between subtotal
		// (-$10) and tax (-$1) must be preserved on the fee item entry.
		$this->assertSame( '-11.00', $result['breakdown']['fees']['total'] );
		$this->assertCount( 1, $result['breakdown']['fees']['items'] );
		$this->assertEquals( '-10.00', $result['breakdown']['fees']['items'][0]['subtotal'] );
		$this->assertEquals( '-1.00', $result['breakdown']['fees']['items'][0]['tax'] );
		$this->assertEquals( '-11.00', $result['breakdown']['fees']['items'][0]['total'] );

		$order->delete( true );
	}

	/**
	 * @testdox Should allow validating a negative-total fee (discount fee) that has no prior refund.
	 */
	public function test_validate_preview_line_items_negative_fee_passes(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 50.00,
				'total'    => 50.00,
			)
		);
		$item->save();
		$order->add_item( $item );

		$fee = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Discount',
				'total' => -10.00,
			)
		);
		$fee->save();
		$order->add_item( $fee );

		$order->set_total( 40.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$result = $this->data_utils->validate_preview_line_items(
			array(
				array(
					'line_item_id' => $fee->get_id(),
					'quantity'     => 1,
				),
			),
			$order
		);

		$this->assertTrue( $result, 'Negative-total fee with no prior refund should pass validation.' );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox Should build refund preview with correct tax extraction.
	 */
	public function test_build_refund_preview_with_tax(): void {
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$order = $this->create_order_with_taxes( array( $tax_rate_id ), 100.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$items  = $order->get_items( 'line_item' );
		$item   = reset( $items );
		$result = $this->data_utils->build_refund_preview(
			$order,
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( '100.00', $result['subtotal'] );
		$this->assertEquals( '10.00', $result['tax'] );
		$this->assertEquals( '110.00', $result['total'] );
		$this->assertArrayHasKey( 'breakdown', $result );
		$this->assertArrayHasKey( 'max_refundable', $result );
		$this->assertCount( 1, $result['breakdown']['products']['items'] );
		$this->assertArrayHasKey( 'name', $result['breakdown']['products']['items'][0] );
		$this->assertArrayHasKey( 'product_id', $result['breakdown']['products']['items'][0] );
		$this->assertArrayHasKey( 'subtotal', $result['breakdown']['products']['items'][0] );
		$this->assertArrayHasKey( 'tax', $result['breakdown']['products']['items'][0] );
		$this->assertArrayHasKey( 'total', $result['breakdown']['products']['items'][0] );
		$this->assertEquals( '100.00', $result['breakdown']['products']['items'][0]['subtotal'] );
		$this->assertEquals( '10.00', $result['breakdown']['products']['items'][0]['tax'] );
		$this->assertEquals( '110.00', $result['breakdown']['products']['items'][0]['total'] );
		$this->assertArrayHasKey( 'subtotal', $result['breakdown']['products'] );
		$this->assertArrayHasKey( 'tax', $result['breakdown']['products'] );
		$this->assertArrayHasKey( 'total', $result['breakdown']['products'] );
		$this->assertEquals( '100.00', $result['breakdown']['products']['subtotal'] );
		$this->assertEquals( '10.00', $result['breakdown']['products']['tax'] );
		$this->assertEquals( '110.00', $result['breakdown']['products']['total'] );
	}

	/**
	 * @testdox build_refund_preview should set product_id to the variation ID for variation line items.
	 */
	public function test_build_refund_preview_product_id_is_variation_id_for_variations(): void {
		$variable_product = WC_Helper_Product::create_variation_product();
		$variation_ids    = $variable_product->get_children();
		$this->assertNotEmpty( $variation_ids, 'Variable product fixture should expose at least one variation.' );
		$variation_id = (int) $variation_ids[0];

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product_id'   => $variable_product->get_id(),
				'variation_id' => $variation_id,
				'quantity'     => 1,
				'subtotal'     => 10.00,
				'total'        => 10.00,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->save();

		$result = $this->data_utils->build_refund_preview(
			$order,
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
				),
			)
		);

		$product_item = $result['breakdown']['products']['items'][0];
		$this->assertArrayHasKey( 'product_id', $product_item );
		$this->assertArrayNotHasKey( 'variation_id', $product_item );
		$this->assertSame( $variation_id, $product_item['product_id'] );

		$variable_product->delete( true );
		$order->delete( true );
	}

	/**
	 * Helper: Create an order with shipping that has tax rate IDs but zero tax amounts.
	 *
	 * This simulates the scenario where a tax rate exists but doesn't apply to shipping.
	 *
	 * @param int $tax_rate_id Tax rate ID.
	 * @return WC_Order Order with zero-tax shipping.
	 */
	private function create_order_with_zero_tax_shipping( int $tax_rate_id ): WC_Order {
		// Enable tax calculations.
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );

		// Create an order.
		$order = wc_create_order();

		// Add a shipping item with zero taxes but tax rate IDs present.
		$shipping_item = new \WC_Order_Item_Shipping();
		$shipping_item->set_method_title( 'Flat Rate' );
		$shipping_item->set_method_id( 'flat_rate' );
		$shipping_item->set_total( 10.00 );
		// Set taxes with the tax rate ID but zero amount (this is the bug scenario).
		$shipping_item->set_taxes(
			array(
				'total' => array( $tax_rate_id => '0' ),
			)
		);
		$shipping_item->save();
		$order->add_item( $shipping_item );

		// Add a tax item to the order (for the tax rate to be recognized).
		$tax_item = new \WC_Order_Item_Tax();
		$tax_item->set_rate( $tax_rate_id );
		$tax_item->set_order_id( $order->get_id() );
		$tax_item->set_tax_total( 0 );
		// Product tax would be here, but we're focusing on shipping.
		$tax_item->set_shipping_tax_total( 0 );
		$tax_item->save();
		$order->add_item( $tax_item );

		// Set billing address.
		$order->set_billing_country( 'US' );
		$order->set_billing_state( '' );

		// Save order.
		$order->calculate_totals( false );
		$order->save();

		return $order;
	}

	/**
	 * Helper: Create an order with taxes applied.
	 *
	 * @param array $tax_rate_ids Tax rate IDs to apply.
	 * @param float $product_price Product price.
	 * @return WC_Order Order with taxes.
	 */
	private function create_order_with_taxes( array $tax_rate_ids, float $product_price = 100.00 ): WC_Order {
		// Enable tax calculations.
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );

		// Create a product.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( $product_price );
		$product->set_tax_status( 'taxable' );
		$product->set_tax_class( '' );
		$product->save();

		// Create an order.
		$order = wc_create_order();

		// Add product to order.
		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => $product_price,
				'total'    => $product_price,
			)
		);
		$item->save();
		$order->add_item( $item );

		// Set billing address for tax calculation.
		$order->set_billing_country( 'US' );
		$order->set_billing_state( '' );

		// Manually add tax items to the order (since calculate_taxes might not work in test environment).
		foreach ( $tax_rate_ids as $tax_rate_id ) {
			$tax_item = new \WC_Order_Item_Tax();
			$tax_item->set_rate( $tax_rate_id );
			$tax_item->set_order_id( $order->get_id() );

			// Calculate tax amount based on rate.
			$rate_percent = WC_Tax::get_rate_percent_value( $tax_rate_id );
			$tax_amount   = ( $product_price * $rate_percent ) / 100;

			$tax_item->set_tax_total( $tax_amount );
			$tax_item->set_shipping_tax_total( 0 );
			$tax_item->save();

			$order->add_item( $tax_item );

			// Also set taxes on the line item.
			$item->set_taxes(
				array(
					'total'    => array( $tax_rate_id => $tax_amount ),
					'subtotal' => array( $tax_rate_id => $tax_amount ),
				)
			);
			$item->save();
		}

		// Save and recalculate.
		$order->calculate_totals( false );
		$order->save();

		return $order;
	}
}
