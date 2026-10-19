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
	 * @testdox The billing email and both phones read as entered after wptexturize() runs over the template output.
	 */
	public function test_email_and_phones_survive_wptexturize(): void {
		$order = WC_Helper_Order::create_order();
		$order->set_billing_email( "o'neil--shop@48x17.com" );
		$order->set_billing_phone( '555--0100' );
		$order->set_shipping_address_1( 'WooAddress' );
		$order->set_shipping_city( 'WooCity' );
		$order->set_shipping_postcode( '12345' );
		$order->set_shipping_country( 'US' );
		$order->set_shipping_phone( '555--0200' );
		$order->save();

		$html = wc_get_template_html( 'order/order-details-customer.php', array( 'order' => $order ) );
		$text = html_entity_decode( wp_strip_all_tags( wptexturize( $html ) ), ENT_QUOTES | ENT_HTML5 );

		$this->assertStringContainsString( "o'neil--shop@48x17.com", $text, 'The billing email should not be texturized.' );
		$this->assertStringContainsString( '555--0100', $text, 'The billing phone should not be texturized.' );
		$this->assertStringContainsString( '555--0200', $text, 'The shipping phone should not be texturized.' );
	}
}
