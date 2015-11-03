<?php

namespace WooCommerce\Tests\API;

/**
 * Class Functions.
 * @package WooCommerce\Tests\API
 * @since 2.4
 */
class Orders extends \WC_API_Unit_Test_Case {

	/**
	 * Test test_wc_api_order_get_variation_id_returns_correct_id.
	 *
	 * @since 2.4
	 */
	public function test_wc_api_order_get_variation_id_returns_correct_id() {
		parent::setUp();
		$product    = \WC_Helper_Product::create_variation_product();
		$orders_api = WC()->api->WC_API_Orders;

		$variation_id = $orders_api->get_variation_id( $product, array( 'size' => 'small' ) );
		$this->assertSame( ( $product->id + 1 ), $variation_id );

		$variation_id = $orders_api->get_variation_id( $product, array( 'size' => 'large' ) );
		$this->assertSame( ( $product->id + 2 ), $variation_id );
	}


}
