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
	 * @testdox Order item totals pass discount and refund amounts to wc_price as negative values.
	 */
	public function test_discount_and_refund_totals_are_passed_to_wc_price_as_negative_values(): void {
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
			unset( $formatted_price, $args, $unformatted_price );

			return 0 > $original_price ? 'localized-negative-price' : $price_html;
		};
		add_filter( 'wc_price', $price_filter, 10, 5 );

		ob_start();
		include WC_ABSPATH . 'includes/admin/meta-boxes/views/html-order-items.php';
		$order_items_html = ob_get_clean();

		remove_filter( 'wc_price', $price_filter );
		$order->delete( true );
		$product->delete( true );

		$this->assertMatchesRegularExpression(
			'/<td class="label">Discount:<\/td>.*?<td class="total">\s*localized-negative-price\s*<\/td>/s',
			$order_items_html,
			'The wc_price filter should receive a negative order discount amount.'
		);
		$this->assertMatchesRegularExpression(
			'/<td class="label refunded-total">Refunded:<\/td>.*?<td class="total refunded-total">localized-negative-price<\/td>/s',
			$order_items_html,
			'The wc_price filter should receive a negative refunded total.'
		);
		$this->assertMatchesRegularExpression(
			'/<td class="label">Amount already refunded:<\/td>\s*<td class="total">localized-negative-price<\/td>/s',
			$order_items_html,
			'The wc_price filter should receive a negative amount already refunded.'
		);
	}
}
