<?php
/**
 * WooCommerce Brands Unit tests suite
 *
 * @package woocommerce-brands
 */

declare( strict_types = 1);

/**
 * WC Brands test
 */
class WC_Brands_Test extends WC_Unit_Test_Case {

	/**
	 * Tear down test data.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clear term cache to prevent interference between tests.
		clean_term_cache( array(), 'product_brand' );
	}
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
	 * @testdox Product brand shortcode omits image style when width and height are empty.
	 */
	public function test_product_brand_shortcode_omits_empty_image_style() {
		$data = $this->setup_single_brand_shortcode_test_data();

		$output = $data['brands_instance']->output_product_brand(
			array(
				'post_id' => $data['product']->get_id(),
			)
		);

		$this->assertStringContainsString( '<img ', $output );
		$this->assertStringNotContainsString( ' style=', $output );
		$this->assertStringNotContainsString( 'width: ;', $output );
		$this->assertStringNotContainsString( 'height: ;', $output );
	}

	/**
	 * @testdox Product brand shortcode normalizes numeric dimensions to pixels.
	 */
	public function test_product_brand_shortcode_normalizes_numeric_dimensions_to_pixels() {
		$data = $this->setup_single_brand_shortcode_test_data();

		$output = $data['brands_instance']->output_product_brand(
			array(
				'post_id' => $data['product']->get_id(),
				'width'   => '123',
				'height'  => '567',
			)
		);

		$this->assertStringContainsString( 'style="width: 123px; height: 567px;"', $output );
		$this->assertStringNotContainsString( 'width: 123;', $output );
		$this->assertStringNotContainsString( 'height: 567;', $output );
	}

	/**
	 * @testdox Product brand shortcode keeps unit dimensions and auto fallback.
	 */
	public function test_product_brand_shortcode_keeps_unit_dimensions_and_auto_fallback() {
		$data = $this->setup_single_brand_shortcode_test_data();

		$output = $data['brands_instance']->output_product_brand(
			array(
				'post_id' => $data['product']->get_id(),
				'width'   => '64px',
			)
		);

		$this->assertStringContainsString( 'style="width: 64px; height: auto;"', $output );
	}

	/**
	 * Test that brand counts are correctly calculated and cached.
	 */
	public function test_brand_count_calculation_and_caching() {
		$data = $this->setup_brand_test_data();

		// Get the brand term.
		clean_term_cache( $data['brand_with_products']['term_id'], 'product_brand' );
		$brand_term = $this->get_first_brand_term( array( $data['brand_with_products']['term_id'] ) );

		// Test that the count is correctly calculated.
		$this->assertEquals( 1, $brand_term->count, 'Brand should have 1 product' );

		// Test that the count is cached in term meta.
		$cached_count = get_term_meta( $brand_term->term_id, 'product_count_product_brand', true );
		$this->assertEquals( '1', $cached_count, 'Brand count should be cached in term meta' );
	}

	/**
	 * Test that brand counts respect product visibility settings.
	 */
	public function test_brand_count_respects_product_visibility() {
		$data    = $this->setup_brand_test_data();
		$product = $data['product'];

		// Enable hide out of stock setting FIRST.
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );

		// THEN set product to out of stock (hook will fire with correct setting).
		$product->set_stock_status( 'outofstock' );
		$product->save();

		// Get the brand term.
		$brand_term = $this->get_first_brand_term( array( $data['brand_with_products']['term_id'] ) );

		// Test that the count is 0 when product is out of stock and hidden.
		$this->assertEquals( 0, $brand_term->count, 'Brand count should be 0 when product is out of stock and hidden' );

		// Test that the count is cached correctly.
		$cached_count = get_term_meta( $brand_term->term_id, 'product_count_product_brand', true );
		$this->assertEquals( '0', $cached_count, 'Brand count should be cached as 0 when product is out of stock' );

