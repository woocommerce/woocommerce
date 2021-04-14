<?php
/**
 * Unit tests for wc-term-functions.php.
 *
 * @package WooCommerce\Tests\Functions\Term
 */

// phpcs:disable WordPress.Files.FileName

/**
 * Class WC_Term_Functions_Tests.
 */
class WC_Term_Functions_Tests extends \WC_Unit_Test_Case {
	/**
	 * Test to make sure products without categories will be
	 * assigned a default category always.
	 */
	public function test_products_are_assigned_a_default_category() {
		global $wpdb;

		$product1         = WC_Helper_Product::create_simple_product();
		$product2         = WC_Helper_Product::create_simple_product();
		$product3         = WC_Helper_Product::create_simple_product();
		$default_category = (int) get_option( 'default_product_cat', 0 );

		$products = array( $product1, $product2, $product3 );

		// Remove all categories from products.
		foreach ( $products as $product ) {
			$result = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->term_relationships} WHERE object_id = %d AND term_taxonomy_id = %d",
					$product->get_id(),
					$default_category
				)
			);
		}

		// Ensure all categories are removed from products.
		foreach ( $products as $product ) {
			$cats = wp_get_post_terms( $product->get_id(), 'product_cat' );

			$this->assertEmpty( $cats );
		}

		// Add in default category.
		_wc_maybe_assign_default_product_cat();

		// Ensure default category are now assigned to products.
		foreach ( $products as $product ) {
			$cats = wp_list_pluck( wp_get_post_terms( $product->get_id(), 'product_cat' ), 'term_id' );

			$this->assertContains( $default_category, $cats );
		}
	}
}
