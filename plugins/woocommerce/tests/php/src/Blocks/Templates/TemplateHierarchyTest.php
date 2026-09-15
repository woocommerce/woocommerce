<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\Templates;

use Automattic\WooCommerce\Blocks\Templates\CartTemplate;
use Automattic\WooCommerce\Blocks\Templates\CheckoutTemplate;
use Automattic\WooCommerce\Blocks\Templates\ProductAttributeTemplate;
use Automattic\WooCommerce\Blocks\Templates\ProductCategoryTemplate;
use Automattic\WooCommerce\Blocks\Templates\ProductSearchResultsTemplate;
use Automattic\WooCommerce\Blocks\Templates\ProductTagTemplate;
use WP_UnitTestCase;

/**
 * Integration tests for WooCommerce block template hierarchy filters.
 */
class TemplateHierarchyTests extends WP_UnitTestCase {

	/**
	 * Original active theme stylesheet.
	 *
	 * @var string
	 */
	private $original_stylesheet;

	/**
	 * Original registered product attributes.
	 *
	 * @var mixed
	 */
	private $original_product_attributes;

	/**
	 * Set up isolated hooks and a block theme.
	 */
	protected function setUp(): void {
		parent::setUp();

		global $wc_product_attributes, $wp_filter;

		$this->original_stylesheet         = get_stylesheet();
		$this->original_product_attributes = $wc_product_attributes;

		// Detach the ambient hierarchy callbacks so each test sees only what the
		// template under it registers. _restore_hooks() puts them back afterwards.
		foreach ( array( 'search_template_hierarchy', 'page_template_hierarchy', 'taxonomy_template_hierarchy' ) as $hook_name ) {
			unset( $wp_filter[ $hook_name ] );
		}

		switch_theme( 'twentytwentytwo' );
		$this->assertTrue( wp_is_block_theme(), 'A block theme is required for hierarchy integration tests.' );
	}

	/**
	 * Restore the registered taxonomies, the attribute registry, and the theme.
	 *
	 * Everything else this class touches -- posts, terms, the two page-id options,
	 * the hierarchy hooks and the template caches -- goes back with the rollback,
	 * the hook restore and the cache flush the base class already performs.
	 */
	protected function tearDown(): void {
		global $wc_product_attributes;

		if ( taxonomy_exists( 'pa_hierarchy' ) ) {
			unregister_taxonomy( 'pa_hierarchy' );
		}
		$wc_product_attributes = $this->original_product_attributes;

		switch_theme( $this->original_stylesheet );

		parent::tearDown();
	}

	/**
	 * @testdox Product searches prepend the WooCommerce product search template.
	 */
	public function test_product_search_hierarchy(): void {
		$template = new ProductSearchResultsTemplate();
		$template->init();

		// go_to() on its own does not produce a product-search query -- the archive
		// flags come back false -- so the shape has to be built by hand. Nothing below
		// asserts these values back: that would just restate the arrangement. The
		// hierarchy assertion is the test.
		$this->go_to( '/?s=hoodie&post_type=product' );
		$GLOBALS['wp_query']->set( 'post_type', 'product' );
		$GLOBALS['wp_query']->is_search            = true;
		$GLOBALS['wp_query']->is_post_type_archive = true;
		$GLOBALS['wp_query']->is_archive           = true;
		$GLOBALS['wp_query']->is_404               = false;

		$this->assertSame(
			array( 'product-search-results', 'search.php', 'index.php' ),
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Applying the core hierarchy filter is the behavior under test.
			apply_filters( 'search_template_hierarchy', array( 'search.php', 'index.php' ) )
		);
	}

	/**
	 * @testdox Cart and Checkout pages prepend their classic and registered block template slugs.
	 */
	public function test_page_template_hierarchy(): void {
		$cart_page_id     = $this->create_page( 'hierarchy-test-cart' );
		$checkout_page_id = $this->create_page( 'hierarchy-test-checkout' );
		update_option( 'woocommerce_cart_page_id', $cart_page_id );
		update_option( 'woocommerce_checkout_page_id', $checkout_page_id );

		( new CartTemplate() )->init();
		( new CheckoutTemplate() )->init();

		$this->go_to( get_permalink( $cart_page_id ) );
		$this->assertSame(
			array( 'cart', 'page-cart', 'page.php', 'singular.php', 'index.php' ),
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Applying the core hierarchy filter is the behavior under test.
			apply_filters( 'page_template_hierarchy', array( 'page.php', 'singular.php', 'index.php' ) )
		);

		$this->go_to( get_permalink( $checkout_page_id ) );
		$this->assertSame(
			array( 'checkout', 'page-checkout', 'page.php', 'singular.php', 'index.php' ),
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Applying the core hierarchy filter is the behavior under test.
			apply_filters( 'page_template_hierarchy', array( 'page.php', 'singular.php', 'index.php' ) )
		);
	}

