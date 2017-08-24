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
			'columns' => '4',
			'orderby' => 'title',
			'order'   => 'asc',
			'ids'     => '',
			'skus'    => '',
		);
		$this->assertEquals( $expected, $shortcode->get_attributes() );

		$shortcode2 = new WC_Shortcode_Products( array(
			'orderby' => 'id',
			'order'   => 'desc',
		) );
		$expected2  = array(
			'columns' => '4',
			'orderby' => 'id',
			'order'   => 'desc',
			'ids'     => '',
			'skus'    => '',
		);
		$this->assertEquals( $expected2, $shortcode2->get_attributes() );
	}

	/**
	 * Test: WC_Shortcode_Products::get_query_args.
	 */
	public function test_get_query_args() {
		$shortcode = new WC_Shortcode_Products();
		$expected  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'orderby'             => 'title',
			'order'               => 'asc',
			'posts_per_page'      => -1,
			'meta_query'          => WC()->query->get_meta_query(),
			'tax_query'           => WC()->query->get_tax_query(),
		);
		$this->assertEquals( $expected, $shortcode->get_query_args() );

		$shortcode2 = new WC_Shortcode_Products( array(
			'orderby' => 'id',
			'order'   => 'desc',
		) );
		$expected2  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'orderby'             => 'id',
			'order'               => 'desc',
			'posts_per_page'      => -1,
			'meta_query'          => WC()->query->get_meta_query(),
			'tax_query'           => WC()->query->get_tax_query(),
		);
		$this->assertEquals( $expected2, $shortcode2->get_query_args() );

		$shortcode2 = new WC_Shortcode_Products( array(
			'ids'  => '1,2,3',
			'skus' => 'foo,bar',
		) );
		$expected2  = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'orderby'             => 'title',
			'order'               => 'asc',
			'posts_per_page'      => -1,
			'meta_query'          => WC()->query->get_meta_query(),
			'tax_query'           => WC()->query->get_tax_query(),
			'post__in'            => array( '1', '2', '3' ),
		);
		$expected2['meta_query'][] = array(
			'key'     => '_sku',
			'value'   => array( 'foo', 'bar' ),
			'compare' => 'IN',
		);

		$this->assertEquals( $expected2, $shortcode2->get_query_args() );
	}

	/**
	 * Test: WC_Shortcode_Products::get_loop_name.
	 */
	public function test_loop_name() {
		$shortcode = new WC_Shortcode_Products();

		$this->assertEquals( 'products', $shortcode->get_loop_name() );
	}

	/**
	 * Test: WC_Shortcode_Products::get_content.
	 */
	public function test_get_content() {
		$shortcode = new WC_Shortcode_Products();

		$this->assertTrue( ! empty( $shortcode->get_content() ) );
	}
}