		// Reset the setting.
		update_option( 'woocommerce_hide_out_of_stock_items', 'no' );
	}

	/**
	 * Test that brand counts are updated when products are added/removed.
	 */
	public function test_brand_count_updates_when_products_change() {
		$data    = $this->setup_brand_test_data();
		$product = $data['product'];

		// Initially should have 1 product.
		$brand_term = get_term( $data['brand_with_products']['term_id'], 'product_brand' );
		$this->assertEquals( 1, $brand_term->count, 'Brand should initially have 1 product' );

		// Remove the product from the brand.
		wp_set_object_terms( $product->get_id(), array(), 'product_brand' );

		// Count should be updated to 0.
		$brand_term_updated = get_term( $data['brand_with_products']['term_id'], 'product_brand' );
		$this->assertEquals( 0, $brand_term_updated->count, 'Brand should have 0 products after removal' );

		// Add the product back.
		wp_set_object_terms( $product->get_id(), array( $data['brand_with_products']['term_id'] ), 'product_brand' );

		// Count should be updated back to 1.
		$brand_term_final = get_term( $data['brand_with_products']['term_id'], 'product_brand' );
		$this->assertEquals( 1, $brand_term_final->count, 'Brand should have 1 product after re-adding' );
	}

	/**
	 * Test that brand counts ignore product visibility in admin context.
	 */
	public function test_brand_count_ignores_product_visibility_in_admin_context() {
		$data    = $this->setup_brand_test_data();
		$product = $data['product'];

		// Enable hide out of stock setting.
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );

		// Set product to out of stock.
		$product->set_stock_status( 'outofstock' );
		$product->save();

		// Set admin context.
		set_current_screen( 'edit-post' );

		// Get the brand term using helper.
		$brand_term = $this->get_first_brand_term( array( $data['brand_with_products']['term_id'] ) );

		// Test that the count is 1 (ignores out of stock setting in admin context).
		$this->assertEquals( 1, $brand_term->count, 'Brand count should be 1 in admin context, ignoring out of stock setting' );

		// Reset the setting.
		update_option( 'woocommerce_hide_out_of_stock_items', 'no' );
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

	/**
	 * Helper method to set up test data for single brand shortcode tests.
	 *
	 * @return array Contains brands instance, brand term, product, and thumbnail ID.
	 */
	private function setup_single_brand_shortcode_test_data() {
		WC_Brands::init_taxonomy();

		$brand   = wp_insert_term( 'Shortcode Brand', 'product_brand' );
		$product = WC_Helper_Product::create_simple_product();
		$product->save();
		wp_set_object_terms( $product->get_id(), array( $brand['term_id'] ), 'product_brand' );

		$thumbnail_id = $this->create_brand_thumbnail_attachment();
		update_term_meta( $brand['term_id'], 'thumbnail_id', $thumbnail_id );

		return array(
			'brands_instance' => new WC_Brands(),
			'brand'           => $brand,
			'product'         => $product,
			'thumbnail_id'    => $thumbnail_id,
		);
	}

	/**
	 * Create a lightweight image attachment for brand thumbnail tests.
	 *
	 * @return int Attachment ID.
	 */
	private function create_brand_thumbnail_attachment() {
		$attachment_id = wp_insert_attachment(
			array(
				'post_title'     => 'Brand shortcode thumbnail',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);

		update_post_meta( $attachment_id, '_wp_attached_file', 'brand-shortcode-thumbnail.jpg' );
		update_post_meta(
			$attachment_id,
			'_wp_attachment_metadata',
			array(
				'width'  => 100,
				'height' => 80,
				'file'   => 'brand-shortcode-thumbnail.jpg',
				'sizes'  => array(),
			)
		);

		return $attachment_id;
	}

	/**
	 * Helper method to get the first brand term.
	 *
	 * @param array $term_ids Array of brand term IDs to include.
	 * @return WP_Term The first brand term.
	 */
	private function get_first_brand_term( $term_ids = array() ) {
		$args = array(
			'taxonomy'   => 'product_brand',
			'hide_empty' => false,
		);
		if ( ! empty( $term_ids ) ) {
			$args['include'] = $term_ids;
		}
		$brand_terms = get_terms( $args );
		return $brand_terms[0];
	}
}
