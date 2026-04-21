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
	 * @testdox Sitewide search includes or excludes products according to their catalog visibility setting.
	 *
	 * @dataProvider visibility_search_provider
	 *
	 * @param string $visibility       The catalog visibility setting to test.
	 * @param bool   $should_be_found  Whether the product is expected to appear in search results.
	 * @param string $expected_message The expected assertion message.
	 */
	public function test_search_respects_product_visibility( string $visibility, bool $should_be_found, string $expected_message ) {
		// Create a baseline product that should always appear in search.
		$visible_product = WC_Helper_Product::create_simple_product();
		$visible_product->set_name( 'Search Visible Product' );
		$visible_product->set_catalog_visibility( 'visible' );
		$visible_product->save();

		// Create the product under test with the visibility provided by the data provider.
		$test_product = WC_Helper_Product::create_simple_product();
		$test_product->set_name( 'Search Tested Product' );
		$test_product->set_catalog_visibility( $visibility );
		$test_product->save();

		// Save the previous main query and prepare for a new one.
		global $wp_the_query, $wp_query;
		$previous_wp_the_query = $wp_the_query;
		$previous_wp_query     = $wp_query;

		// Set the query as the main query before running so pre_get_posts fires with WC_Query's handler.
		$query        = new WP_Query();
		$wp_the_query = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query     = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$query->query( array( 's' => 'Search' ) );
		$found_ids = wp_list_pluck( $query->posts, 'ID' );

		$this->assertContains( $visible_product->get_id(), $found_ids, 'Visible product should always appear in search results' );

		if ( $should_be_found ) {
			$this->assertContains( $test_product->get_id(), $found_ids, $expected_message );
		} else {
			$this->assertNotContains( $test_product->get_id(), $found_ids, $expected_message );
		}

		// Cleanup.
		$wp_the_query = $previous_wp_the_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query     = $previous_wp_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$visible_product->delete( true );
		$test_product->delete( true );
	}

	/**
	 * Data provider for visibility-based search tests.
	 *
	 * @return array
	 */
	public function visibility_search_provider(): array {
		return array(
			'catalog visibility (shop only)' => array( 'catalog', false, 'Product with catalog-only visibility should not appear in search results' ),
			'hidden visibility'              => array( 'hidden', false, 'Product with hidden visibility should not appear in search results' ),
			'search visibility'              => array( 'search', true, 'Product with search-only visibility should appear in search results' ),
		);
	}

	/**
	 * @testdox Sitewide search continues to return regular posts and pages alongside the product visibility filter.
	 */
	public function test_search_includes_non_product_post_types() {
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_title'   => 'Search Regular Post',
				'post_content' => 'Body content referencing Search.',
			)
		);

		global $wp_the_query, $wp_query;
		$previous_wp_the_query = $wp_the_query;
		$previous_wp_query     = $wp_query;

		$query        = new WP_Query();
		$wp_the_query = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query     = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$query->query( array( 's' => 'Search' ) );
		$found_ids = wp_list_pluck( $query->posts, 'ID' );

		$this->assertContains( $post_id, $found_ids, 'Regular posts should still appear in sitewide search results' );

		$wp_the_query = $previous_wp_the_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query     = $previous_wp_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		wp_delete_post( $post_id, true );
	}
}
