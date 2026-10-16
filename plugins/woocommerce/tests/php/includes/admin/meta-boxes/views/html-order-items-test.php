<?php
/**
 * Tests for the order items meta box view.
 *
 * @package WooCommerce\Tests\Admin
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\TaxBasedOn;

/**
 * Tests for the order items meta box view.
 */
class WC_Admin_Meta_Box_Order_Items_View_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Editable price formatting pads currency decimals without rounding stored amounts.
	 *
	 * @testWith ["3.7", 2, ".", "3.70"]
	 *           ["3.7", 2, ",", "3,70"]
	 *           ["3.7001", 2, ".", "3.7001"]
	 *           ["-3.7", 2, ".", "-3.70"]
	 *           ["0", 2, ".", "0.00"]
	 *           ["", 2, ".", ""]
	 *           ["3", 0, ".", "3"]
	 *           ["3.7", 0, ".", "3.7"]
	 *           ["3.7", 3, ".", "3.700"]
	 *           ["1.0E-5", 6, ".", "0.000010"]
	 *           ["-1.0e-7", 2, ",", "-0,00000010"]
	 *           ["1.234567890123456789e-5", 2, ".", "0.00001234567890123456789"]
	 *           ["1.2300e+0", 2, ".", "1.2300"]
	 *           ["1.234e+2", 2, ".", "123.40"]
	 *           ["1e+3", 0, ".", "1000"]
	 *           [".5e-2", 2, ".", "0.005"]
	 *           ["0e-5", 2, ".", "0.00000"]
	 *
	 * @param string $value     Stored amount.
	 * @param int    $decimals  Currency decimals.
	 * @param string $separator Decimal separator.
	 * @param string $expected  Formatted amount.
	 */
	public function test_format_price_for_editing( string $value, int $decimals, string $separator, string $expected ): void {
		update_option( 'woocommerce_price_num_decimals', $decimals );
		update_option( 'woocommerce_price_decimal_sep', $separator );

		$this->assertSame( $expected, WC_Meta_Box_Order_Items::format_price_for_editing( $value ) );
	}

	/**
	 * @testdox Filtered small numeric amounts render without losing precision.
	 *
	 * @testWith ["0.00001", 6, 2, ".", "0.000010"]
	 *           ["0.0000001", 6, 6, ".", "0.00000010"]
	 *           ["-0.0000001", 6, 2, ",", "-0,00000010"]
	 *
	 * @param string $amount    Stored amount.
	 * @param int    $decimals  Currency decimals.
	 * @param int    $precision Internal rounding precision.
	 * @param string $separator Decimal separator.
	 * @param string $expected  Formatted amount.
	 */
	public function test_editable_prices_expand_filtered_floats( string $amount, int $decimals, int $precision, string $separator, string $expected ): void {
		update_option( 'woocommerce_price_num_decimals', $decimals );
		update_option( 'woocommerce_price_decimal_sep', $separator );
		add_filter( 'woocommerce_internal_rounding_precision', static fn() => $precision );
		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_name( 'Sample item' );
		$item->set_subtotal( $amount );
		$item->set_total( $amount );
		$order->add_item( $item );
		$order->save();
		$product_item_id = $item->get_id();
		add_filter( 'woocommerce_order_item_get_total', 'floatval' );
		add_filter( 'woocommerce_order_item_get_subtotal', 'floatval' );

		ob_start();
		include WC_ABSPATH . 'includes/admin/meta-boxes/views/html-order-items.php';
		$xpath = $this->get_xpath_for_html( ob_get_clean() );

		foreach ( array( 'line_total', 'line_subtotal' ) as $field ) {
			$input = $xpath->query( "//input[@name='{$field}[{$product_item_id}]']" )->item( 0 );
			$this->assertSame( $expected, $input->getAttribute( 'value' ), "$field should expand the filtered float without rounding." );
		}
	}

	/**
	 * @testdox Editable order prices retain currency decimals when internal rounding precision is reduced.
	 *
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $filter_numeric_values Whether item filters return floats instead of decimal strings.
	 */
	public function test_editable_prices_retain_currency_decimals( bool $filter_numeric_values ): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_tax_based_on', TaxBasedOn::BASE );
		update_option( 'woocommerce_price_num_decimals', 2 );
		add_filter( 'woocommerce_internal_rounding_precision', static fn() => 2 );
		$rate_id         = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '21.0000',
				'tax_rate_name'     => 'Tax',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_class'    => '',
			)
		);
		$product         = WC_Helper_Product::create_simple_product(
			true,
			array(
				'price'         => '21.30',
				'regular_price' => '21.30',
			)
		);
		$order           = wc_create_order();
		$product_item_id = $order->add_product( $product, 1 );
		$fee             = new WC_Order_Item_Fee();
		$fee->set_name( 'Fee' );
		$fee->set_amount( '10' );
		$fee->set_total( '10' );
		$order->add_item( $fee );
		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_method_id( 'flat_rate' );
		$shipping->set_total( '10' );
		$order->add_item( $shipping );
		$order->calculate_totals();
		$order->save();
		$fee_id      = $fee->get_id();
		$shipping_id = $shipping->get_id();
		if ( $filter_numeric_values ) {
			add_filter( 'woocommerce_order_item_get_total', 'floatval' );
			add_filter( 'woocommerce_order_item_get_subtotal', 'floatval' );
		}

		ob_start();
		include WC_ABSPATH . 'includes/admin/meta-boxes/views/html-order-items.php';
		$xpath = $this->get_xpath_for_html( ob_get_clean() );

		foreach (
			array(
				"line_subtotal[$product_item_id]"        => '17.60',
				"line_total[$product_item_id]"           => '17.60',
				"line_subtotal_tax[$product_item_id][$rate_id]" => '3.70',
				"line_tax[$product_item_id][$rate_id]"   => '3.70',
				"line_total[$fee_id]"                    => '10.00',
				"line_tax[$fee_id][$rate_id]"            => '2.10',
				"shipping_cost[$shipping_id]"            => '10.00',
				"shipping_taxes[$shipping_id][$rate_id]" => '2.10',
			) as $name => $expected
		) {
			$inputs = $xpath->query( "//input[@name='$name']" );
			$this->assertSame( 1, $inputs->length, "Expected the $name input." );
			$this->assertSame( $expected, $inputs->item( 0 )->getAttribute( 'value' ), "$name should retain currency decimals." );
		}

		$item = $order->get_item( $product_item_id );
		$this->assertSame( '17.6', $item->get_total( 'edit' ), 'Formatting must not change the stored item total.' );
		$this->assertSame( '3.7', $item->get_taxes()['total'][ $rate_id ], 'Formatting must not change stored taxes.' );
	}

	/**
	 * @testdox Order item totals pass discount and refund amounts to wc_price as negative amounts.
	 */
	public function test_discount_and_refund_totals_pass_negative_amounts_to_wc_price(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'price'         => 100,
				'regular_price' => 100,
			)
		);
		$order   = wc_create_order();
		$item_id = $order->add_product( $product, 1 );
		$order->set_discount_total( 10 );
		$order->set_total( 90 );
		$order->save();

		wc_create_refund(
			array(
				'amount'        => 25,
				'order_id'      => $order->get_id(),
				'line_items'    => array(
					$item_id => array(
						'qty'          => 1,
						'refund_total' => 25,
						'refund_tax'   => array(),
					),
				),
				'restock_items' => false,
			)
		);
		$order = wc_get_order( $order->get_id() );

		$price_filter = static function ( $price_html, $formatted_price, $args, $unformatted_price, $original_price ) {
			return (float) $original_price < 0 ? 'signed-price:' . $original_price . ':' . $unformatted_price : $price_html;
		};
		add_filter( 'wc_price', $price_filter, 10, 5 );

		ob_start();
		include WC_ABSPATH . 'includes/admin/meta-boxes/views/html-order-items.php';
		$order_items_html = ob_get_clean();

		$xpath          = $this->get_xpath_for_html( $order_items_html );
		$discount_nodes = $xpath->query( "//td[contains(concat(' ', normalize-space(@class), ' '), ' total ') and normalize-space(.) = 'signed-price:-10:-10']" );
		$refund_nodes   = $xpath->query( "//td[contains(concat(' ', normalize-space(@class), ' '), ' total ') and normalize-space(.) = 'signed-price:-25:-25']" );

		$this->assertNotFalse( $discount_nodes, 'The discount price XPath query should be valid.' );
		$this->assertNotFalse( $refund_nodes, 'The refund price XPath query should be valid.' );
		$this->assertSame( 1, $discount_nodes->length, 'The discount row should pass the discount to the wc_price filter as a negative amount.' );
		$this->assertSame( 2, $refund_nodes->length, 'Both refund total cells should pass the refunded total to the wc_price filter as a negative amount.' );
	}

	/**
	 * @testdox The refund form shows a zero refunded total without a minus sign.
	 */
	public function test_refund_form_renders_zero_refunded_total_without_sign(): void {
		$product = WC_Helper_Product::create_simple_product();
		$order   = wc_create_order();
		$order->add_product( $product, 1 );
		$order->calculate_totals();
		$order->save();

		ob_start();
		include WC_ABSPATH . 'includes/admin/meta-boxes/views/html-order-items.php';
		$order_items_html = ob_get_clean();

		$xpath          = $this->get_xpath_for_html( $order_items_html );
		$refunded_nodes = $xpath->query( "//tr[td[contains(concat(' ', normalize-space(@class), ' '), ' label ') and normalize-space(.) = 'Amount already refunded:']]/td[contains(concat(' ', normalize-space(@class), ' '), ' total ')]" );

		$this->assertNotFalse( $refunded_nodes, 'The refunded total XPath query should be valid.' );
		$this->assertSame( 1, $refunded_nodes->length, 'The refund form should show the amount already refunded.' );
		$this->assertSame( '$0.00', trim( $refunded_nodes->item( 0 )->textContent ), 'A zero refunded total should render without a minus sign.' );
	}

	/**
	 * Parses an HTML fragment and returns an XPath object for it.
	 *
	 * @param string $html The HTML fragment.
	 * @return DOMXPath
	 */
	private function get_xpath_for_html( string $html ): DOMXPath {
		$document       = new DOMDocument();
		$previous_state = libxml_use_internal_errors( true );
		$loaded         = $document->loadHTML( '<!DOCTYPE html><html><body>' . $html . '</body></html>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_state );

		$this->assertTrue( $loaded, 'The order items output should be valid enough for DOM parsing.' );

		return new DOMXPath( $document );
	}
}
