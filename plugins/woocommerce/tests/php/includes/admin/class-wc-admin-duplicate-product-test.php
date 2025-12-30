<?php
declare( strict_types = 1 );

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

	/**
	 * Tests that duplicating a product correctly handles trashed products with indexed SKUs.
	 *
	 * When duplicating a product with SKU 'woo-cap', if there's a trashed product
	 * with SKU 'woo-cap-1', the duplicate should get SKU 'woo-cap-2' instead of throwing an error.
	 */
	public function test_duplicate_product_skips_trashed_products_with_indexed_sku() {
		// Create a product with SKU 'woo-cap'.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_sku( 'woo-cap' );
		$product->save();

		// Create a duplicate and trash it.
		$first_duplicate = ( new WC_Admin_Duplicate_Product() )->product_duplicate( $product );
		$first_duplicate->save();
		$first_duplicate->delete(); // Move to trash.

		// Verify the trashed product is in trash.
		$this->assertEquals( 'trash', $first_duplicate->get_status() );

		// Duplicate the original product.
		$second_duplicate = ( new WC_Admin_Duplicate_Product() )->product_duplicate( $product );

		// Verify the duplicate has SKU 'woo-cap-2' (skipping 'woo-cap-1' which is trashed).
		$this->assertEquals( 'woo-cap-2', $second_duplicate->get_sku() );
		$this->assertNotEquals( $product->get_id(), $second_duplicate->get_id() );
	}
}
