<?php
/**
 * Tests for the order customer details template.
 */

declare( strict_types = 1 );

/**
 * Order customer details template test.
 */
class WC_Order_Details_Customer_Template_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox The billing email reads as entered after wp_kses_post() and wptexturize() run over the template output.
	 */
	public function test_billing_email_survives_wptexturize(): void {
		$order = WC_Helper_Order::create_order();
		$order->set_billing_email( "o'neil--shop@48x17.com" );
		$order->save();

		$html = wc_get_template_html( 'order/order-details-customer.php', array( 'order' => $order ) );
		$text = html_entity_decode( wp_strip_all_tags( wptexturize( wp_kses_post( $html ) ) ), ENT_QUOTES | ENT_HTML5 );

		$this->assertStringContainsString( "o'neil--shop@48x17.com", $text, 'The billing email should not be texturized.' );
	}
}
