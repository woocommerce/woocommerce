<?php
/**
 * Unit tests for the WC_Admin_Duplicate_Product class.
 *
 * @package WooCommerce\Tests\Admin
 */

/**
 * Class WC_Admin_Duplicate_Product_Test
 */
class WC_Admin_Duplicate_Product_Test extends WC_Unit_Test_Case {
	/**
	 * Tests that the filter will exclude metadata from the duplicate as-expected.
	 */
	public function test_filter_allows_excluding_metadata_from_duplicate() {
		$product = WC_Helper_Product::create_simple_product();
		$product->add_meta_data( 'test_data', 'test' );

		$filter = function ( $exclude_meta, $existing_meta_keys ) {
			$this->assertContains( 'test_data', $existing_meta_keys );
			return array( 'test_data' );
		};
		add_filter( 'woocommerce_duplicate_product_exclude_meta', $filter, 10, 2 );

		$duplicate = ( new WC_Admin_Duplicate_Product() )->product_duplicate( $product );

		remove_filter( 'woocommerce_duplicate_product_exclude_meta', $filter );

		$this->assertNotEquals( $product->get_id(), $duplicate->get_id() );
		$this->assertEmpty( $duplicate->get_meta_data() );
	}
}
