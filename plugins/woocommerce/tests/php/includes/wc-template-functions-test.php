<?php
declare( strict_types = 1 );

/**
 * Tests for wc-template-functions.php.
 *
 * @package WooCommerce\Tests\Includes
 */
class WC_Template_Functions_Tests extends \WC_Unit_Test_Case {
	/**
	 * Render the loop add-to-cart template for a product.
	 *
	 * @param WC_Product $test_product Product to render.
	 * @return string Rendered template markup.
	 */
	private function render_loop_add_to_cart( WC_Product $test_product ): string {
		global $product;

		$previous_product = $product;
		$product          = $test_product;
		$buffer_level     = ob_get_level();

		ob_start();
		try {
			woocommerce_template_loop_add_to_cart();

			return (string) ob_get_clean();
		} finally {
			while ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}
			$product = $previous_product;
		}
	}

	/**
	 * Helper: create a parent product category with child categories and products.
	 *
	 * @return int Parent category term ID.
	 */
	private function create_category_tree(): int {
		$parent = wp_insert_term( 'Test Parent', 'product_cat' );
		if ( is_wp_error( $parent ) ) {
			throw new \RuntimeException( esc_html( $parent->get_error_message() ) );
		}
		$parent_id = $parent['term_id'];

		update_term_meta( $parent_id, 'display_type', 'both' );

		for ( $i = 1; $i <= 3; $i++ ) {
			$child = wp_insert_term(
				"Test Child $i",
				'product_cat',
				array( 'parent' => $parent_id )
			);
			if ( is_wp_error( $child ) ) {
				throw new \RuntimeException( esc_html( $child->get_error_message() ) );
			}

			$product = \WC_Helper_Product::create_simple_product();
			$product->set_category_ids( array( $child['term_id'] ) );
			$product->save();
		}

		wp_update_term_count_now(
			get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'fields'     => 'ids',
					'hide_empty' => 0,
				)
			),
			'product_cat'
		);

		return $parent_id;
	}

	/**
	 * Clean up cache between tests.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_cache_flush();
	}

	/**
	 * @testdox Product archive titles fall back to Shop when the configured Shop page is missing.
	 */
	public function test_product_archive_title_falls_back_to_shop_when_shop_page_is_missing(): void {
		global $wp_query, $wp_the_query;

		$missing_option_sentinel = new stdClass();
		$previous_shop_page_id   = get_option( 'woocommerce_shop_page_id', $missing_option_sentinel );
		$previous_wp_query       = $wp_query;
		$previous_wp_the_query   = $wp_the_query;
		$shop_page_id            = 0;

		try {
			$shop_page_id = self::factory()->post->create(
				array(
					'post_type'   => 'page',
					'post_status' => 'publish',
					'post_title'  => 'Retired catalog',
				)
			);
			update_option( 'woocommerce_shop_page_id', $shop_page_id );
			wp_delete_post( $shop_page_id, true );

			$query                       = new WP_Query( array( 'post_type' => 'product' ) );
			$query->is_post_type_archive = true;
			$query->is_archive           = true;
			$query->is_tax               = false;
			$query->is_home              = false;
			$wp_query                    = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$wp_the_query                = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			$this->assertTrue( is_shop(), 'The query must represent the product archive.' );
			$this->assertSame( '', get_the_title( $shop_page_id ), 'The configured Shop page must be missing.' );
			$this->assertSame( 10, has_filter( 'post_type_archive_title', 'wc_update_product_archive_title' ) );

			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- WordPress core owns this hook; the test exercises WooCommerce's registered callback.
			$title = apply_filters( 'post_type_archive_title', 'Products', 'product' );

			$this->assertSame( __( 'Shop', 'woocommerce' ), $title );
		} finally {
			if ( $missing_option_sentinel === $previous_shop_page_id ) {
				delete_option( 'woocommerce_shop_page_id' );
			} else {
				update_option( 'woocommerce_shop_page_id', $previous_shop_page_id );
			}
			$wp_query     = $previous_wp_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$wp_the_query = $previous_wp_the_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			if ( $shop_page_id && get_post( $shop_page_id ) ) {
				wp_delete_post( $shop_page_id, true );
			}
		}
	}

	/**
	 * @testdox woocommerce_get_product_subcategories caches results under the expected key.
	 */
	public function test_subcategories_are_cached_under_expected_key(): void {
		$parent_id = $this->create_category_tree();
		$cache_key = 'product-category-hierarchy-' . $parent_id;

		// Cache should be empty before the call.
		$this->assertFalse( wp_cache_get( $cache_key, 'product_cat' ) );

		$result = woocommerce_get_product_subcategories( $parent_id );

		// Cache should be populated after the call.
		$cached = wp_cache_get( $cache_key, 'product_cat' );
		$this->assertNotFalse( $cached );
		$this->assertCount( 3, $cached );
		$this->assertSame( $result, $cached );
	}

	/**
	 * @testdox woocommerce_get_product_subcategories does not cache when taxonomy is cleared by filter.
	 */
	public function test_cache_is_skipped_when_taxonomy_is_cleared(): void {
		$parent_id = $this->create_category_tree();
		$cache_key = 'product-category-hierarchy-' . $parent_id;

		$filter = function ( $args ) {
			$args['taxonomy'] = '';
			return $args;
		};
		add_filter( 'woocommerce_product_subcategories_args', $filter );

		$result = woocommerce_get_product_subcategories( $parent_id );

		// Cache should remain empty because taxonomy was cleared.
		$this->assertFalse( wp_cache_get( $cache_key, 'product_cat' ) );
		// Result should be empty too (query with empty taxonomy returns nothing).
		$this->assertEmpty( $result );

		remove_filter( 'woocommerce_product_subcategories_args', $filter );
	}

	/**
	 * @testdox woocommerce_get_product_subcategories does not cache when taxonomy is missing after filter.
	 */
	public function test_cache_is_skipped_when_taxonomy_is_missing(): void {
		$parent_id = $this->create_category_tree();
		$cache_key = 'product-category-hierarchy-' . $parent_id;

		$filter = function ( $args ) {
			unset( $args['taxonomy'] );
			return $args;
		};
		add_filter( 'woocommerce_product_subcategories_args', $filter );

		woocommerce_get_product_subcategories( $parent_id );

		// Cache should remain empty because taxonomy was removed.
		$this->assertFalse( wp_cache_get( $cache_key, 'product_cat' ) );

		remove_filter( 'woocommerce_product_subcategories_args', $filter );
	}

	/**
	 * @testdox woocommerce_get_product_subcategories caches normally after filter is removed.
	 */
	public function test_cache_works_normally_after_filter_removed(): void {
		$parent_id = $this->create_category_tree();
		$cache_key = 'product-category-hierarchy-' . $parent_id;

		// First call with filter that clears taxonomy.
		$filter = function ( $args ) {
			$args['taxonomy'] = '';
			return $args;
		};
		add_filter( 'woocommerce_product_subcategories_args', $filter );
		woocommerce_get_product_subcategories( $parent_id );
		$this->assertFalse( wp_cache_get( $cache_key, 'product_cat' ) );
		remove_filter( 'woocommerce_product_subcategories_args', $filter );

		// Second call without filter should cache normally.
		$result = woocommerce_get_product_subcategories( $parent_id );
		$cached = wp_cache_get( $cache_key, 'product_cat' );
		$this->assertNotFalse( $cached );
		$this->assertCount( 3, $cached );
	}

	/**
	 * @testdox Loop buttons do not add nofollow to product permalink links.
	 */
	public function test_loop_button_product_permalink_does_not_include_nofollow(): void {
		$product = WC_Helper_Product::create_variation_product();
		$markup  = $this->render_loop_add_to_cart( $product );

		$this->assertStringContainsString( 'href="' . esc_url( $product->get_permalink() ) . '"', $markup );
		$this->assertStringContainsString( 'rel=""', $markup );
	}

	/**
	 * @testdox Loop button filter arguments retain an empty rel attribute for product permalink links.
	 */
	public function test_loop_button_product_permalink_filter_args_include_empty_rel(): void {
		$product       = WC_Helper_Product::create_variation_product();
		$filtered_args = null;
		$filter        = static function ( array $args ) use ( &$filtered_args ): array {
			$filtered_args = $args;
			return $args;
		};

		add_filter( 'woocommerce_loop_add_to_cart_args', $filter );
		try {
			$this->render_loop_add_to_cart( $product );
		} finally {
			remove_filter( 'woocommerce_loop_add_to_cart_args', $filter );
		}

		$this->assertIsArray( $filtered_args );
		$this->assertArrayHasKey( 'rel', $filtered_args['attributes'] );
		$this->assertSame( '', $filtered_args['attributes']['rel'] );
	}

	/**
	 * @testdox Loop buttons retain nofollow on direct add-to-cart links.
	 */
	public function test_loop_button_direct_add_to_cart_link_retains_nofollow(): void {
		$product = WC_Helper_Product::create_simple_product();
		$markup  = $this->render_loop_add_to_cart( $product );

		$this->assertStringContainsString( 'rel="nofollow"', $markup );
	}

	/**
	 * @testdox Loop buttons retain nofollow on external product links.
	 */
	public function test_loop_button_external_product_link_retains_nofollow(): void {
		$product = WC_Helper_Product::create_external_product();
		$markup  = $this->render_loop_add_to_cart( $product );

		$this->assertStringContainsString( 'rel="nofollow"', $markup );
	}

	/**
	 * @testdox Pagination defaults link page one to the canonical archive URL.
	 */
	public function test_pagination_defaults_link_page_one_to_canonical_archive_url(): void {
		global $wp_rewrite;

		$original_permalink_structure = $wp_rewrite->permalink_structure;
		$buffer_level                 = ob_get_level();
		$previous_loop                = $GLOBALS['woocommerce_loop'] ?? null;
		$pagination_args              = null;
		$get_pagenum_link_filter      = static function ( $link, $pagenum ) {
			return 'https://example.test/shop/page/' . $pagenum . '/';
		};
		$pagination_args_filter       = static function ( $args ) use ( &$pagination_args ) {
			$pagination_args = $args;
			return $args;
		};

		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		wc_setup_loop(
			array(
				'is_shortcode' => false,
				'is_paginated' => true,
				'total'        => 2,
				'total_pages'  => 2,
				'current_page' => 2,
			)
		);
		add_filter( 'get_pagenum_link', $get_pagenum_link_filter, 10, 2 );
		add_filter( 'woocommerce_pagination_args', $pagination_args_filter );

		ob_start();
		try {
			woocommerce_pagination();
			$markup = (string) ob_get_clean();
		} finally {
			while ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}
			remove_filter( 'get_pagenum_link', $get_pagenum_link_filter, 10 );
			remove_filter( 'woocommerce_pagination_args', $pagination_args_filter );
			$wp_rewrite->set_permalink_structure( $original_permalink_structure );
			if ( null === $previous_loop ) {
				wc_reset_loop();
			} else {
				$GLOBALS['woocommerce_loop'] = $previous_loop;
			}
		}

		$this->assertSame( 'https://example.test/shop/%_%', $pagination_args['base'] );
		$this->assertSame( 'page/%#%/', $pagination_args['format'] );
		$this->assertStringContainsString( 'href="https://example.test/shop/"', $markup );
	}
}
