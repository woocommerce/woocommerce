<?php

declare( strict_types = 1 );

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

		$shop_page_id                     = wp_insert_post(
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

		$this->assertTrue( defined( 'SHOP_IS_ON_FRONT' ) && SHOP_IS_ON_FRONT );

		// Reset main query, options and delete the page we created.
		$wp_the_query = $previous_wp_the_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		update_option( 'woocommerce_shop_page_id', $default_woocommerce_shop_page_id );
		update_option( 'show_on_front', $default_show_on_front );
		update_option( 'page_on_front', $default_page_on_front );
		wp_delete_post( $shop_page_id, true );
	}

	/**
	 * @testdox Products with certain visibility settings are excluded from search results.
	 *
	 * @dataProvider visibility_exclusion_provider
	 *
	 * @param string $visibility     The catalog visibility setting to test.
	 * @param string $expected_message The expected assertion message.
	 */
	public function test_search_excludes_products_by_visibility( string $visibility, string $expected_message ) {
		// Create a product that should appear in search.
		$visible_product = WC_Helper_Product::create_simple_product();
		$visible_product->set_name( 'Search Visible Product' );
		$visible_product->set_catalog_visibility( 'visible' );
		$visible_product->save();

		// Create a product with the specified visibility setting.
		$hidden_product = WC_Helper_Product::create_simple_product();
		$hidden_product->set_name( 'Search Hidden Product' );
		$hidden_product->set_catalog_visibility( $visibility );
		$hidden_product->save();

		// Save the previous main query and prepare for a new one.
		global $wp_the_query, $wp_query;
		$previous_wp_the_query = $wp_the_query;
		$previous_wp_query     = $wp_query;

		// Create a product search query.
		// Set as the main query before running so pre_get_posts will fire.
		$query        = new WP_Query();
		$wp_the_query = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query     = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		// Execute the search query which will trigger pre_get_posts.
		$query->query( array( 's' => 'Search' ) );

		// Set it as the main query so pre_get_posts will run.
		$wp_the_query = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query     = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		// Now execute the query which will trigger pre_get_posts.
		$query->get_posts();
		$found_ids = wp_list_pluck( $query->posts, 'ID' );

		// Assert that the visible product is in the results.
		$this->assertContains( $visible_product->get_id(), $found_ids, 'Visible product should appear in search results' );

		// Assert that the hidden product is NOT in the results.
		$this->assertNotContains( $hidden_product->get_id(), $found_ids, $expected_message );

		// Cleanup.
		$wp_the_query = $previous_wp_the_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query     = $previous_wp_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$visible_product->delete( true );
		$hidden_product->delete( true );
	}

	/**
	 * Data provider for visibility exclusion tests.
	 *
	 * @return array
	 */
	public function visibility_exclusion_provider(): array {
		return array(
			'catalog visibility' => array( 'catalog', 'Product with exclude-from-search should not appear in search results' ),
			'hidden visibility'  => array( 'hidden', 'Product with hidden visibility should not appear in search results' ),
		);
	}
}