	/**
	 * @testdox Product category and tag hierarchies place Product Catalog immediately after their specific template.
	 *
	 * @dataProvider provide_product_taxonomies
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param string $specific_template Specific template slug.
	 */
	public function test_product_taxonomy_hierarchy( string $taxonomy, string $specific_template ): void {
		( new ProductCategoryTemplate() )->init();
		( new ProductTagTemplate() )->init();

		$term = wp_insert_term( 'Hierarchy test ' . $taxonomy, $taxonomy, array( 'slug' => 'hierarchy-test-' . $taxonomy ) );
		if ( is_wp_error( $term ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the exact fixture error in the test failure.
			throw new \RuntimeException( $term->get_error_message() );
		}
		$this->assertIsArray( $term );

		$term_link = get_term_link( (int) $term['term_id'], $taxonomy );
		if ( is_wp_error( $term_link ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the exact fixture error in the test failure.
			throw new \RuntimeException( $term_link->get_error_message() );
		}
		$this->assertIsString( $term_link );
		$this->go_to( $term_link );
		$this->assertTrue( is_tax( $taxonomy ), "The request must target {$taxonomy}." );

		$hierarchy = array( $specific_template . '.php', 'taxonomy.php', 'archive.php', 'index.php' );
		$this->assertSame(
			array( $specific_template . '.php', 'archive-product', 'taxonomy.php', 'archive.php', 'index.php' ),
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Applying the core hierarchy filter is the behavior under test.
			apply_filters( 'taxonomy_template_hierarchy', $hierarchy )
		);
	}

	/**
	 * Product taxonomy provider.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function provide_product_taxonomies(): array {
		return array(
			'product category' => array( 'product_cat', 'taxonomy-product_cat' ),
			'product tag'      => array( 'product_tag', 'taxonomy-product_tag' ),
		);
	}

	/**
	 * @testdox Product attributes use Product Catalog alone by default and prepend the specific template when customized.
	 */
	public function test_product_attribute_hierarchy(): void {
		global $wc_product_attributes;

		register_taxonomy( 'pa_hierarchy', array( 'product' ), array( 'public' => true ) );
		$wc_product_attributes['pa_hierarchy'] = (object) array( 'attribute_name' => 'hierarchy' );
		$template                              = new ProductAttributeTemplate();
		$template->init();

		$term = wp_insert_term( 'Hierarchy test attribute', 'pa_hierarchy', array( 'slug' => 'hierarchy-test-attribute' ) );
		if ( is_wp_error( $term ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the exact fixture error in the test failure.
			throw new \RuntimeException( $term->get_error_message() );
		}
		$this->assertIsArray( $term );
		$term_link = get_term_link( (int) $term['term_id'], 'pa_hierarchy' );
		if ( is_wp_error( $term_link ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the exact fixture error in the test failure.
			throw new \RuntimeException( $term_link->get_error_message() );
		}
		$this->assertIsString( $term_link );

		$this->go_to( $term_link );
		$this->assertTrue( is_tax( 'pa_hierarchy' ), 'The request must target the product attribute.' );
		$base_hierarchy = array( 'taxonomy-pa_hierarchy.php', 'taxonomy.php', 'archive.php', 'index.php' );
		$this->assertSame(
			array( 'taxonomy-pa_hierarchy.php', 'taxonomy.php', 'archive.php', 'archive-product', 'index.php' ),
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Applying the core hierarchy filter is the behavior under test.
			apply_filters( 'taxonomy_template_hierarchy', $base_hierarchy )
		);

		switch_theme( 'twentytwentyfour' );
		$template_id = $this->create_theme_template_post( 'taxonomy-product_attribute', get_stylesheet() );
		wp_cache_delete_multiple( array( 'wp_template-ids', 'wp_template_part-ids' ), 'woocommerce_blocks' );
		$this->go_to( $term_link );

		$this->assertSame(
			array( 'taxonomy-pa_hierarchy.php', 'taxonomy.php', 'archive.php', 'taxonomy-product_attribute', 'archive-product', 'index.php' ),
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Applying the core hierarchy filter is the behavior under test.
			apply_filters( 'taxonomy_template_hierarchy', $base_hierarchy )
		);
		$this->assertGreaterThan( 0, $template_id );
	}

	/**
	 * Create a published page.
	 *
	 * @param string $slug Page slug.
	 * @return int
	 */
	private function create_page( string $slug ): int {
		return self::factory()->post->create(
			array(
				'post_name'   => $slug,
				'post_status' => 'publish',
				'post_type'   => 'page',
			)
		);
	}

	/**
	 * Create a customized template for the current theme.
	 *
	 * @param string $slug Template slug.
	 * @param string $theme Theme term name.
	 * @return int
	 */
	private function create_theme_template_post( string $slug, string $theme ): int {
		$post_id = wp_insert_post(
			array(
				'post_name'    => $slug,
				'post_title'   => $slug,
				'post_content' => '<!-- wp:paragraph --><p>Customized attribute template</p><!-- /wp:paragraph -->',
				'post_status'  => 'publish',
				'post_type'    => 'wp_template',
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the exact fixture error in the test failure.
			throw new \RuntimeException( $post_id->get_error_message() );
		}
		$term = get_term_by( 'name', $theme, 'wp_theme', ARRAY_A );
		if ( ! $term ) {
			$term = wp_insert_term( $theme, 'wp_theme' );
			if ( is_wp_error( $term ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the exact fixture error in the test failure.
				throw new \RuntimeException( $term->get_error_message() );
			}
		}

		$result = wp_set_post_terms( $post_id, array( (int) $term['term_id'] ), 'wp_theme' );
		if ( is_wp_error( $result ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the exact fixture error in the test failure.
			throw new \RuntimeException( $result->get_error_message() );
		}

		return $post_id;
	}
}
