<?php
/**
 * WooCommerce Brands Unit tests suite
 *
 * @package woocommerce-brands
 */

declare( strict_types = 1);

require_once WC_ABSPATH . '/includes/class-wc-brands.php';

/**
 * WC Brands test
 */
class WC_Brands_Test extends WC_Unit_Test_Case {
	/**
	 * Test that `product_brand_thumbnails` shortcode's `show_empty` argument works as expected.
	 * This test prevents regression of the issue where double filtering caused no brands to be displayed.
	 */
	public function test_product_brand_thumbnails_shortcode_with_show_empty_arg() {
		$data            = $this->setup_brand_test_data();
		$brands_instance = $data['brands_instance'];

		$output = $brands_instance->output_product_brand_thumbnails( array( 'show_empty' => 'false' ) );

		// Full brand is shown, empty brand is not.
		$this->assertStringContainsString( 'Full Brand', $output );
		$this->assertStringNotContainsString( 'Empty Brand', $output );

		$output = $brands_instance->output_product_brand_thumbnails( array( 'show_empty' => 'true' ) );

		// Both brands are shown.
		$this->assertStringContainsString( 'Full Brand', $output );
		$this->assertStringContainsString( 'Empty Brand', $output );
	}

	/**
	 * Test that `product_brand_thumbnails_description` shortcode's `show_empty` argument works as expected.
	 */
	public function test_product_brand_thumbnails_description_shortcode_with_show_empty_arg() {
		$data            = $this->setup_brand_test_data();
		$brands_instance = $data['brands_instance'];

		$output = $brands_instance->output_product_brand_thumbnails_description( array( 'show_empty' => 'false' ) );

		// Full brand is shown, empty brand is not.
		$this->assertStringContainsString( 'Full Brand', $output );
		$this->assertStringNotContainsString( 'Empty Brand', $output );

		$output = $brands_instance->output_product_brand_thumbnails_description( array( 'show_empty' => 'true' ) );

		// Both brands are shown.
		$this->assertStringContainsString( 'Full Brand', $output );
		$this->assertStringContainsString( 'Empty Brand', $output );
	}

	/**
	 * Test that `product_brand_list` shortcode's `show_empty_brands` argument works as expected.
	 */
	public function test_product_brand_list_shortcode_with_show_empty_brands_arg() {
		$data            = $this->setup_brand_test_data();
		$brands_instance = $data['brands_instance'];

		$output = $brands_instance->output_product_brand_list( array( 'show_empty_brands' => 'false' ) );

		// Full brand is shown, empty brand is not.
		$this->assertStringContainsString( 'Full Brand', $output );
		$this->assertStringNotContainsString( 'Empty Brand', $output );

		$output = $brands_instance->output_product_brand_list( array( 'show_empty_brands' => 'true' ) );

		// Both brands are shown.
		$this->assertStringContainsString( 'Full Brand', $output );
		$this->assertStringContainsString( 'Empty Brand', $output );
	}

	/**
	 * Helper method to set up test data for brand shortcode tests.
	 *
	 * @return array Contains brands instance, brand term IDs and product ID.
	 */
	private function setup_brand_test_data() {
		WC_Brands::init_taxonomy();

		$brand_with_products = wp_insert_term( 'Full Brand', 'product_brand' );
		$empty_brand         = wp_insert_term( 'Empty Brand', 'product_brand' );

		$product = WC_Helper_Product::create_simple_product();
		$product->save();
		wp_set_object_terms( $product->get_id(), array( $brand_with_products['term_id'] ), 'product_brand' );

		return array(
			'brands_instance'     => new WC_Brands(),
			'brand_with_products' => $brand_with_products,
			'empty_brand'         => $empty_brand,
			'product'             => $product,
		);
	}
}
