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
	 * Don't allow SKUs with numbers lower than 6. Used to hook into `wc_product_pre_has_unique_sku`.
	 *
	 * @param bool|null $has_unique_sku Set to a boolean value to short-circuit the default SKU check.
	 * @param int       $product_id The ID of the current product.
	 * @param string    $sku The SKU to check for uniqueness.
	 * @return bool
	 */
	public function dont_allow_skus_with_numbers_lower_than_6( $has_unique_sku, $product_id, $sku ) {
		if ( preg_match( '/[0-5]/', $sku ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Don't allow SKUs with numbers lower than 8. Used to hook into `wc_product_has_unique_sku`.
	 *
	 * @param bool|null $sku_found Set to a boolean value to short-circuit the default SKU check.
	 * @param int       $product_id The ID of the current product.
	 * @param string    $sku The SKU to check for uniqueness.
	 * @return bool
	 */
	public function dont_allow_skus_with_numbers_lower_than_8( $sku_found, $product_id, $sku ) {
		if ( preg_match( '/[0-7]/', $sku ) ) {
			return true;
		}

		return false;
	}

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
		$first_duplicate->delete();

		// Verify the trashed product is in trash.
		$this->assertEquals( 'trash', $first_duplicate->get_status() );

		// Duplicate the original product.
		$second_duplicate = ( new WC_Admin_Duplicate_Product() )->product_duplicate( $product );

		// Verify the duplicate was created successfully and has SKU 'woo-cap-2'
		// (skipping 'woo-cap-1' which is trashed).
		$this->assertEquals( 'woo-cap-2', $second_duplicate->get_sku() );
		$this->assertNotEquals( $product->get_id(), $second_duplicate->get_id() );

		// Verify the filter 'wc_product_pre_has_unique_sku' is honored.
		add_filter( 'wc_product_pre_has_unique_sku', array( $this, 'dont_allow_skus_with_numbers_lower_than_6' ), 10, 3 );

		$third_duplicate = ( new WC_Admin_Duplicate_Product() )->product_duplicate( $product );
		$this->assertEquals( 'woo-cap-6', $third_duplicate->get_sku() );
		$this->assertNotEquals( $product->get_id(), $third_duplicate->get_id() );

		remove_filter( 'wc_product_pre_has_unique_sku', array( $this, 'dont_allow_skus_with_numbers_lower_than_6' ) );

		// Verify the filter 'wc_product_has_unique_sku' is honored.
		add_filter( 'wc_product_has_unique_sku', array( $this, 'dont_allow_skus_with_numbers_lower_than_8' ), 10, 3 );

		$fourth_duplicate = ( new WC_Admin_Duplicate_Product() )->product_duplicate( $product );
		$this->assertEquals( 'woo-cap-8', $fourth_duplicate->get_sku() );
		$this->assertNotEquals( $product->get_id(), $fourth_duplicate->get_id() );

		remove_filter( 'wc_product_has_unique_sku', array( $this, 'dont_allow_skus_with_numbers_lower_than_8' ) );
	}

	/**
	 * Tests that duplicating a product with hyphens in the SKU correctly appends the suffix.
	 *
	 * For SKUs like "SKU-123", the duplicate should get "SKU-123-1", not "SKU-124".
	 * This tests the fix for issue #65378.
	 */
	public function test_duplicate_product_with_hyphenated_sku_preserves_original() {
		// Test case 1: Simple hyphen with number.
		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_sku( 'SKU-123' );
		$product1->save();

		$duplicate1 = ( new WC_Admin_Duplicate_Product() )->product_duplicate( $product1 );
		$this->assertEquals( 'SKU-123-1', $duplicate1->get_sku(), 'Duplicate of SKU-123 should be SKU-123-1' );

		// Test case 2: Multiple hyphens.
		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_sku( 'my-product-456' );
		$product2->save();

		$duplicate2 = ( new WC_Admin_Duplicate_Product() )->product_duplicate( $product2 );
		$this->assertEquals( 'my-product-456-1', $duplicate2->get_sku(), 'Duplicate of my-product-456 should be my-product-456-1' );

		// Test case 3: Non-numeric suffix (should still work).
		$product3 = WC_Helper_Product::create_simple_product();
		$product3->set_sku( 'PROD-A' );
		$product3->save();

		$duplicate3 = ( new WC_Admin_Duplicate_Product() )->product_duplicate( $product3 );
		$this->assertEquals( 'PROD-A-1', $duplicate3->get_sku(), 'Duplicate of PROD-A should be PROD-A-1' );

		// Test case 4: Sequential duplicates.
		$product4 = WC_Helper_Product::create_simple_product();
		$product4->set_sku( 'SEQ-100' );
		$product4->save();

		$dup_a = ( new WC_Admin_Duplicate_Product() )->product_duplicate( $product4 );
		$this->assertEquals( 'SEQ-100-1', $dup_a->get_sku() );

		$dup_b = ( new WC_Admin_Duplicate_Product() )->product_duplicate( $product4 );
		$this->assertEquals( 'SEQ-100-2', $dup_b->get_sku() );

		$dup_c = ( new WC_Admin_Duplicate_Product() )->product_duplicate( $product4 );
		$this->assertEquals( 'SEQ-100-3', $dup_c->get_sku() );
	}

	/**
	 * Tests that case-insensitive SKU matching prevents collisions.
	 *
	 * MySQL LIKE is case-insensitive by default, so 'SKU-%' matches 'sku-1'.
	 * The prefix check must use stripos() to match SQL behavior.
	 */
	public function test_duplicate_product_handles_case_insensitive_sku_conflict() {
		// Create product with uppercase SKU.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_sku( 'SKU' );
		$product->save();

		// Create a different product with lowercase variant + suffix.
		$other = WC_Helper_Product::create_simple_product();
		$other->set_sku( 'sku-1' );
		$other->save();

		// Duplicate the uppercase product.
		// SQL LIKE 'SKU-%' returns 'sku-1', so max_suffix should be 1.
		// Expected: 'SKU-2', not 'SKU-1' (which collides with 'sku-1').
		$duplicate = ( new WC_Admin_Duplicate_Product() )->product_duplicate( $product );
		$this->assertEquals( 'SKU-2', $duplicate->get_sku(), 'Duplicate must not collide with case-variant sku-1' );
	}
}
