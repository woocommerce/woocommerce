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
	 * @testdox The "Paid" totals section is shown for custom paid statuses and can be hidden with a filter.
	 */
	public function test_paid_totals_render_for_custom_paid_status_and_can_be_filtered(): void {
		add_filter(
			'wc_order_statuses',
			static function ( $statuses ) {
				$statuses['wc-ready-for-pickup'] = 'Ready for pickup';
				return $statuses;
			}
		);
		add_filter(
			'woocommerce_order_is_paid_statuses',
			static function ( $statuses ) {
				$statuses[] = 'ready-for-pickup';
				return $statuses;
			}
		);

		$order = wc_create_order();
		$order->set_status( 'ready-for-pickup' );
		$order->set_date_paid( time() );
		$order->save();

		$render_order_items = function () use ( $order ): string {
			ob_start();
			include WC_ABSPATH . 'includes/admin/meta-boxes/views/html-order-items.php';
			return (string) ob_get_clean();
		};

		$this->assertStringContainsString( 'Paid:', $render_order_items(), 'The "Paid" totals section should show for a custom paid status.' );

		add_filter( 'woocommerce_admin_order_should_render_paid_totals', '__return_false' );

		$this->assertStringNotContainsString( 'Paid:', $render_order_items(), 'The filter should be able to hide the "Paid" totals section.' );
	}

	/**
	 * @testdox The refund quantity input's max excludes units that were already refunded.
	 */
	public function test_refund_quantity_max_excludes_refunded_units(): void {
		$order   = wc_create_order();
		$item_id = $order->add_product( WC_Helper_Product::create_simple_product(), 3 );
		$order->calculate_totals();
		$order->save();

		wc_create_refund(
			array(
				'amount'     => 10,
				'order_id'   => $order->get_id(),
				'line_items' => array(
					$item_id => array(
						'qty'          => 1,
						'refund_total' => 10,
					),
				),
			)
		);
		$order = wc_get_order( $order->get_id() );

		ob_start();
		include WC_ABSPATH . 'includes/admin/meta-boxes/views/html-order-items.php';
		$order_items_html = ob_get_clean();

		$qty_inputs = $this->get_xpath_for_html( $order_items_html )->query( "//input[@name='refund_order_item_qty[{$item_id}]']" );

		$this->assertSame( 1, $qty_inputs->length, 'The line item should have one refund quantity input.' );
		$this->assertSame( '2', $qty_inputs->item( 0 )->getAttribute( 'max' ), 'The max should be the 3 ordered units less the 1 already refunded.' );
	}

	/**
	 * @testdox The refund quantity input's max keeps fractional remainders when stock amounts allow decimals.
	 */
	public function test_refund_quantity_max_keeps_fractional_remainder(): void {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );

		try {
			$order   = wc_create_order();
			$item_id = $order->add_product( WC_Helper_Product::create_simple_product(), 2.3 );
			$order->calculate_totals();
			$order->save();

			wc_create_refund(
				array(
					'amount'     => 1,
					'order_id'   => $order->get_id(),
					'line_items' => array(
						$item_id => array(
							'qty'          => 0.1,
							'refund_total' => 1,
						),
					),
				)
			);
			$order = wc_get_order( $order->get_id() );

			ob_start();
			include WC_ABSPATH . 'includes/admin/meta-boxes/views/html-order-items.php';
			$order_items_html = ob_get_clean();
		} finally {
			remove_filter( 'woocommerce_stock_amount', 'floatval' );
			add_filter( 'woocommerce_stock_amount', 'intval' );
		}

		$qty_inputs = $this->get_xpath_for_html( $order_items_html )->query( "//input[@name='refund_order_item_qty[{$item_id}]']" );

		$this->assertSame( 1, $qty_inputs->length, 'The line item should have one refund quantity input.' );
		$this->assertSame( '2.2', $qty_inputs->item( 0 )->getAttribute( 'max' ), 'The max should be the 2.3 ordered units less the 0.1 already refunded.' );
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
