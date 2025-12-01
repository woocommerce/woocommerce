<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Email;

use Automattic\WooCommerce\Internal\Email\OrderPriceFormatter;
use WC_Helper_Order;
use WC_Helper_Product;
use WC_Order_Item_Product;
use WC_Tax;
use WC_Unit_Test_Case;

/**
 * OrderPriceFormatter test.
 *
 * @covers \Automattic\WooCommerce\Internal\Email\OrderPriceFormatter
 */
class OrderPriceFormatterTest extends WC_Unit_Test_Case {

	/**
	 * @testdox The get_formatted_item_subtotal method returns correctly formatted subtotal for an order item.
	 */
	public function test_get_formatted_item_subtotal_returns_correctly_formatted_subtotal() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '10.00' );
		$product->save();

		$order = WC_Helper_Order::create_order();
		$order->set_prices_include_tax( false );
		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => '10.00',
				'total'    => '10.00',
			)
		);
		$order->add_item( $item );
		$order->save();

		// Test with tax display 'excl' but no tax rate.
		$this->assertEquals(
			wc_price(
				10.00,
				array(
					'ex_tax_label' => 0,
					'currency'     => $order->get_currency(),
				)
			),
			OrderPriceFormatter::get_formatted_item_subtotal( $order, $item, 'excl' )
		);

		// Test with tax display 'incl' but no tax rate.
		$this->assertEquals(
			wc_price( 10.00, array( 'currency' => $order->get_currency() ) ),
			OrderPriceFormatter::get_formatted_item_subtotal( $order, $item, 'incl' )
		);

		// Enable tax as a requirement of the "exlucding tax" label to be shown in `wc_price`.
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Test with prices including tax and 20% tax rate.
		$order->set_prices_include_tax( true );
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'tax',
			'tax_rate_priority' => '1',
			'tax_rate_order'    => '1',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		$item->set_props(
			array(
				'subtotal'     => '10.00',
				'total'        => '10.00',
				'subtotal_tax' => '2.00',
				'total_tax'    => '2.00',
			)
		);
		$order->add_item( $item );
		$order->save();

		// Test with tax display 'excl' and prices including tax.
		$this->assertEquals(
			wc_price(
				10.00,
				array(
					'ex_tax_label' => 1,
					'currency'     => $order->get_currency(),
				)
			),
			OrderPriceFormatter::get_formatted_item_subtotal( $order, $item, 'excl' )
		);

		// Test with tax display 'incl' and prices including tax.
		$this->assertEquals(
			wc_price( 12.00, array( 'currency' => $order->get_currency() ) ),
			OrderPriceFormatter::get_formatted_item_subtotal( $order, $item, 'incl' )
		);
	}
}
