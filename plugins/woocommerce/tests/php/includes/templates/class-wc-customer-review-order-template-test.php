<?php
/**
 * Tests for the Review Order templates.
 */

declare( strict_types = 1 );

/**
 * Review Order templates test.
 */
class WC_Customer_Review_Order_Template_Test extends WC_Unit_Test_Case {

	private const EMAIL = "o'neil--shop@48x17.com";

	/**
	 * @testdox The billing email in the meta line reads as entered after wptexturize() runs over the template output.
	 * @testWith ["order/customer-review-order.php", "woocommerce-review-order__form"]
	 *           ["order/customer-review-order-empty.php", "woocommerce-review-order--empty"]
	 *
	 * @param string $template     Template path relative to the templates directory.
	 * @param string $marker_class Class only that template prints.
	 */
	public function test_meta_line_email_survives_wptexturize( string $template, string $marker_class ): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_reviews_allowed( true );
		$product->save();

		$order = WC_Helper_Order::create_order( 0, $product );
		$order->set_billing_email( self::EMAIL );
		$order->save();

		$html = wc_get_template_html(
			$template,
			array(
				'order'          => $order,
				'reviewed_count' => 0,
			)
		);
		$text = html_entity_decode( wp_strip_all_tags( wptexturize( $html ) ), ENT_QUOTES | ENT_HTML5 );

		$this->assertStringContainsString( $marker_class, $html, "{$template} should render its own markup." );
		$this->assertStringContainsString( self::EMAIL, $text, 'The billing email should not be texturized.' );
	}
}
