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
	 * Data provider for search ordering tests.
	 *
	 * @return array[] Each entry: [ search query string, whether relevance ordering is expected, description ].
	 */
	public function data_provider_search_ordering(): array {
		return array(
			'normal search'              => array( 'shirt', true, 'Normal search should use relevance ordering' ),
			'exclusion-only search'      => array( '-condebug', false, 'Exclusion-only search should not use relevance ordering' ),
			'empty search'               => array( '', false, 'Empty search should not use relevance ordering' ),
			'multiple exclusion terms'   => array( '-foo+-bar', false, 'Multiple exclusion terms should not use relevance ordering' ),
			'mixed positive + exclusion' => array( 'shirt+-condebug', true, 'Mixed search with positive terms should use relevance ordering' ),
			'bare dash'                  => array( '-', false, 'Bare dash search should not use relevance ordering' ),
			'comma-separated mixed'      => array( '-foo,bar', true, 'Comma-separated search with positive terms should use relevance ordering' ),
		);
	}

	/**
	 * @testdox Ordering args: $description.
	 * @dataProvider data_provider_search_ordering
	 *
	 * @param string $search           The search query string.
	 * @param bool   $expect_relevance Whether relevance ordering is expected.
	 * @param string $description      Test case description.
	 */
	public function test_get_catalog_ordering_args_search_ordering( string $search, bool $expect_relevance, string $description ): void {
		$sut = new WC_Query();

		$this->go_to( '/?s=' . rawurlencode( $search ) . '&post_type=product' );

		$result = $sut->get_catalog_ordering_args();

		if ( $expect_relevance ) {
			$this->assertSame( 'relevance', $result['orderby'], $description );
		} else {
			$this->assertNotEquals( 'relevance', $result['orderby'], $description );
		}
	}

	/**
	 * @testdox Ordering args should respect the wp_query_search_exclusion_prefix filter.
	 */
	public function test_get_catalog_ordering_args_respects_custom_exclusion_prefix(): void {
		$sut = new WC_Query();

		$custom_prefix = static function () {
			return '!';
		};
		add_filter( 'wp_query_search_exclusion_prefix', $custom_prefix );

		$this->go_to( '/?s=!foo&post_type=product' );

		$result = $sut->get_catalog_ordering_args();

		remove_filter( 'wp_query_search_exclusion_prefix', $custom_prefix );

		$this->assertNotEquals( 'relevance', $result['orderby'], 'Exclusion-only search with custom prefix should not use relevance ordering' );
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
	 * @testdox Sitewide search excludes hidden products while continuing to return regular posts.
	 */
	public function test_search_excludes_hidden_products_but_keeps_other_post_types() {
		$hidden_product = WC_Helper_Product::create_simple_product();
		$hidden_product->set_name( 'Search Hidden Companion Product' );
		$hidden_product->set_catalog_visibility( 'hidden' );
		$hidden_product->save();

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
		$this->assertNotContains( $hidden_product->get_id(), $found_ids, 'Hidden products should be filtered out of sitewide search results' );

		$wp_the_query = $previous_wp_the_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query     = $previous_wp_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		wp_delete_post( $post_id, true );
		$hidden_product->delete( true );
	}

	/**
	 * @testdox A tax_query set by another plugin or hook before WC_Query's pre_get_posts survives the visibility merge.
	 */
	public function test_search_preserves_existing_tax_query() {
		$existing_clause = array(
			'taxonomy' => 'category',
			'field'    => 'slug',
			'terms'    => array( 'uncategorized' ),
		);

		// Hook at priority 5 so it runs before WC_Query::pre_get_posts (default priority 10).
		$hook = function ( $q ) use ( $existing_clause ) {
			if ( $q->is_search() ) {
				$q->set( 'tax_query', array( $existing_clause ) );
			}
		};
		add_action( 'pre_get_posts', $hook, 5 );

		global $wp_the_query, $wp_query;
		$previous_wp_the_query = $wp_the_query;
		$previous_wp_query     = $wp_query;

		$query        = new WP_Query();
		$wp_the_query = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query     = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$query->query( array( 's' => 'Search' ) );

		$tax_query = $query->get( 'tax_query' );
		$this->assertIsArray( $tax_query, 'Tax query should be an array after WC_Query merges its clause.' );
		$this->assertContains( $existing_clause, $tax_query, 'Pre-existing tax_query clause should survive the merge.' );

		$product_visibility_terms = wc_get_product_visibility_term_ids();
		$exclude_term_id          = (int) $product_visibility_terms['exclude-from-search'];
		$visibility_clause        = null;
		foreach ( $tax_query as $clause ) {
			if ( is_array( $clause ) && isset( $clause['taxonomy'] ) && 'product_visibility' === $clause['taxonomy'] ) {
				$visibility_clause = $clause;
				break;
			}
		}
		$this->assertNotNull( $visibility_clause, 'WC_Query should append the product_visibility exclusion clause to the existing tax_query.' );
		$this->assertSame( 'term_taxonomy_id', $visibility_clause['field'], 'Visibility clause should match by term_taxonomy_id.' );
		$this->assertSame( array( $exclude_term_id ), $visibility_clause['terms'], 'Visibility clause should target the exclude-from-search term.' );
		$this->assertSame( 'NOT IN', $visibility_clause['operator'], 'Visibility clause should use the NOT IN operator.' );

		remove_action( 'pre_get_posts', $hook, 5 );
		$wp_the_query = $previous_wp_the_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query     = $previous_wp_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}
