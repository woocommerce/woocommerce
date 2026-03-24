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
}
