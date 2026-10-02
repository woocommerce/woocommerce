<?php
/**
 * Tests for the order items meta box view.
 *
 * @package WooCommerce\Tests\Admin
 */

declare( strict_types = 1 );

/**
 * Tests for the order items meta box view.
 */
class WC_Admin_Meta_Box_Order_Items_View_Test extends WC_Unit_Test_Case {

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

		remove_filter( 'wc_price', $price_filter );
		$order->delete( true );
		$product->delete( true );

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

		$order->delete( true );
		$product->delete( true );

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
