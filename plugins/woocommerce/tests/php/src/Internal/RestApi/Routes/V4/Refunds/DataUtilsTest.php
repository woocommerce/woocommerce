<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\Refunds;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\DataUtils;
use WC_Cache_Helper;
use WC_Helper_Product;
use WC_Order;
use WC_Order_Item_Product;
use WC_Tax;
use WC_Unit_Test_Case;
use ReflectionClass;

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
	 * Test building tax rates array with a single rate.
	 */
	public function test_build_tax_rates_array_with_single_rate() {
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

		// Create an order with the tax rate.
		$order = $this->create_order_with_taxes( array( $tax_rate_id ) );

		// Use reflection to access private method.
		$reflection = new ReflectionClass( $this->data_utils );
		$method     = $reflection->getMethod( 'build_tax_rates_array' );
		$method->setAccessible( true );

		// Call the method.
		$result = $method->invoke( $this->data_utils, $order, array( $tax_rate_id ) );

		// Assertions.
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( $tax_rate_id, $result );
		$this->assertEquals( 10.0, $result[ $tax_rate_id ]['rate'] );
		$this->assertEquals( 'VAT', $result[ $tax_rate_id ]['label'] );
		$this->assertEquals( 'no', $result[ $tax_rate_id ]['compound'] );
	}

	/**
	 * Test building tax rates array with multiple rates.
	 */
	public function test_build_tax_rates_array_with_multiple_rates() {
		// Create two tax rates.
		$tax_rate_id_1 = WC_Tax::_insert_tax_rate(
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

		$tax_rate_id_2 = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '5.0000',
				'tax_rate_name'     => 'Regional',
				'tax_rate_priority' => '2',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '2',
				'tax_rate_class'    => '',
			)
		);

		// Create an order with both tax rates.
		$order = $this->create_order_with_taxes( array( $tax_rate_id_1, $tax_rate_id_2 ) );

		// Use reflection to access private method.
		$reflection = new ReflectionClass( $this->data_utils );
		$method     = $reflection->getMethod( 'build_tax_rates_array' );
		$method->setAccessible( true );

		// Call the method.
		$result = $method->invoke( $this->data_utils, $order, array( $tax_rate_id_1, $tax_rate_id_2 ) );

		// Assertions.
		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );

		$this->assertArrayHasKey( $tax_rate_id_1, $result );
		$this->assertEquals( 10.0, $result[ $tax_rate_id_1 ]['rate'] );
		$this->assertEquals( 'VAT', $result[ $tax_rate_id_1 ]['label'] );

		$this->assertArrayHasKey( $tax_rate_id_2, $result );
		$this->assertEquals( 5.0, $result[ $tax_rate_id_2 ]['rate'] );
		$this->assertEquals( 'Regional', $result[ $tax_rate_id_2 ]['label'] );
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
		$line_items = array(
			array(
				'line_item_id' => $item->get_id(),
				'quantity'     => 1,
				'refund_total' => 110.00, // Includes 10% tax.
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
		$line_items = array(
			array(
				'line_item_id' => $item->get_id(),
				'quantity'     => 1,
				'refund_total' => 50.00,
				'refund_tax'   => array(
					array(
						'id'           => $tax_rate_id,
						'refund_total' => 7.50, // Explicit value.
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
