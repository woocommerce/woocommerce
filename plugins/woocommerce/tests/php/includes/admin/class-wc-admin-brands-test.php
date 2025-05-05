<?php
/**
 * WooCommerce Brands Unit tests suit
 *
 * @package woocommerce-brands
 */

declare( strict_types = 1);

require_once WC_ABSPATH . '/includes/admin/class-wc-admin-brands.php';
require_once WC_ABSPATH . '/includes/class-wc-brands.php';

/**
 * WC Brands Admin test
 */
class WC_Admin_Brands_Test extends WC_Unit_Test_Case {


	/**
	 * Tests brands filter outputs as a standard dropdown.
	 *
	 * @return void
	 */
	public function test_product_brand_filter_render_outputs_a_dropdown() {
		$simple_product = WC_Helper_Product::create_simple_product();

		WC_Brands::init_taxonomy();
		$term_a_id = $this->factory()->term->create(
			array(
				'taxonomy' => 'product_brand',
				'name'     => 'Blah_A',
			)
		);
		$term_b_id = $this->factory()->term->create(
			array(
				'taxonomy' => 'product_brand',
				'name'     => 'Foo_A',
			)
		);
		$term_c_id = $this->factory()->term->create(
			array(
				'taxonomy' => 'product_brand',
				'name'     => 'Blah_B',
			)
		);

		wp_set_post_terms( $simple_product->get_id(), array( $term_a_id, $term_b_id, $term_c_id ), 'product_brand' );

		add_filter(
			'woocommerce_product_brand_filter_threshold',
			function () {
				return 3;
			}
		);

		$brands_admin = new WC_Brands_Admin();
		ob_start();
		$brands_admin->render_product_brand_filter();
		$output = ob_get_contents();
		ob_end_clean();

		$this->assertStringContainsString(
			'<select  name=\'product_brand\' id=\'product_brand\' class=\'dropdown_product_brand\'',
			$output
		);
	}

	/**
	 * Tests brands filter outputs as a custom search-select component.
	 *
	 * @return void
	 */
	public function test_product_brand_filter_render_outputs_a_select() {
		$simple_product = WC_Helper_Product::create_simple_product();

		WC_Brands::init_taxonomy();
		$term_a_id = $this->factory()->term->create(
			array(
				'taxonomy' => 'product_brand',
				'name'     => 'Blah_A',
			)
		);
		$term_b_id = $this->factory()->term->create(
			array(
				'taxonomy' => 'product_brand',
				'name'     => 'Foo_A',
			)
		);
		$term_c_id = $this->factory()->term->create(
			array(
				'taxonomy' => 'product_brand',
				'name'     => 'Blah_B',
			)
		);

		wp_set_post_terms( $simple_product->get_id(), array( $term_a_id, $term_b_id, $term_c_id ), 'product_brand' );

		add_filter(
			'woocommerce_product_brand_filter_threshold',
			function () {
				return 2;
			}
		);

		$brands_admin = new WC_Brands_Admin();
		ob_start();
		$brands_admin->render_product_brand_filter();
		$output = ob_get_contents();
		ob_end_clean();

		$this->assertStringContainsString(
			'<select class="wc-brands-search" name="product_brand" data-placeholder="Filter by brand" data-allow_clear="true"',
			$output
		);
	}
}
