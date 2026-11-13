<?php
declare( strict_types = 1 );

/**
 * Tests for the WC_Product_Simple class.
 */
class WC_Product_Simple_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox add_to_cart_url() builds on the product permalink, not on the current request's URL and query arguments.
	 */
	public function test_add_to_cart_url_ignores_current_request_url(): void {
		$product                = WC_Helper_Product::create_simple_product();
		$_SERVER['REQUEST_URI'] = '/cart/?remove_item=abc123&_wpnonce=0123456789';

		$this->assertSame(
			add_query_arg( 'add-to-cart', $product->get_id(), $product->get_permalink() ),
			$product->add_to_cart_url(),
			'The add to cart URL should not carry over the arguments of the page it is rendered on.'
		);
	}
}
