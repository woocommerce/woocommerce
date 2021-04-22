<?php
/**
 * Tests for WC_Comments class.
 */
class WC_Comments_Tests extends \WC_Unit_Test_Case {
	/**
	 * Test get_review_counts_for_product_ids().
	 */
	public function test_get_review_counts_for_product_ids() {
		$product1 = WC_Helper_Product::create_simple_product();
		$product2 = WC_Helper_Product::create_simple_product();
		$product3 = WC_Helper_Product::create_simple_product();

		$expected_review_count = array(
			$product1->get_id() => 0,
			$product2->get_id() => 0,
			$product3->get_id() => 0,
		);
		$product_id_array = array_keys( $expected_review_count );

		$this->assertEquals( $expected_review_count, WC_Comments::get_review_counts_for_product_ids( $product_id_array ) );

		\Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_product_review( $product2->get_id() );

		\Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_product_review( $product3->get_id() );
		\Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_product_review( $product3->get_id() );

		$expected_review_count = array(
			$product1->get_id() => 0,
			$product2->get_id() => 1,
			$product3->get_id() => 2,
		);
		$this->assertEquals( $expected_review_count, WC_Comments::get_review_counts_for_product_ids( $product_id_array ) );
	}

	/**
	 * Test get_review_count_for_product.
	 */
	public function test_get_review_count_for_product() {
		$product = WC_Helper_Product::create_simple_product();
		$this->assertEquals( 0, WC_Comments::get_review_count_for_product( $product ) );

		\Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_product_review( $product->get_id() );
		$this->assertEquals( 1, WC_Comments::get_review_count_for_product( $product ) );

		\Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_product_review( $product->get_id() );
		$this->assertEquals( 2, WC_Comments::get_review_count_for_product( $product ) );
	}
}
