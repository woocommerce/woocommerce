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
			'limit'          => '-1',
			'columns'        => '4',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'ids'            => '',
			'skus'           => '',
			'category'       => '',
			'cat_operator'   => 'IN',
			'attribute'      => '',
			'terms'          => '',
			'terms_operator' => 'IN',
			'class'          => '',
		);
		$this->assertEquals( $expected, $shortcode->get_attributes() );

		$shortcode2 = new WC_Shortcode_Products( array(
			'orderby' => 'id',
			'order'   => 'DESC',
		) );
		$expected2  = array(
			'limit'          => '-1',
			'columns'        => '4',
			'orderby'        => 'id',
			'order'          => 'DESC',
			'ids'            => '',
			'skus'           => '',
			'category'       => '',
			'cat_operator'   => 'IN',
			'attribute'      => '',
			'terms'          => '',
			'terms_operator' => 'IN',
			'class'          => '',
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
		$shortcode5 = new WC_Shortcode_Products( array(
			'ids'      => '1',
			'per_page' => '1',
		), 'product' );
		$expected5  = array(
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

		$this->assertEquals( $expected5, $shortcode5->get_query_args() );

		// sale_products shortcode.
		$shortcode6 = new WC_Shortcode_Products( array(
			'per_page' => '12',
			'columns'  => '4',
			'orderby'  => 'title',
			'order'    => 'ASC',
			'category' => '',
			'operator' => 'IN',
		), 'sale_products' );
		$expected6  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'title',
			'order'               => 'ASC',
			'posts_per_page'      => 12,
			'meta_query'          => $meta_query,
			'tax_query'           => $tax_query,
			'post__in'            => array_merge( array( 0 ), wc_get_product_ids_on_sale() ),
		);

		$this->assertEquals( $expected6, $shortcode6->get_query_args() );

		// best_selling_products shortcode.
		$shortcode7 = new WC_Shortcode_Products( array(
			'per_page' => '12',
			'columns'  => '4',
			'category' => '',
			'operator' => 'IN',
		), 'best_selling_products' );
		$expected7  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'meta_value_num',
			'order'               => 'DESC',
			'posts_per_page'      => 12,
			'meta_query'          => $meta_query,
			'tax_query'           => $tax_query,
			'meta_key'            => 'total_sales',
		);

		$this->assertEquals( $expected7, $shortcode7->get_query_args() );

		// top_rated_products shortcode.
		$shortcode8 = new WC_Shortcode_Products( array(
			'per_page' => '12',
			'columns'  => '4',
			'orderby'  => 'title',
			'order'    => 'ASC',
			'category' => '',
			'operator' => 'IN',
		), 'top_rated_products' );
		$expected8  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'title',
			'order'               => 'ASC',
			'posts_per_page'      => 12,
			'meta_query'          => $meta_query,
			'tax_query'           => $tax_query,
		);

		// featured_products shortcode.
		$shortcode9 = new WC_Shortcode_Products( array(
			'per_page' => '12',
			'columns'  => '4',
			'orderby'  => 'date',
			'order'    => 'DESC',
			'category' => '',
			'operator' => 'IN',
		), 'featured_products' );
		$expected9  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'posts_per_page'      => 12,
			'meta_query'          => $meta_query,
			'tax_query'           => array_merge( $tax_query, array(
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'featured',
					'operator' => 'IN',
				),
			) ),
		);

		// product_attribute shortcode.
		$shortcode10 = new WC_Shortcode_Products( array(
			'per_page'  => '12',
			'columns'   => '4',
			'orderby'   => 'title',
			'order'     => 'asc',
			'attribute' => 'color',
			'filter'    => 'black',
		), 'product_attribute' );
		$expected10  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'title',
			'order'               => 'ASC',
			'posts_per_page'      => 12,
			'meta_query'          => $meta_query,
			'tax_query'           => array_merge( $tax_query, array(
				array(
					'taxonomy' => 'pa_color',
					'terms'    => array( 'black' ),
					'field'    => 'slug',
					'operator' => 'IN',
				),
			) ),
		);

		$this->assertEquals( $expected10, $shortcode10->get_query_args() );
	}

	/**
	 * Test: WC_Shortcode_Products::get_type.
	 */
	public function test_get_type() {
		$shortcode = new WC_Shortcode_Products();

		$this->assertEquals( 'products', $shortcode->get_type() );
	}

	/**
	 * Test: WC_Shortcode_Products::get_content.
	 */
	public function test_get_content() {
		$shortcode = new WC_Shortcode_Products();
		$result    = $shortcode->get_content();

		$this->assertTrue( ! empty( $result ) );
	}

	/**
	 * Test: WC_Shortcode_Products::order_by_rating_post_clauses.
	 */
	public function test_order_by_rating_post_clauses() {
		global $wpdb;

		$expected = array(
			'where'   => " AND $wpdb->commentmeta.meta_key = 'rating' ",
			'join'    => "LEFT JOIN $wpdb->comments ON($wpdb->posts.ID = $wpdb->comments.comment_post_ID) LEFT JOIN $wpdb->commentmeta ON($wpdb->comments.comment_ID = $wpdb->commentmeta.comment_id)",
			'orderby' => "$wpdb->commentmeta.meta_value DESC",
			'groupby' => "$wpdb->posts.ID",
		);

		$this->assertEquals( $expected, WC_Shortcode_Products::order_by_rating_post_clauses( array( 'where' => '', 'join' => '' ) ) );
	}
}
