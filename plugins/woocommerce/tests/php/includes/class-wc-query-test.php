<?php

/**
 * Tests for WC_Query.
 */
class WC_Query_Test extends \WC_Unit_Test_Case {

	/**
	 * @testdox 'price_filter_post_clauses' generates the proper 'where' clause when there are 'max_price' and 'min_price' arguments in the query.
	 */
	public function test_price_filter_post_clauses_creates_the_proper_where_clause() {
		// phpcs:disable Squiz.Commenting
		$wp_query = new class() {
			public function is_main_query() {
				return true;
			}
		};
		// phpcs:enable Squiz.Commenting

		$_GET['min_price'] = '100';
		$_GET['max_price'] = '200';

		$sut = new WC_Query();

		$args = array(
			'join'  => '(JOIN CLAUSE)',
			'where' => '(WHERE CLAUSE)',
		);

		$args     = $sut->price_filter_post_clauses( $args, $wp_query );
		$expected = '(WHERE CLAUSE) AND NOT (200.000000<wc_product_meta_lookup.min_price OR 100.000000>wc_product_meta_lookup.max_price ) ';

		$this->assertEquals( $expected, $args['where'] );
	}

	/**
	 * @testdox Shop page can be set as the homepage on block themes.
	 */
	public function test_shop_page_in_home_displays_correctly() {
		switch_theme( 'twentytwentyfour' );

		// Create a page and use it as the Shop page.
		$shop_page_id                     = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Shop',
			)
		);
		$default_woocommerce_shop_page_id = get_option( 'woocommerce_shop_page_id' );
		update_option( 'woocommerce_shop_page_id', $shop_page_id );

		// Set the Shop page as the homepage.
		$default_show_on_front = get_option( 'show_on_front' );
		$default_page_on_front = get_option( 'page_on_front' );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $shop_page_id );

		// Simulate the main query.
		$query = new WP_Query(
			array(
				'post_type' => 'page',
				'page_id'   => $shop_page_id,
			)
		);
		global $wp_the_query;
		$previous_wp_the_query = $wp_the_query;
		$wp_the_query          = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$query->get_posts();

		$this->assertTrue( defined( 'SHOP_IS_ON_FRONT' ) && SHOP_IS_ON_FRONT );

		// Reset main query, options and delete the page we created.
		$wp_the_query = $previous_wp_the_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		update_option( 'woocommerce_shop_page_id', $default_woocommerce_shop_page_id );
		update_option( 'show_on_front', $default_show_on_front );
		update_option( 'page_on_front', $default_page_on_front );
		wp_delete_post( $shop_page_id, true );
	}

	/**
	 * @testdox Shop page can be identified by slug when page_id is not populated in query vars.
	 */
	public function test_shop_page_resolves_by_slug_without_page_id() {
		switch_theme( 'twentytwentyfour' );

		$shop_page_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Shop',
				'post_name'   => 'shop',
			)
		);

		$default_woocommerce_shop_page_id = get_option( 'woocommerce_shop_page_id' );
		update_option( 'woocommerce_shop_page_id', $shop_page_id );

		// Set the Shop page as the homepage.
		$default_show_on_front = get_option( 'show_on_front' );
		$default_page_on_front = get_option( 'page_on_front' );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $shop_page_id );

		$query = new WP_Query(
			array(
				'post_type' => 'page',
				'pagename'  => 'shop',
				// NOTE: We are deliberately NOT setting `page_id` to simulate slug-based resolution.
				// See https://github.com/woocommerce/woocommerce/issues/61676 for more details.
			)
		);

		global $wp_the_query;
		$previous_wp_the_query = $wp_the_query;
		$wp_the_query          = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$query->get_posts();

		// Test that shop page is properly identified.
		$this->assertTrue( defined( 'SHOP_IS_ON_FRONT' ) && SHOP_IS_ON_FRONT );

		// Test that queried object is a WP_Post, not WP_Post_Type (critical for theme compatibility).
		$queried_object = $query->get_queried_object();
		$this->assertInstanceOf( 'WP_Post', $queried_object, 'Queried object should be a WP_Post' );
		$this->assertEquals( $shop_page_id, $queried_object->ID );

		// Reset main query, options and delete the page we created.
		$wp_the_query = $previous_wp_the_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		update_option( 'woocommerce_shop_page_id', $default_woocommerce_shop_page_id );
		update_option( 'show_on_front', $default_show_on_front );
		update_option( 'page_on_front', $default_page_on_front );
		wp_delete_post( $shop_page_id, true );
	}

	/**
	 * @testdox Shop page queried object has all required post properties for theme compatibility.
	 */
	public function test_shop_page_queried_object_has_all_post_properties() {
		switch_theme( 'twentytwentyfour' );

		$shop_page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Shop',
				'post_name'    => 'shop',
				'post_content' => 'Shop page content',
				'post_excerpt' => 'Shop page excerpt',
			)
		);

		$default_woocommerce_shop_page_id = get_option( 'woocommerce_shop_page_id' );
		update_option( 'woocommerce_shop_page_id', $shop_page_id );

		// Set the Shop page as the homepage.
		$default_show_on_front = get_option( 'show_on_front' );
		$default_page_on_front = get_option( 'page_on_front' );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $shop_page_id );

		$query = new WP_Query(
			array(
				'post_type' => 'page',
				'page_id'   => $shop_page_id,
			)
		);

		global $wp_the_query;
		$previous_wp_the_query = $wp_the_query;
		$wp_the_query          = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$query->get_posts();

		$queried_object = $query->get_queried_object();

		// Assert it's a WP_Post, not WP_Post_Type (critical for Divi and other themes).
		$this->assertInstanceOf( 'WP_Post', $queried_object );

		// Assert essential properties exist (these were missing in the regression).
		$this->assertObjectHasProperty( 'ID', $queried_object );
		$this->assertObjectHasProperty( 'post_content', $queried_object );
		$this->assertObjectHasProperty( 'post_excerpt', $queried_object );
		$this->assertObjectHasProperty( 'post_title', $queried_object );
		$this->assertObjectHasProperty( 'post_name', $queried_object );
		$this->assertObjectHasProperty( 'post_parent', $queried_object );
		$this->assertObjectHasProperty( 'post_author', $queried_object );
		$this->assertObjectHasProperty( 'comment_status', $queried_object );

		// Assert values are correct.
		$this->assertEquals( $shop_page_id, $queried_object->ID );
		$this->assertEquals( 'Shop page content', $queried_object->post_content );
		$this->assertEquals( 'Shop page excerpt', $queried_object->post_excerpt );
		$this->assertEquals( 'Shop', $queried_object->post_title );

		// Cleanup.
		$wp_the_query = $previous_wp_the_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		update_option( 'woocommerce_shop_page_id', $default_woocommerce_shop_page_id );
		update_option( 'show_on_front', $default_show_on_front );
		update_option( 'page_on_front', $default_page_on_front );
		wp_delete_post( $shop_page_id, true );
	}

}
