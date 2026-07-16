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
	 * @testdox Order item totals pass positive amounts and explicit negative display intent to wc_price.
	 */
	public function test_discount_and_refund_totals_pass_explicit_negative_display_intent_to_wc_price(): void {
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
			unset( $formatted_price );

			return true === ( $args['is_negative'] ?? false ) ? 'localized-negative-price:' . (float) $original_price . ':' . $unformatted_price : $price_html;
		};
		add_filter( 'wc_price', $price_filter, 10, 5 );

		ob_start();
		include WC_ABSPATH . 'includes/admin/meta-boxes/views/html-order-items.php';
		$order_items_html = ob_get_clean();

		remove_filter( 'wc_price', $price_filter );
		$order->delete( true );
		$product->delete( true );

		$document       = new DOMDocument();
		$previous_state = libxml_use_internal_errors( true );
		$loaded         = $document->loadHTML( '<!DOCTYPE html><html><body>' . $order_items_html . '</body></html>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_state );

		$this->assertTrue( $loaded, 'The order items output should be valid enough for DOM parsing.' );

		$xpath          = new DOMXPath( $document );
		$discount_nodes = $xpath->query( "//td[contains(concat(' ', normalize-space(@class), ' '), ' total ') and normalize-space(.) = 'localized-negative-price:10:10']" );
		$refund_nodes   = $xpath->query( "//td[contains(concat(' ', normalize-space(@class), ' '), ' total ') and normalize-space(.) = 'localized-negative-price:25:25']" );

		$this->assertNotFalse( $discount_nodes, 'The discount price XPath query should be valid.' );
		$this->assertNotFalse( $refund_nodes, 'The refund price XPath query should be valid.' );
		$this->assertSame( 1, $discount_nodes->length, 'The order items view should pass the positive discount and explicit negative display intent to the wc_price filter.' );
		$this->assertSame( 2, $refund_nodes->length, 'Both refund total cells should pass the positive amount and explicit negative display intent to the wc_price filter.' );
	}

	/**
	 * @testdox The refund form passes explicit negative display intent before any amount is refunded.
	 */
	public function test_refund_form_passes_negative_display_intent_for_zero_refunded_total(): void {
		$product = WC_Helper_Product::create_simple_product();
		$order   = wc_create_order();
		$order->add_product( $product, 1 );
		$order->calculate_totals();
		$order->save();

		$price_filter = static function ( $price_html, $formatted_price, $args, $unformatted_price, $original_price ) {
			unset( $formatted_price );

			return true === ( $args['is_negative'] ?? false ) ? 'localized-negative-price:' . (float) $original_price . ':' . $unformatted_price : $price_html;
		};
		add_filter( 'wc_price', $price_filter, 10, 5 );

		ob_start();
		include WC_ABSPATH . 'includes/admin/meta-boxes/views/html-order-items.php';
		$order_items_html = ob_get_clean();

		remove_filter( 'wc_price', $price_filter );
		$order->delete( true );
		$product->delete( true );

		$document       = new DOMDocument();
		$previous_state = libxml_use_internal_errors( true );
		$loaded         = $document->loadHTML( '<!DOCTYPE html><html><body>' . $order_items_html . '</body></html>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_state );

		$this->assertTrue( $loaded, 'The order items output should be valid enough for DOM parsing.' );

		$xpath      = new DOMXPath( $document );
		$zero_nodes = $xpath->query( "//div[contains(concat(' ', normalize-space(@class), ' '), ' wc-order-refund-items ')]//td[contains(concat(' ', normalize-space(@class), ' '), ' total ') and normalize-space(.) = 'localized-negative-price:0:0']" );

		$this->assertNotFalse( $zero_nodes, 'The zero refunded total XPath query should be valid.' );
		$this->assertSame( 1, $zero_nodes->length, 'The refund form should pass explicit negative display intent without changing the zero-valued filter arguments.' );
	}
}
