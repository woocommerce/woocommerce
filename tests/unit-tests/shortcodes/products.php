<?php

/**
 * Class WC_Shortcode_Products.
 *
 * @package WooCommerce\Tests\Shortcodes
 */
class WC_Test_Shortcode_Products extends WC_Unit_Test_Case {

	/**
	 * Test: WC_Shortcode_Products::get_attributes.
	 */
	public function test_get_attributes() {
		$shortcode = new WC_Shortcode_Products();
		$expected  = array(
			'per_page' => '-1',
			'columns'  => '4',
			'orderby'  => 'title',
			'order'    => 'ASC',
			'ids'      => '',
			'skus'     => '',
			'category' => '',
			'operator' => 'IN',
			'class'    => '',
		);
		$this->assertEquals( $expected, $shortcode->get_attributes() );

		$shortcode2 = new WC_Shortcode_Products( array(
			'orderby' => 'id',
			'order'   => 'DESC',
		) );
		$expected2  = array(
			'per_page' => '-1',
			'columns'  => '4',
			'orderby'  => 'id',
			'order'    => 'DESC',
			'ids'      => '',
			'skus'     => '',
			'category' => '',
			'operator' => 'IN',
			'class'    => '',
		);
		$this->assertEquals( $expected2, $shortcode2->get_attributes() );
	}

	/**
	 * Test: WC_Shortcode_Products::get_query_args.
	 */
	public function test_get_query_args() {
		$meta_query = WC()->query->get_meta_query();
		$tax_query  = WC()->query->get_tax_query();

		// Emtpy products shortcode.
		$shortcode = new WC_Shortcode_Products();
		$expected  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'title',
			'order'               => 'ASC',
			'posts_per_page'      => -1,
			'meta_query'          => $meta_query,
			'tax_query'           => $tax_query,
		);
		$this->assertEquals( $expected, $shortcode->get_query_args() );

		// products shortcode with attributes.
		$shortcode2 = new WC_Shortcode_Products( array(
			'orderby' => 'id',
			'order'   => 'DESC',
		) );
		$expected2  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'id',
			'order'               => 'DESC',
			'posts_per_page'      => -1,
			'meta_query'          => $meta_query,
			'tax_query'           => $tax_query,
		);
		$this->assertEquals( $expected2, $shortcode2->get_query_args() );

		$shortcode2 = new WC_Shortcode_Products( array(
			'ids'  => '1,2,3',
			'skus' => 'foo,bar',
		) );
		$expected2  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'title',
			'order'               => 'ASC',
			'posts_per_page'      => -1,
			'meta_query'          => $meta_query,
			'tax_query'           => $tax_query,
			'post__in'            => array( '1', '2', '3' ),
		);
		$expected2['meta_query'][] = array(
			'key'     => '_sku',
			'value'   => array( 'foo', 'bar' ),
			'compare' => 'IN',
		);

		$this->assertEquals( $expected2, $shortcode2->get_query_args() );

		// product_category shortcode.
		$shortcode3 = new WC_Shortcode_Products( array(
			'per_page' => '12',
			'columns'  => '4',
			'orderby'  => 'menu_order title',
			'order'    => 'ASC',
			'category' => 'clothing',
			'operator' => 'IN',
		), 'product_category' );
		$expected3  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'menu_order title',
			'order'               => 'ASC',
			'posts_per_page'      => 12,
			'meta_query'          => $meta_query,
			'tax_query'           => $tax_query,
			'meta_key'            => '',
		);
		$expected3['tax_query'][] = array(
			'taxonomy' => 'product_cat',
			'terms'    => array( 'clothing' ),
			'field'    => 'slug',
			'operator' => 'IN',
		);
		$this->assertEquals( $expected3, $shortcode3->get_query_args() );

		// recent_products shortcode.
		$shortcode4 = new WC_Shortcode_Products( array(
			'per_page' => '12',
			'columns'  => '4',
			'orderby'  => 'date',
			'order'    => 'DESC',
			'category' => '',
			'operator' => 'IN',
		), 'recent_products' );
		$expected4  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'posts_per_page'      => 12,
			'meta_query'          => $meta_query,
			'tax_query'           => $tax_query,
		);

		$this->assertEquals( $expected4, $shortcode4->get_query_args() );

		// product shortcode.
		$shortcode4 = new WC_Shortcode_Products( array(
			'ids'      => '1',
			'per_page' => '1',
		), 'product' );
		$expected4  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'title',
			'order'               => 'ASC',
			'posts_per_page'      => 1,
			'meta_query'          => $meta_query,
			'tax_query'           => $tax_query,
			'p'                   => '1',
		);

		$this->assertEquals( $expected4, $shortcode4->get_query_args() );
	}

	/**
	 * Test: WC_Shortcode_Products::get_type.
	 */
	public function test_loop_name() {
		$shortcode = new WC_Shortcode_Products();

		$this->assertEquals( 'products', $shortcode->get_type() );
	}

	/**
	 * Test: WC_Shortcode_Products::get_content.
	 */
	public function test_get_content() {
		$shortcode = new WC_Shortcode_Products();

		$this->assertTrue( ! empty( $shortcode->get_content() ) );
	}
}
