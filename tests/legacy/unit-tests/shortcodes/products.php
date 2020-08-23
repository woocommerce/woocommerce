<?php
/**
 * Test WC_Shortcode_Products
 *
 * @package WooCommerce\Tests\Shortcodes
 */

/**
 * Class WC_Test_Shortcode_Products.
 */
class WC_Test_Shortcode_Products extends WC_Unit_Test_Case {

	/**
	 * Test: WC_Shortcode_Products::get_attributes.
	 */
	public function test_get_attributes() {
		$shortcode = new WC_Shortcode_Products();
		$expected  = array(
			'limit'          => '-1',
			'columns'        => 4,
			'orderby'        => '',
			'order'          => '',
			'ids'            => '',
			'skus'           => '',
			'category'       => '',
			'cat_operator'   => 'IN',
			'attribute'      => '',
			'terms'          => '',
			'terms_operator' => 'IN',
			'tag'            => '',
			'tag_operator'   => 'IN',
			'visibility'     => 'visible',
			'class'          => '',
			'rows'           => '',
			'page'           => 1,
			'paginate'       => false,
			'cache'          => true,
			'tag'            => '',
		);
		$this->assertEquals( $expected, $shortcode->get_attributes() );

		$shortcode2 = new WC_Shortcode_Products(
			array(
				'orderby' => 'id',
				'order'   => 'DESC',
			)
		);
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
			'tag'            => '',
			'tag_operator'   => 'IN',
			'visibility'     => 'visible',
			'class'          => '',
			'rows'           => '',
			'page'           => 1,
			'paginate'       => false,
			'cache'          => true,
			'tag'            => '',
		);
		$this->assertEquals( $expected2, $shortcode2->get_attributes() );
	}

	/**
	 * Test: WC_Shortcode_Products::get_query_args.
	 */
	public function test_get_query_args() {
		$meta_query = WC()->query->get_meta_query();
		$tax_query  = WC()->query->get_tax_query();

		// Empty products shortcode.
		$shortcode = new WC_Shortcode_Products();
		$expected  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'menu_order title',
			'order'               => 'ASC',
			'posts_per_page'      => -1,
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'           => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'fields'              => 'ids',
		);
		$this->assertEquals( $expected, $shortcode->get_query_args() );

		// products shortcode with attributes.
		$shortcode2 = new WC_Shortcode_Products(
			array(
				'orderby' => 'ID',
				'order'   => 'DESC',
			)
		);
		$expected2  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'ID',
			'order'               => 'DESC',
			'posts_per_page'      => '-1',
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'           => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'fields'              => 'ids',
		);
		$this->assertEquals( $expected2, $shortcode2->get_query_args() );

		$shortcode3                = new WC_Shortcode_Products(
			array(
				'ids'  => '1,2,3',
				'skus' => 'foo,bar',
			)
		);
		$expected3                 = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'menu_order title',
			'order'               => 'ASC',
			'posts_per_page'      => -1,
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'           => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'post__in'            => array( '1', '2', '3' ),
			'fields'              => 'ids',
		);
		$expected3['meta_query'][] = array(
			'key'     => '_sku',
			'value'   => array( 'foo', 'bar' ),
			'compare' => 'IN',
		);

		$this->assertEquals( $expected3, $shortcode3->get_query_args() );

		// product_category shortcode.
		$shortcode4               = new WC_Shortcode_Products(
			array(
				'per_page' => '12',
				'columns'  => '4',
				'orderby'  => 'title',
				'order'    => 'ASC',
				'category' => 'clothing',
				'operator' => 'IN',
			),
			'product_category'
		);
		$expected4                = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'title',
			'order'               => 'ASC',
			'posts_per_page'      => '12',
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'           => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'fields'              => 'ids',
		);
		$expected4['tax_query'][] = array(
			'taxonomy'         => 'product_cat',
			'terms'            => array( 'clothing' ),
			'field'            => 'slug',
			'operator'         => 'IN',
			'include_children' => true,
		);

		$this->assertEquals( $expected4, $shortcode4->get_query_args() );

		// product_category shortcode using category ids.
		$shortcode4_id               = new WC_Shortcode_Products(
			array(
				'per_page' => '12',
				'columns'  => '4',
				'orderby'  => 'title',
				'order'    => 'ASC',
				'category' => '123',
				'operator' => 'IN',
			),
			'product_category'
		);
		$expected4_id                = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'title',
			'order'               => 'ASC',
			'posts_per_page'      => '12',
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'           => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'fields'              => 'ids',
		);
		$expected4_id['tax_query'][] = array(
			'taxonomy'         => 'product_cat',
			'terms'            => array( 123 ),
			'field'            => 'term_id',
			'operator'         => 'IN',
			'include_children' => true,
		);

		$this->assertEquals( $expected4_id, $shortcode4_id->get_query_args() );

		// recent_products shortcode.
		$shortcode5 = new WC_Shortcode_Products(
			array(
				'per_page' => '12',
				'columns'  => '4',
				'orderby'  => 'date',
				'order'    => 'DESC',
				'category' => '',
				'operator' => 'IN',
			),
			'recent_products'
		);
		$expected5  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'date ID',
			'order'               => 'DESC',
			'posts_per_page'      => '12',
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'           => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'fields'              => 'ids',
		);

		$this->assertEquals( $expected5, $shortcode5->get_query_args() );

		// product shortcode.
		$shortcode6 = new WC_Shortcode_Products(
			array(
				'ids'      => '1',
				'per_page' => '1',
			),
			'product'
		);
		$expected6  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'menu_order title',
			'order'               => 'ASC',
			'posts_per_page'      => 1,
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'           => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'p'                   => '1',
			'fields'              => 'ids',
		);

		$this->assertEquals( $expected6, $shortcode6->get_query_args() );

		// sale_products shortcode.
		$shortcode7 = new WC_Shortcode_Products(
			array(
				'per_page' => '12',
				'columns'  => '4',
				'orderby'  => 'title',
				'order'    => 'ASC',
				'category' => '',
				'operator' => 'IN',
			),
			'sale_products'
		);
		$expected7  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'title',
			'order'               => 'ASC',
			'posts_per_page'      => 12,
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'           => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'post__in'            => array_merge( array( 0 ), wc_get_product_ids_on_sale() ),
			'fields'              => 'ids',
		);

		$this->assertEquals( $expected7, $shortcode7->get_query_args() );

		// best_selling_products shortcode.
		$shortcode8 = new WC_Shortcode_Products(
			array(
				'per_page' => '12',
				'columns'  => '4',
				'category' => '',
				'operator' => 'IN',
			),
			'best_selling_products'
		);
		$expected8  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'meta_value_num',
			'order'               => 'DESC',
			'posts_per_page'      => 12,
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'           => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'meta_key'            => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'fields'              => 'ids',
		);

		$this->assertEquals( $expected8, $shortcode8->get_query_args() );

		// top_rated_products shortcode.
		$shortcode9 = new WC_Shortcode_Products(
			array(
				'per_page' => '12',
				'columns'  => '4',
				'orderby'  => 'title',
				'order'    => 'ASC',
				'category' => '',
				'operator' => 'IN',
			),
			'top_rated_products'
		);
		$expected9  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'meta_value_num',
			'order'               => 'DESC',
			'posts_per_page'      => 12,
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'           => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'fields'              => 'ids',
			'meta_key'            => '_wc_average_rating', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		);

		$this->assertEquals( $expected9, $shortcode9->get_query_args() );

		// featured_products shortcode.
		$shortcode10 = new WC_Shortcode_Products(
			array(
				'per_page'   => '12',
				'columns'    => '4',
				'orderby'    => 'date',
				'order'      => 'DESC',
				'category'   => '',
				'operator'   => 'IN',
				'visibility' => 'featured',
			)
		);
		$expected10  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'date ID',
			'order'               => 'DESC',
			'posts_per_page'      => 12,
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'           => array_merge( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				$tax_query,
				array(
					array(
						'taxonomy'         => 'product_visibility',
						'terms'            => 'featured',
						'field'            => 'name',
						'operator'         => 'IN',
						'include_children' => false,
					),
				)
			),
			'fields'              => 'ids',
		);

		$this->assertEquals( $expected10, $shortcode10->get_query_args() );

		// product_attribute shortcode.
		$shortcode11 = new WC_Shortcode_Products(
			array(
				'per_page'  => '12',
				'columns'   => '4',
				'orderby'   => 'title',
				'order'     => 'asc',
				'attribute' => 'color',
				'filter'    => 'black',
			),
			'product_attribute'
		);
		$expected11  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'title',
			'order'               => 'ASC',
			'posts_per_page'      => 12,
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'           => array_merge( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				$tax_query,
				array(
					array(
						'taxonomy' => 'pa_color',
						'terms'    => array( 'black' ),
						'field'    => 'slug',
						'operator' => 'IN',
					),
				)
			),
			'fields'              => 'ids',
		);

		$this->assertEquals( $expected11, $shortcode11->get_query_args() );

		// product_attribute shortcode using term ids.
		$shortcode11_id = new WC_Shortcode_Products(
			array(
				'per_page'  => '12',
				'columns'   => '4',
				'orderby'   => 'title',
				'order'     => 'asc',
				'attribute' => 'color',
				'terms'     => '123',
			),
			'product_attribute'
		);
		$expected11_id  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'title',
			'order'               => 'ASC',
			'posts_per_page'      => 12,
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'           => array_merge( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				$tax_query,
				array(
					array(
						'taxonomy' => 'pa_color',
						'terms'    => array( 123 ),
						'field'    => 'term_id',
						'operator' => 'IN',
					),
				)
			),
			'fields'              => 'ids',
		);

		$this->assertEquals( $expected11_id, $shortcode11_id->get_query_args() );

		// Check for visibility shortcode.
		$shortcode12 = new WC_Shortcode_Products(
			array(
				'visibility' => 'hidden',
			)
		);
		$expected12  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'menu_order title',
			'order'               => 'ASC',
			'posts_per_page'      => -1,
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy'         => 'product_visibility',
					'terms'            => array( 'exclude-from-catalog', 'exclude-from-search' ),
					'field'            => 'name',
					'operator'         => 'AND',
					'include_children' => false,
				),
			),
			'fields'              => 'ids',
		);

		$this->assertEquals( $expected12, $shortcode12->get_query_args() );

		$shortcode13 = new WC_Shortcode_Products(
			array(
				'visibility' => 'catalog',
			)
		);
		$expected13  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'menu_order title',
			'order'               => 'ASC',
			'posts_per_page'      => -1,
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy'         => 'product_visibility',
					'terms'            => 'exclude-from-search',
					'field'            => 'name',
					'operator'         => 'IN',
					'include_children' => false,
				),
				array(
					'taxonomy'         => 'product_visibility',
					'terms'            => 'exclude-from-catalog',
					'field'            => 'name',
					'operator'         => 'NOT IN',
					'include_children' => false,
				),
			),
			'fields'              => 'ids',
		);

		$this->assertEquals( $expected13, $shortcode13->get_query_args() );

		$shortcode14 = new WC_Shortcode_Products(
			array(
				'visibility' => 'search',
			)
		);
		$expected14  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'menu_order title',
			'order'               => 'ASC',
			'posts_per_page'      => -1,
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy'         => 'product_visibility',
					'terms'            => 'exclude-from-catalog',
					'field'            => 'name',
					'operator'         => 'IN',
					'include_children' => false,
				),
				array(
					'taxonomy'         => 'product_visibility',
					'terms'            => 'exclude-from-search',
					'field'            => 'name',
					'operator'         => 'NOT IN',
					'include_children' => false,
				),
			),
			'fields'              => 'ids',
		);

		$this->assertEquals( $expected14, $shortcode14->get_query_args() );

		// products shortcode -- select multiple categories using AND operator.
		$shortcode15 = new WC_Shortcode_Products(
			array(
				'category'     => 'cat1,cat2',
				'cat_operator' => 'AND',
			)
		);
		$expected15  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'menu_order title',
			'order'               => 'ASC',
			'posts_per_page'      => -1,
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'           => array_merge( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				$tax_query,
				array(
					array(
						'taxonomy'         => 'product_cat',
						'terms'            => array( 'cat1', 'cat2' ),
						'field'            => 'slug',
						'operator'         => 'AND',
						'include_children' => false,
					),
				)
			),
			'fields'              => 'ids',
		);

		$this->assertEquals( $expected15, $shortcode15->get_query_args() );

		// products shortcode -- exclude multiple categories using NOT IN operator.
		$shortcode16 = new WC_Shortcode_Products(
			array(
				'category'     => 'cat1,cat2',
				'cat_operator' => 'NOT IN',
			)
		);
		$expected16  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'menu_order title',
			'order'               => 'ASC',
			'posts_per_page'      => -1,
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'           => array_merge( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				$tax_query,
				array(
					array(
						'taxonomy'         => 'product_cat',
						'terms'            => array( 'cat1', 'cat2' ),
						'field'            => 'slug',
						'operator'         => 'NOT IN',
						'include_children' => true,
					),
				)
			),
			'fields'              => 'ids',
		);

		$this->assertEquals( $expected16, $shortcode16->get_query_args() );

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
	 * Test: WC_Shortcode_Products::set_product_as_visible.
	 */
	public function test_set_product_as_visible() {
		$shortcode = new WC_Shortcode_Products();
		$this->assertFalse( $shortcode->set_product_as_visible( false ) );

		$shortcode2 = new WC_Shortcode_Products(
			array(
				'visibility' => 'hidden',
			)
		);
		$this->assertTrue( $shortcode2->set_product_as_visible( false ) );
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

		$this->assertEquals(
			$expected,
			WC_Shortcode_Products::order_by_rating_post_clauses(
				array(
					'where' => '',
					'join'  => '',
				)
			)
		);
	}
}
