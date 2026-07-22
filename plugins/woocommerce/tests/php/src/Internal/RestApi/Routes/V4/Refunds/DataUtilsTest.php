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
	 * @testdox calculate_refund_amount treats explicit refund_total: 0 as a valid zero contribution, not as missing.
	 *
	 * Regression guard: a previous implementation used `!empty($line_item['refund_total'])`
	 * which is `true` for `0` / `0.0` / `"0"`. A mixed request like
	 * `[{refund_total: 50}, {refund_total: 0}]` therefore summed to 50 with the
	 * second line silently absent. The current implementation uses `isset() && is_numeric()`,
	 * which preserves the explicit-zero contract documented in the schema.
	 */
	public function test_calculate_refund_amount_includes_explicit_zero(): void {
		$line_items = array(
			array(
				'line_item_id' => 1,
				'quantity'     => 1,
				'refund_total' => 50.00,
			),
			array(
				'line_item_id' => 2,
				'quantity'     => 1,
				'refund_total' => 0,
			),
		);

		$result = $this->data_utils->calculate_refund_amount( $line_items );

		$this->assertSame( 50.0, $result, 'Explicit-zero line contributes 0; total stays 50.' );
	}

	/**
	 * @testdox convert_line_items_to_internal_format accepts the legacy v3-style shape (refund_total without quantity) and records qty=0.
	 */
	public function test_convert_line_items_legacy_no_quantity_defaults_qty_zero(): void {
		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'quantity' => 2,
				'subtotal' => 100.00,
				'total'    => 100.00,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->save();

		$result = $this->data_utils->convert_line_items_to_internal_format(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'refund_total' => 30.00,
				),
			),
			$order
		);

		$this->assertArrayHasKey( $item->get_id(), $result, 'Line item must be attached, not silently dropped.' );
		$this->assertSame( 0, $result[ $item->get_id() ]['qty'] );
		$this->assertSame( 30.00, $result[ $item->get_id() ]['refund_total'] );

		$order->delete( true );
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
	 * @testdox validate_line_items rejects an order whose status is not refundable, mirroring the preview path.
	 *
	 * @dataProvider provider_non_refundable_statuses
	 *
	 * @param string $status Non-refundable order status.
	 */
	public function test_validate_line_items_order_not_refundable( string $status ): void {
		$order = $this->create_order_with_taxes( array(), 50.00 );
		$order->set_status( $status );
		$order->save();

		$items = $order->get_items( 'line_item' );
		$item  = reset( $items );

		$result = $this->data_utils->validate_line_items(
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
	 * @return array<string, array<int, string>>
	 */
	public function provider_non_refundable_statuses(): array {
		return array(
			'cancelled' => array( OrderStatus::CANCELLED ),
			'pending'   => array( OrderStatus::PENDING ),
			'failed'    => array( OrderStatus::FAILED ),
			'refunded'  => array( OrderStatus::REFUNDED ),
		);
	}

	/**
	 * @testdox validate_line_items rejects an explicit refund_total of zero, matching the preview path.
	 *
	 * @dataProvider provider_zero_refund_totals
	 *
	 * @param mixed $refund_total The zero-equivalent refund_total to test.
	 */
	public function test_validate_line_items_rejects_zero_refund_total( $refund_total ): void {
		$order = $this->create_order_with_taxes( array(), 50.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$items = $order->get_items( 'line_item' );
		$item  = reset( $items );

		$result = $this->data_utils->validate_line_items(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'refund_total' => $refund_total,
				),
			),
			$order
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_refund_total', $result->get_error_code() );
	}

	/**
	 * @return array<string, array<int, mixed>>
	 */
	public function provider_zero_refund_totals(): array {
		return array(
			'int zero'       => array( 0 ),
			'float zero'     => array( 0.0 ),
			'rounds to zero' => array( 0.001 ),
		);
	}

	/**
	 * @testdox validate_line_items caps explicit refund_tax against the remaining per-tax-id amount, not the original line tax.
	 */
	public function test_validate_line_items_refund_tax_capped_against_remaining_per_tax_id(): void {
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

		// $100 net + $10 tax (rate VAT) = $110 line total.
		$order = $this->create_order_with_taxes( array( $tax_rate_id ), 100.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$items   = $order->get_items( 'line_item' );
		$item    = reset( $items );
		$item_id = $item->get_id();

		// Prior refund consumes $8 of the $10 tax bucket, leaving $2 remaining.
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 88.00,
				'line_items' => array(
					$item_id => array(
						'qty'          => 0,
						'refund_total' => 80.00,
						'refund_tax'   => array( $tax_rate_id => 8.00 ),
					),
				),
			)
		);

		// A second refund claiming $5 of the same tax bucket exceeds the $2 remaining.
		$result = $this->data_utils->validate_line_items(
			array(
				array(
					'line_item_id' => $item_id,
					'refund_total' => 5.00,
					'refund_tax'   => array(
						array(
							'id'           => $tax_rate_id,
							'refund_total' => 5.00,
						),
					),
				),
			),
			$order
		);

		$this->assertInstanceOf( \WP_Error::class, $result, 'Refund tax exceeding the remaining bucket must be rejected.' );
		$this->assertEquals( 'invalid_refund_amount', $result->get_error_code() );
	}

	/**
	 * Build a completed order with a positive product line and a discount fee that carries a
	 * negative stored tax bucket, for the negative-tax refund_tax cap tests.
	 *
	 * @param int $tax_rate_id Tax rate id used for the fee's stored tax bucket.
	 * @return array{0: WC_Order, 1: int} The order and the fee line item id.
	 */
	private function create_order_with_negative_tax_fee( int $tax_rate_id ): array {
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
				'name'  => 'Loyalty discount',
				'total' => -10.00,
			)
		);
		$fee->set_taxes( array( 'total' => array( $tax_rate_id => -1.00 ) ) );
		$fee->save();
		$order->add_item( $fee );

		$order->set_total( 39.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$product->delete( true );

		return array( $order, $fee->get_id() );
	}

	/**
	 * @testdox validate_line_items accepts a partial negative refund_tax within a negative stored tax bucket.
	 */
	public function test_validate_line_items_negative_tax_bucket_partial_refund_passes(): void {
		$tax_rate_id            = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country' => 'US',
				'tax_rate'         => '10.0000',
				'tax_rate_name'    => 'VAT',
				'tax_rate_order'   => '1',
			)
		);
		list( $order, $fee_id ) = $this->create_order_with_negative_tax_fee( $tax_rate_id );

		// Refund half of the -$1.00 tax bucket. Same sign, within the magnitude cap.
		$result = $this->data_utils->validate_line_items(
			array(
				array(
					'line_item_id' => $fee_id,
					'refund_total' => -5.00,
					'refund_tax'   => array(
						array(
							'id'           => $tax_rate_id,
							'refund_total' => -0.50,
						),
					),
				),
			),
			$order
		);

		$this->assertTrue( $result, 'A partial negative refund_tax within the bucket must be accepted.' );

		$order->delete( true );
	}

	/**
	 * @testdox validate_line_items rejects a negative refund_tax that exceeds the negative stored tax bucket magnitude.
	 */
	public function test_validate_line_items_negative_tax_bucket_over_refund_rejected(): void {
		$tax_rate_id            = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country' => 'US',
				'tax_rate'         => '10.0000',
				'tax_rate_name'    => 'VAT',
				'tax_rate_order'   => '1',
			)
		);
		list( $order, $fee_id ) = $this->create_order_with_negative_tax_fee( $tax_rate_id );

		// -$2.00 exceeds the -$1.00 bucket magnitude.
		$result = $this->data_utils->validate_line_items(
			array(
				array(
					'line_item_id' => $fee_id,
					'refund_total' => -5.00,
					'refund_tax'   => array(
						array(
							'id'           => $tax_rate_id,
							'refund_total' => -2.00,
						),
					),
				),
			),
			$order
		);

		$this->assertInstanceOf( \WP_Error::class, $result, 'A negative refund_tax over the bucket magnitude must be rejected.' );
		$this->assertEquals( 'invalid_refund_amount', $result->get_error_code() );

		$order->delete( true );
	}

	/**
	 * @testdox validate_line_items rejects a positive refund_tax against a negative stored tax bucket (wrong sign).
	 */
	public function test_validate_line_items_wrong_sign_tax_refund_rejected(): void {
		$tax_rate_id            = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country' => 'US',
				'tax_rate'         => '10.0000',
				'tax_rate_name'    => 'VAT',
				'tax_rate_order'   => '1',
			)
		);
		list( $order, $fee_id ) = $this->create_order_with_negative_tax_fee( $tax_rate_id );

		$result = $this->data_utils->validate_line_items(
			array(
				array(
					'line_item_id' => $fee_id,
					'refund_total' => -5.00,
					'refund_tax'   => array(
						array(
							'id'           => $tax_rate_id,
							'refund_total' => 0.50,
						),
					),
				),
			),
			$order
		);

		$this->assertInstanceOf( \WP_Error::class, $result, 'A positive refund_tax on a negative bucket must be rejected.' );
		$this->assertEquals( 'invalid_refund_amount', $result->get_error_code() );

		$order->delete( true );
	}

	/**
	 * @testdox validate_line_items caps the gross (refund_total + explicit refund_tax) against the line total.
	 *
	 * With an explicit refund_tax breakdown, refund_total is the tax-exclusive subtotal
	 * and the tax is added on top. A refund_total within the line that pushes the gross
	 * over the line total via refund_tax must be rejected.
	 */
	public function test_validate_line_items_gross_with_explicit_tax_exceeds_line_rejected(): void {
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country' => 'US',
				'tax_rate'         => '10.0000',
				'tax_rate_name'    => 'VAT',
				'tax_rate_order'   => '1',
			)
		);

		// $50 net + $5 tax = $55 tax-inclusive line.
		$order = $this->create_order_with_taxes( array( $tax_rate_id ), 50.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$items = $order->get_items( 'line_item' );
		$item  = reset( $items );

		// Net 51 + tax 5 = gross 56 > 55.
		$result = $this->data_utils->validate_line_items(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'refund_total' => 51.00,
					'refund_tax'   => array(
						array(
							'id'           => $tax_rate_id,
							'refund_total' => 5.00,
						),
					),
				),
			),
			$order
		);

		$this->assertInstanceOf( \WP_Error::class, $result, 'Gross refund over the line total must be rejected.' );
		$this->assertEquals( 'refund_total_exceeds_line', $result->get_error_code() );

		$order->delete( true );
	}

	/**
	 * @testdox validate_line_items accepts a gross (refund_total + explicit refund_tax) equal to the line total.
	 */
	public function test_validate_line_items_gross_with_explicit_tax_within_line_passes(): void {
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country' => 'US',
				'tax_rate'         => '10.0000',
				'tax_rate_name'    => 'VAT',
				'tax_rate_order'   => '1',
			)
		);

		$order = $this->create_order_with_taxes( array( $tax_rate_id ), 50.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$items = $order->get_items( 'line_item' );
		$item  = reset( $items );

		// Net 50 + tax 5 = gross 55 == line total.
		$result = $this->data_utils->validate_line_items(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'refund_total' => 50.00,
					'refund_tax'   => array(
						array(
							'id'           => $tax_rate_id,
							'refund_total' => 5.00,
						),
					),
				),
			),
			$order
		);

		$this->assertTrue( $result, 'A gross equal to the line total must be accepted.' );

		$order->delete( true );
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
	 * @testdox compute_line_item_refund_total returns the same total regardless of the quantity argument for shipping items.
	 *
	 * Behavior lock. Shipping lines refund as a whole; the quantity argument
	 * must not multiply the result. A future refactor that wrongly applied
	 * unit_price * quantity to shipping would fail this assertion.
	 */
	public function test_compute_line_item_refund_total_shipping_ignores_quantity(): void {
		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_props(
			array(
				'method_title' => 'Flat Rate',
				'total'        => 10.00,
			)
		);
		$shipping->set_taxes( array( 'total' => array( 1 => 1.50 ) ) );
		$shipping->save();

		$this->assertSame( 11.50, $this->data_utils->compute_line_item_refund_total( $shipping, 5 ) );
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
	 * @testdox compute_line_item_refund_total returns the same total regardless of the quantity argument for fee items.
	 *
	 * Behavior lock matching the shipping case. Fees refund as a whole; quantity
	 * must not multiply the result.
	 */
	public function test_compute_line_item_refund_total_fee_ignores_quantity(): void {
		$fee = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Handling',
				'total' => 20.00,
			)
		);
		$fee->set_taxes( array( 'total' => array( 1 => 3.00 ) ) );
		$fee->save();

		$this->assertSame( 23.00, $this->data_utils->compute_line_item_refund_total( $fee, 5 ) );
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
		try {
			$this->data_utils->build_refund_preview(
				$order,
				array(
					array(
						'line_item_id' => 999999,
						'quantity'     => 1,
					),
				)
			);
		} finally {
			$order->delete( true );
		}
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
	 * @testdox Should return missing_quantity_or_refund_total when neither a valid quantity nor refund_total is provided.
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
		$this->assertEquals( 'missing_quantity_or_refund_total', $result->get_error_code() );
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
	 * @testdox Should return invalid_refund_total when refund_total is present but not a positive number.
	 *
	 * @dataProvider provider_invalid_refund_totals_for_validate
	 *
	 * @param array<string, mixed> $line_item_overrides Keys to merge into the test line item.
	 */
	public function test_validate_preview_line_items_invalid_refund_total( array $line_item_overrides ): void {
		$order = $this->create_order_with_taxes( array(), 50.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$items = $order->get_items( 'line_item' );
		$item  = reset( $items );

		$line_item = array_merge( array( 'line_item_id' => $item->get_id() ), $line_item_overrides );

		$result = $this->data_utils->validate_preview_line_items( array( $line_item ), $order );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_refund_total', $result->get_error_code() );
	}

	/**
	 * @return array<string, array<array<string, mixed>>>
	 */
	public function provider_invalid_refund_totals_for_validate(): array {
		return array(
			'zero'                   => array( array( 'refund_total' => 0 ) ),
			'zero with quantity'     => array(
				array(
					'quantity'     => 1,
					'refund_total' => 0,
				),
			),
			'negative'               => array( array( 'refund_total' => -5.00 ) ),
			'negative with quantity' => array(
				array(
					'quantity'     => 1,
					'refund_total' => -5.00,
				),
			),
			'non-numeric string'     => array( array( 'refund_total' => 'abc' ) ),
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
	 * @testdox Should cap a quantity preview of a partially-refunded shipping line to its remaining amount.
	 *
	 * Order has a $10 shipping line + a $50 product line so the order is still
	 * refundable after a $5 partial shipping refund. A shipping line carries a
	 * single refundable unit, so previewing it at qty=1 refunds the remainder:
	 * validation passes and the capped computation returns the $5 remaining,
	 * not the original $10 total.
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

		$this->assertTrue( $result );

		$filled = $this->data_utils->fill_missing_refund_totals(
			array(
				array(
					'line_item_id' => $shipping->get_id(),
					'quantity'     => 1,
				),
			),
			$order
		);
		$this->assertEqualsWithDelta(
			5.00,
			$filled[0]['refund_total'],
			0.001,
			'The shipping line refunds its $5 remainder, not the original $10 total'
		);

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
	 * @testdox Preview validates a supplied quantity even when refund_total is also present (matches create).
	 *
	 * Regression guard: preview previously skipped quantity validation whenever a
	 * refund_total was supplied, so { quantity: 2, refund_total: 1 } on a 1-unit
	 * line previewed successfully but failed at create.
	 */
	public function test_validate_preview_line_items_quantity_with_refund_total_still_validated(): void {
		// 1-unit, no-tax product line.
		$order = $this->create_order_with_taxes( array(), 50.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$items = $order->get_items( 'line_item' );
		$item  = reset( $items );

		$result = $this->data_utils->validate_preview_line_items(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 2,
					'refund_total' => 1.00,
				),
			),
			$order
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'quantity_exceeds_refundable', $result->get_error_code() );

		$order->delete( true );
	}

	/**
	 * @testdox Preview accepts a product quantity refund after a prior amount-only refund and caps it to the remaining line amount (matches create).
	 *
	 * An amount-only prior refund leaves all units "available" by count while
	 * consuming line dollars. The quantity form derives its amount server-side,
	 * capped to the remaining line amount, so validation passes and both preview
	 * and create resolve the same capped value — never an over-refund.
	 */
	public function test_validate_preview_line_items_product_quantity_respects_prior_amount_refund(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
				'subtotal' => 200.00,
				'total'    => 200.00,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->set_total( 200.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		// Prior amount-only refund of $150 on the line (no units consumed: qty 0),
		// leaving $50 of line amount but both units still uncounted.
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 150.00,
				'line_items' => array(
					$item->get_id() => array(
						'qty'          => 0,
						'refund_total' => 150.00,
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

		$this->assertTrue( $result );

		$filled = $this->data_utils->fill_missing_refund_totals(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 2,
				),
			),
			$order
		);
		$this->assertEqualsWithDelta(
			50.00,
			$filled[0]['refund_total'],
			0.001,
			'Both units consume the line, so the refund caps to the $50 remaining after the prior $150 amount-only refund'
		);

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox fill_missing_refund_totals clamps a partial quantity to the remaining amount when prior refunds consumed line dollars without units.
	 */
	public function test_fill_missing_refund_totals_clamps_partial_quantity_to_remaining(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
				'subtotal' => 200.00,
				'total'    => 200.00,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->set_total( 200.00 );
		$order->save();

		// Amount-only refund of $150: no units consumed, $50 of line amount left.
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 150.00,
				'line_items' => array(
					$item->get_id() => array(
						'qty'          => 0,
						'refund_total' => 150.00,
						'refund_tax'   => array(),
					),
				),
			)
		);

		$filled = $this->data_utils->fill_missing_refund_totals(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
				),
			),
			$order
		);

		$this->assertEqualsWithDelta(
			50.00,
			$filled[0]['refund_total'],
			0.001,
			'One of two units derives $100, clamped to the $50 remaining on the line'
		);

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox fill_missing_refund_totals refunds the exact remainder when the quantity consumes the line's remaining units after quantity-derived refunds.
	 */
	public function test_fill_missing_refund_totals_full_consumption_returns_remainder(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 12.97375 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 6,
				'subtotal' => 77.8425,
				'total'    => 77.8425,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->set_total( 77.8425 );
		$order->save();

		// Prior qty-2 refund at the unit-derived (rounded-up) 25.95.
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 25.95,
				'line_items' => array(
					$item->get_id() => array(
						'qty'          => 2,
						'refund_total' => 25.95,
						'refund_tax'   => array(),
					),
				),
			)
		);

		$filled = $this->data_utils->fill_missing_refund_totals(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 4,
				),
			),
			$order
		);

		$this->assertEqualsWithDelta(
			51.89,
			$filled[0]['refund_total'],
			0.001,
			'The remaining 4 units refund the 51.89 remainder, not the unit-derived 51.90'
		);

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox fill_missing_refund_totals returns the unclamped amount for a fully-refunded line so validators reject it with the right error.
	 */
	public function test_fill_missing_refund_totals_fully_refunded_line_returns_unclamped(): void {
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
		$order->set_total( 50.00 );
		$order->save();

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

		$filled = $this->data_utils->fill_missing_refund_totals(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
				),
			),
			$order
		);

		$this->assertEqualsWithDelta(
			50.00,
			$filled[0]['refund_total'],
			0.001,
			'Nothing remains: the unclamped unit-derived amount comes back so validation rejects with line_item_already_refunded, not a zero-refund error'
		);

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox fill_missing_refund_totals preserves the line sign when capping a partially-refunded discount fee.
	 */
	public function test_fill_missing_refund_totals_negative_fee_keeps_sign(): void {
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
		$order->save();

		// Prior mixed refund: $10 of the product line and -$4 of the discount, net $6.
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 6.00,
				'line_items' => array(
					$item->get_id() => array(
						'qty'          => 0,
						'refund_total' => 10.00,
						'refund_tax'   => array(),
					),
					$fee->get_id()  => array(
						'qty'          => 1,
						'refund_total' => -4.00,
						'refund_tax'   => array(),
					),
				),
			)
		);

		$filled = $this->data_utils->fill_missing_refund_totals(
			array(
				array(
					'line_item_id' => $fee->get_id(),
					'quantity'     => 1,
				),
			),
			$order
		);

		$this->assertEqualsWithDelta(
			-6.00,
			$filled[0]['refund_total'],
			0.001,
			'The discount fee refunds its -$6 remainder, keeping the negative sign'
		);

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox fill_missing_refund_totals tops up the final unit to the exact remainder when every prior refund matches its quantity-derived amount.
	 */
	public function test_fill_missing_refund_totals_tops_up_final_unit_after_consistent_refunds(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 5.005 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
				'subtotal' => 10.01,
				'total'    => 10.01,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->set_total( 10.01 );
		$order->save();

		// A 10.01 line over 2 units derives 5.00 per unit (5.005 rounds down), so a
		// prior quantity refund stored exactly that value. The shortfall against the
		// line is pure rounding drift.
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 5.00,
				'line_items' => array(
					$item->get_id() => array(
						'qty'          => 1,
						'refund_total' => 5.00,
						'refund_tax'   => array(),
					),
				),
			)
		);

		$filled = $this->data_utils->fill_missing_refund_totals(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
				),
			),
			$order
		);

		$this->assertEqualsWithDelta(
			5.01,
			$filled[0]['refund_total'],
			0.001,
			'The final unit absorbs the drift and refunds the 5.01 remainder, closing the 10.01 line exactly'
		);

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox fill_missing_refund_totals does not pay out a deliberately withheld residue when refunding the final unit.
	 */
	public function test_fill_missing_refund_totals_final_unit_keeps_unit_amount_after_offschedule_refund(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
				'subtotal' => 200.00,
				'total'    => 200.00,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->set_total( 200.00 );
		$order->save();

		// A two-unit, $200 line ($100/unit): one unit was deliberately under-refunded
		// at $50, an off-schedule amount. The withheld $50 must not be silently paid
		// out by the final unit's quantity refund.
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

		$filled = $this->data_utils->fill_missing_refund_totals(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
				),
			),
			$order
		);

		$this->assertEqualsWithDelta(
			100.00,
			$filled[0]['refund_total'],
			0.001,
			'The final unit refunds its $100 value, not the $150 line remainder'
		);

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox Subclasses overriding the public method signatures stay loadable and keep intercepting the controller flow.
	 *
	 * The controller dispatches through these exact methods, so subclass
	 * overrides keep affecting REST behavior, and each method loads its own
	 * refund-history snapshot rather than receiving it as a parameter — a
	 * parent gaining an optional parameter would fatal any subclass still
	 * declaring the old signature. Declaring such a subclass here pins both
	 * guarantees.
	 */
	public function test_original_public_method_signatures_remain_overridable(): void {
		// phpcs:disable Squiz.Commenting.FunctionComment.Missing, Generic.CodeAnalysis.UselessOverridingMethod.Found -- pass-through overrides ARE the fixture: they pin the parent signatures.
		$subclass = new class() extends DataUtils {
			public function validate_line_items( $line_items, WC_Order $order ) {
				return parent::validate_line_items( $line_items, $order );
			}

			public function fill_missing_refund_totals( array $line_items, WC_Order $order ): array {
				return parent::fill_missing_refund_totals( $line_items, $order );
			}

			public function build_refund_preview( WC_Order $order, array $line_items ): array {
				return parent::build_refund_preview( $order, $line_items );
			}

			public function validate_preview_line_items( array $line_items, WC_Order $order ) {
				return parent::validate_preview_line_items( $line_items, $order );
			}
		};
		// phpcs:enable Squiz.Commenting.FunctionComment.Missing, Generic.CodeAnalysis.UselessOverridingMethod.Found

		$this->assertInstanceOf( DataUtils::class, $subclass );
	}

	/**
	 * @testdox Preview accepts an explicit negative refund_total on a discount-fee line (matches create).
	 */
	public function test_validate_preview_line_items_negative_fee_explicit_refund_total_passes(): void {
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
					'refund_total' => -5.00,
				),
			),
			$order
		);

		$this->assertTrue( $result, 'A negative refund_total on a negative line should be accepted.' );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox Preview rejects a positive refund_total against a negative discount-fee line (matches create).
	 */
	public function test_validate_preview_line_items_positive_refund_total_on_negative_line_rejected(): void {
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
					'refund_total' => 5.00,
				),
			),
			$order
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_refund_total', $result->get_error_code() );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox build_refund_preview honors an explicit negative refund_total on a discount-fee line.
	 */
	public function test_build_refund_preview_explicit_negative_refund_total(): void {
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_order'    => '1',
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
					'refund_total' => -5.00,
				),
			)
		);

		// The explicit -$5 is used directly, not recomputed from a (missing) quantity.
		$this->assertSame( '-5.00', $result['breakdown']['fees']['total'] );
		$item_data = $result['breakdown']['fees']['items'][0];
		$this->assertEqualsWithDelta(
			-5.00,
			(float) $item_data['subtotal'] + (float) $item_data['tax'],
			0.0001,
			'Subtotal + tax must reconstitute the requested negative amount.'
		);

		$order->delete( true );
	}

	/**
	 * @testdox validate_line_items rejects missing or non-positive quantity with a clear invalid_line_item error.
	 *
	 * @dataProvider provider_invalid_quantities_for_validate_line_items
	 *
	 * @param mixed $quantity The quantity value to test (or null to omit the key).
	 */
	public function test_validate_line_items_rejects_missing_quantity( $quantity ): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
				'subtotal' => 20.00,
				'total'    => 20.00,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->set_total( 20.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$line_item = array( 'line_item_id' => $item->get_id() );
		if ( null !== $quantity ) {
			$line_item['quantity'] = $quantity;
		}

		$result = $this->data_utils->validate_line_items( array( $line_item ), $order );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'missing_quantity_or_refund_total', $result->get_error_code() );
		$this->assertStringContainsString( 'positive integer', $result->get_error_message() );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @return array<string, array<int, mixed>>
	 */
	public function provider_invalid_quantities_for_validate_line_items(): array {
		return array(
			'missing'  => array( null ),
			'zero'     => array( 0 ),
			'negative' => array( -1 ),
			'string'   => array( '2' ),
			'float'    => array( 1.5 ),
		);
	}

	/**
	 * @testdox validate_line_items accepts missing/zero quantity when refund_total is provided explicitly (legacy v3-style path).
	 *
	 * @dataProvider provider_loose_quantities_with_explicit_refund_total
	 *
	 * @param mixed $quantity The quantity value to test (or null to omit the key).
	 */
	public function test_validate_line_items_accepts_loose_quantity_with_explicit_refund_total( $quantity ): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
				'subtotal' => 20.00,
				'total'    => 20.00,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->set_total( 20.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$line_item = array(
			'line_item_id' => $item->get_id(),
			'refund_total' => 10.00,
		);
		if ( null !== $quantity ) {
			$line_item['quantity'] = $quantity;
		}

		$result = $this->data_utils->validate_line_items( array( $line_item ), $order );

		$this->assertTrue( $result, 'Legacy explicit-refund_total path should accept missing/zero quantity.' );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @return array<string, array<int, mixed>>
	 */
	public function provider_loose_quantities_with_explicit_refund_total(): array {
		return array(
			'missing' => array( null ),
			'zero'    => array( 0 ),
		);
	}

	/**
	 * @testdox validate_line_items rejects a negative or non-integer quantity supplied alongside refund_total.
	 *
	 * A missing or zero quantity is the accepted dollars-only form, but a negative or
	 * fractional quantity would be stored verbatim on the refund line, so it is rejected
	 * — matching the integer/range checks the preview path performs.
	 *
	 * @dataProvider provider_invalid_loose_quantities
	 *
	 * @param mixed $quantity The quantity value to test.
	 */
	public function test_validate_line_items_rejects_invalid_quantity_with_explicit_refund_total( $quantity ): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
				'subtotal' => 20.00,
				'total'    => 20.00,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->set_total( 20.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$result = $this->data_utils->validate_line_items(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'refund_total' => 10.00,
					'quantity'     => $quantity,
				),
			),
			$order
		);

		$this->assertInstanceOf( \WP_Error::class, $result, 'A negative or non-integer quantity must be rejected.' );
		$this->assertEquals( 'invalid_quantity', $result->get_error_code() );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @return array<string, array<int, mixed>>
	 */
	public function provider_invalid_loose_quantities(): array {
		return array(
			'negative'   => array( -1 ),
			'fractional' => array( 1.5 ),
		);
	}

	/**
	 * @testdox fill_missing_refund_totals computes refund_total for a product line item when missing.
	 */
	public function test_fill_missing_refund_totals_product(): void {
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

		$result = $this->data_utils->fill_missing_refund_totals(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 2,
				),
			),
			$order
		);

		$this->assertArrayHasKey( 'refund_total', $result[0] );
		$this->assertSame( 50.00, $result[0]['refund_total'], '2 × $25 unit price = $50' );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox fill_missing_refund_totals treats refund_total: null the same as a missing key (computes it).
	 */
	public function test_fill_missing_refund_totals_treats_null_as_missing(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 15.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
				'subtotal' => 30.00,
				'total'    => 30.00,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->save();

		$result = $this->data_utils->fill_missing_refund_totals(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
					'refund_total' => null,
				),
			),
			$order
		);

		$this->assertArrayHasKey( 'refund_total', $result[0] );
		$this->assertSame( 15.00, $result[0]['refund_total'], 'null should be treated the same as omitted — auto-computed' );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox fill_missing_refund_totals leaves explicit refund_total: 0 untouched.
	 */
	public function test_fill_missing_refund_totals_leaves_explicit_zero_untouched(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 10.00,
				'total'    => 10.00,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->save();

		$result = $this->data_utils->fill_missing_refund_totals(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
					'refund_total' => 0,
				),
			),
			$order
		);

		// normalize_refund_totals() rounds every explicit value to a float, so an
		// explicit 0 is preserved as 0.0 (not replaced by the auto-computed $10).
		$this->assertSame( 0.0, $result[0]['refund_total'], 'Explicit zero must not be replaced by the auto-computed value' );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox fill_missing_refund_totals leaves explicit refund_total untouched.
	 */
	public function test_fill_missing_refund_totals_preserves_explicit(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 10.00,
				'total'    => 10.00,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->save();

		$result = $this->data_utils->fill_missing_refund_totals(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
					'refund_total' => 7.50,
				),
			),
			$order
		);

		$this->assertSame( 7.50, $result[0]['refund_total'], 'Explicit refund_total must not be overwritten' );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @testdox fill_missing_refund_totals leaves the item alone when line_item_id does not resolve.
	 */
	public function test_fill_missing_refund_totals_skips_unknown_item(): void {
		$order = wc_create_order();
		$order->save();

		$result = $this->data_utils->fill_missing_refund_totals(
			array(
				array(
					'line_item_id' => 999999,
					'quantity'     => 1,
				),
			),
			$order
		);

		$this->assertArrayNotHasKey( 'refund_total', $result[0] );

		$order->delete( true );
	}

	/**
	 * @testdox fill_missing_refund_totals skips items with bad or missing quantity.
	 *
	 * @dataProvider provider_bad_quantities_for_fill
	 *
	 * @param mixed $quantity The quantity value to test.
	 */
	public function test_fill_missing_refund_totals_skips_bad_quantity( $quantity ): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();
		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
				'subtotal' => 20.00,
				'total'    => 20.00,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->save();

		$line_item = array( 'line_item_id' => $item->get_id() );
		if ( null !== $quantity ) {
			$line_item['quantity'] = $quantity;
		}

		$result = $this->data_utils->fill_missing_refund_totals( array( $line_item ), $order );

		$this->assertArrayNotHasKey( 'refund_total', $result[0] );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * @return array<string, array<int, mixed>>
	 */
	public function provider_bad_quantities_for_fill(): array {
		return array(
			'missing'  => array( null ),
			'zero'     => array( 0 ),
			'negative' => array( -1 ),
			'string'   => array( 'abc' ),
			'float'    => array( 1.5 ),
		);
	}

	/**
	 * @testdox fill_missing_refund_totals leaves refund_total unset for product items whose source has zero quantity.
	 */
	public function test_fill_missing_refund_totals_skips_zero_source_quantity_product(): void {
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

		$result = $this->data_utils->fill_missing_refund_totals(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
				),
			),
			$order
		);

		$this->assertArrayNotHasKey( 'refund_total', $result[0], 'Helper must leave refund_total unset so validate_line_items can surface a specific error.' );

		$order->delete( true );
	}

	/**
	 * @testdox validate_line_items returns a specific error when refund_total is omitted and source product has zero quantity.
	 */
	public function test_validate_line_items_zero_source_quantity_with_missing_refund_total(): void {
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
		// A non-zero order total keeps the order from looking fully refunded so the
		// zero-source-quantity branch is what surfaces.
		$order->set_total( 10.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$result = $this->data_utils->validate_line_items(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
				),
			),
			$order
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_line_item', $result->get_error_code() );
		$this->assertStringContainsString( 'source quantity is zero', $result->get_error_message() );

		$order->delete( true );
	}

	/**
	 * @testdox fill_missing_refund_totals returns full item total for shipping items, ignoring quantity.
	 */
	public function test_fill_missing_refund_totals_shipping(): void {
		$order    = wc_create_order();
		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_props(
			array(
				'method_title' => 'Flat Rate',
				'total'        => 12.50,
			)
		);
		$shipping->save();
		$order->add_item( $shipping );
		$order->save();

		$result = $this->data_utils->fill_missing_refund_totals(
			array(
				array(
					'line_item_id' => $shipping->get_id(),
					'quantity'     => 1,
				),
			),
			$order
		);

		$this->assertSame( 12.50, $result[0]['refund_total'] );

		$order->delete( true );
	}

	/**
	 * @testdox fill_missing_refund_totals processes a mixed array (some items with, some without refund_total).
	 */
	public function test_fill_missing_refund_totals_mixed(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10.00 );
		$product->save();

		$order  = wc_create_order();
		$item_a = new WC_Order_Item_Product();
		$item_a->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 10.00,
				'total'    => 10.00,
			)
		);
		$item_a->save();
		$order->add_item( $item_a );

		$item_b = new WC_Order_Item_Product();
		$item_b->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 10.00,
				'total'    => 10.00,
			)
		);
		$item_b->save();
		$order->add_item( $item_b );
		$order->save();

		$result = $this->data_utils->fill_missing_refund_totals(
			array(
				array(
					'line_item_id' => $item_a->get_id(),
					'quantity'     => 1,
				),
				// Item A above has no refund_total, expected to be filled with 10.00.
				array(
					'line_item_id' => $item_b->get_id(),
					'quantity'     => 1,
					'refund_total' => 7.0,
				),
				// Item B has explicit refund_total 7.0, expected to be preserved.
			),
			$order
		);

		$this->assertSame( 10.00, $result[0]['refund_total'] );
		$this->assertSame( 7.0, $result[1]['refund_total'] );

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
	 * @testdox normalize_refund_totals rounds a numeric refund_total to currency precision (and coerces ints to float).
	 * @dataProvider provider_normalize_refund_totals_numeric
	 *
	 * @param int|float $input    Provided refund_total.
	 * @param float     $expected Rounded float result.
	 */
	public function test_normalize_refund_totals_rounds_numeric( $input, float $expected ): void {
		$result = $this->data_utils->normalize_refund_totals( array( array( 'refund_total' => $input ) ) );

		$this->assertSame( $expected, $result[0]['refund_total'] );
	}

	/**
	 * @return array<string, array{0: int|float, 1: float}>
	 */
	public function provider_normalize_refund_totals_numeric(): array {
		return array(
			'integer coerced to float' => array( 30, 30.0 ),
			'rounds to two decimals'   => array( 30.999, 31.0 ),
			'explicit zero'            => array( 0, 0.0 ),
		);
	}

	/**
	 * @testdox normalize_refund_totals leaves null, non-numeric, and missing refund_total untouched.
	 */
	public function test_normalize_refund_totals_leaves_non_numeric_untouched(): void {
		$result = $this->data_utils->normalize_refund_totals(
			array(
				array( 'refund_total' => null ),
				array( 'refund_total' => 'abc' ),
				array( 'line_item_id' => 7 ),
			)
		);

		$this->assertNull( $result[0]['refund_total'], 'null means "auto-compute" and must be preserved.' );
		$this->assertSame( 'abc', $result[1]['refund_total'], 'Non-numeric values are left for downstream validation.' );
		$this->assertArrayNotHasKey( 'refund_total', $result[2], 'A missing key stays missing.' );
	}

	/**
	 * @testdox build_refund_preview falls back to zero tax when a line's stored total and tax nearly cancel.
	 *
	 * A line with total 100 and stored tax -99.99 has a near-zero inclusive total; splitting by
	 * the stored ratio would explode the tax. The sanity clamp must fall back to all-net.
	 */
	public function test_build_refund_preview_clamps_degenerate_stored_ratio(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 100.00,
				'total'    => 100.00,
			)
		);
		$item->set_taxes(
			array(
				'total'    => array( 1 => -99.99 ),
				'subtotal' => array( 1 => -99.99 ),
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->set_total( 0.01 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$preview = $this->data_utils->build_refund_preview(
			$order,
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
				),
			)
		);

		$item_data = $preview['breakdown']['products']['items'][0];
		$this->assertEquals( '0.00', $item_data['tax'], 'Degenerate ratio must clamp tax to zero rather than explode.' );
		$this->assertEquals( '0.01', $item_data['subtotal'] );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Invoke the protected split_inclusive_by_stored_ratio() via reflection.
	 *
	 * @param float $amount Tax-inclusive amount to split.
	 * @param mixed $item   Order item supplying the stored total/tax ratio.
	 * @param int   $dp     Price decimal places.
	 * @return array{subtotal: float, total_tax: float, taxes: array<int, float>}
	 */
	private function invoke_split_inclusive( float $amount, $item, int $dp = 2 ): array {
		$method = ( new \ReflectionClass( DataUtils::class ) )->getMethod( 'split_inclusive_by_stored_ratio' );
		$method->setAccessible( true );
		return $method->invoke( $this->data_utils, $amount, $item, $dp );
	}

	/**
	 * @testdox split_inclusive_by_stored_ratio rounds per-tax-id amounts and derives the subtotal as the remainder so the invariant holds.
	 */
	public function test_split_inclusive_two_rate_rounding_remainder(): void {
		$fee = new WC_Order_Item_Fee();
		$fee->set_total( 100.00 );
		$fee->set_taxes(
			array(
				// Two rates: County 1% and State 9%.
				'total' => array(
					1 => 1.00,
					2 => 9.00,
				),
			)
		);

		// Split $33.33 of the $110 tax-inclusive line: forces sub-cent per-id rounding.
		$result = $this->invoke_split_inclusive( 33.33, $fee );

		$this->assertEqualsWithDelta( 0.30, $result['taxes'][1], 0.0001 );
		$this->assertEqualsWithDelta( 2.73, $result['taxes'][2], 0.0001 );
		$this->assertEqualsWithDelta( 3.03, $result['total_tax'], 0.0001 );
		$this->assertEqualsWithDelta( 30.30, $result['subtotal'], 0.0001 );
		// Invariant: subtotal + total_tax reconstitutes the requested amount exactly.
		$this->assertEqualsWithDelta( 33.33, $result['subtotal'] + $result['total_tax'], 0.0001 );
	}

	/**
	 * @testdox split_inclusive_by_stored_ratio preserves negative signs for a discount fee with negative tax.
	 */
	public function test_split_inclusive_negative_discount_fee(): void {
		$fee = new WC_Order_Item_Fee();
		$fee->set_total( -10.00 );
		$fee->set_taxes(
			array(
				'total' => array( 1 => -1.00 ),
			)
		);

		// Refund half of the -$11 tax-inclusive discount line.
		$result = $this->invoke_split_inclusive( -5.50, $fee );

		$this->assertEqualsWithDelta( -0.50, $result['taxes'][1], 0.0001 );
		$this->assertEqualsWithDelta( -0.50, $result['total_tax'], 0.0001 );
		$this->assertEqualsWithDelta( -5.00, $result['subtotal'], 0.0001 );
	}

	/**
	 * @testdox split_inclusive_by_stored_ratio treats a line with no stored tax as fully net.
	 */
	public function test_split_inclusive_zero_tax_line(): void {
		$fee = new WC_Order_Item_Fee();
		$fee->set_total( 50.00 );

		$result = $this->invoke_split_inclusive( 25.00, $fee );

		$this->assertSame( array(), $result['taxes'] );
		$this->assertEqualsWithDelta( 0.0, $result['total_tax'], 0.0001 );
		$this->assertEqualsWithDelta( 25.00, $result['subtotal'], 0.0001 );
	}

	/**
	 * @testdox split_inclusive_by_stored_ratio clamps to net-only when the stored total and tax nearly cancel.
	 */
	public function test_split_inclusive_degenerate_ratio_clamps(): void {
		$fee = new WC_Order_Item_Fee();
		$fee->set_total( 100.00 );
		$fee->set_taxes(
			array(
				'total' => array( 1 => -99.99 ),
			)
		);

		$result = $this->invoke_split_inclusive( 0.01, $fee );

		$this->assertSame( array(), $result['taxes'] );
		$this->assertEqualsWithDelta( 0.0, $result['total_tax'], 0.0001 );
		$this->assertEqualsWithDelta( 0.01, $result['subtotal'], 0.0001 );
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
