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
	 * Original page option states.
	 *
	 * @var array<string, array{exists: bool, value: mixed}>
	 */
	private $option_states = array();

	/**
	 * Original hook objects.
	 *
	 * @var array<string, mixed>
	 */
	private $hook_states = array();

	/**
	 * Posts created by the tests.
	 *
	 * @var int[]
	 */
	private $post_ids = array();

	/**
	 * Terms created by the tests.
	 *
	 * @var array<string, int[]>
	 */
	private $term_ids = array();

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
		$this->option_states               = array(
			'woocommerce_cart_page_id'     => $this->snapshot_option( 'woocommerce_cart_page_id' ),
			'woocommerce_checkout_page_id' => $this->snapshot_option( 'woocommerce_checkout_page_id' ),
		);

		foreach ( array( 'search_template_hierarchy', 'page_template_hierarchy', 'taxonomy_template_hierarchy' ) as $hook_name ) {
			$this->hook_states[ $hook_name ] = array_key_exists( $hook_name, $wp_filter ) ? $wp_filter[ $hook_name ] : null;
			unset( $wp_filter[ $hook_name ] );
		}

		switch_theme( 'twentytwentytwo' );
		$this->assertTrue( wp_is_block_theme(), 'A block theme is required for hierarchy integration tests.' );
	}

	/**
	 * Restore hooks, globals, options, theme, posts, terms, and taxonomies.
	 */
	protected function tearDown(): void {
		global $wc_product_attributes, $wp_filter;

		foreach ( array_reverse( $this->post_ids ) as $post_id ) {
			wp_delete_post( $post_id, true );
		}

		foreach ( $this->term_ids as $taxonomy => $term_ids ) {
			foreach ( array_reverse( $term_ids ) as $term_id ) {
				wp_delete_term( $term_id, $taxonomy );
			}
		}

		if ( taxonomy_exists( 'pa_slice038' ) ) {
			unregister_taxonomy( 'pa_slice038' );
		}
		$wc_product_attributes = $this->original_product_attributes;

		foreach ( $this->option_states as $option_name => $state ) {
			if ( $state['exists'] ) {
				update_option( $option_name, $state['value'] );
			} else {
				delete_option( $option_name );
			}
		}

		foreach ( $this->hook_states as $hook_name => $hook ) {
			if ( null === $hook ) {
				unset( $wp_filter[ $hook_name ] );
			} else {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact pre-test hook object.
				$wp_filter[ $hook_name ] = $hook;
			}
		}

		wp_cache_delete_multiple( array( 'wp_template-ids', 'wp_template_part-ids' ), 'woocommerce_blocks' );
		switch_theme( $this->original_stylesheet );

		parent::tearDown();
	}

	/**
	 * @testdox Product searches prepend the WooCommerce product search template.
	 */
	public function test_product_search_hierarchy(): void {
		$template = new ProductSearchResultsTemplate();
		$template->init();

		$this->go_to( '/?s=hoodie&post_type=product' );
		$GLOBALS['wp_query']->set( 'post_type', 'product' );
		$GLOBALS['wp_query']->is_search            = true;
		$GLOBALS['wp_query']->is_post_type_archive = true;
		$GLOBALS['wp_query']->is_archive           = true;
		$GLOBALS['wp_query']->is_404               = false;
		$this->assertTrue( is_search(), 'The request must be a search.' );
		$this->assertSame( 'product', get_query_var( 'post_type' ), 'The request must carry the product post type.' );
		$this->assertNotNull( get_post_type_object( get_query_var( 'post_type' ) ), 'The product post type must be registered.' );
		$this->assertTrue( $GLOBALS['wp_query']->is_post_type_archive, 'The current global query must be an archive.' );
		$this->assertTrue( is_post_type_archive( 'product' ), 'The request must target the product archive.' );

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
		$cart_page_id     = $this->create_page( 'slice-038-cart' );
		$checkout_page_id = $this->create_page( 'slice-038-checkout' );
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

		$term = wp_insert_term( 'Slice 038 ' . $taxonomy, $taxonomy, array( 'slug' => 'slice-038-' . $taxonomy ) );
		if ( is_wp_error( $term ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the exact fixture error in the test failure.
			throw new \RuntimeException( $term->get_error_message() );
		}
		$this->assertIsArray( $term );
		$this->term_ids[ $taxonomy ][] = (int) $term['term_id'];

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

		register_taxonomy( 'pa_slice038', array( 'product' ), array( 'public' => true ) );
		$wc_product_attributes['pa_slice038'] = (object) array( 'attribute_name' => 'slice038' );
		$template                             = new ProductAttributeTemplate();
		$template->init();

		$term = wp_insert_term( 'Slice 038 attribute', 'pa_slice038', array( 'slug' => 'slice-038-attribute' ) );
		if ( is_wp_error( $term ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the exact fixture error in the test failure.
			throw new \RuntimeException( $term->get_error_message() );
		}
		$this->assertIsArray( $term );
		$this->term_ids['pa_slice038'][] = (int) $term['term_id'];
		$term_link                       = get_term_link( (int) $term['term_id'], 'pa_slice038' );
		if ( is_wp_error( $term_link ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the exact fixture error in the test failure.
			throw new \RuntimeException( $term_link->get_error_message() );
		}
		$this->assertIsString( $term_link );

		$this->go_to( $term_link );
		$this->assertTrue( is_tax( 'pa_slice038' ), 'The request must target the product attribute.' );
		$base_hierarchy = array( 'taxonomy-pa_slice038.php', 'taxonomy.php', 'archive.php', 'index.php' );
		$this->assertSame(
			array( 'taxonomy-pa_slice038.php', 'taxonomy.php', 'archive.php', 'archive-product', 'index.php' ),
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Applying the core hierarchy filter is the behavior under test.
			apply_filters( 'taxonomy_template_hierarchy', $base_hierarchy )
		);

		switch_theme( 'twentytwentyfour' );
		$template_id = $this->create_theme_template_post( 'taxonomy-product_attribute', get_stylesheet() );
		wp_cache_delete_multiple( array( 'wp_template-ids', 'wp_template_part-ids' ), 'woocommerce_blocks' );
		$this->go_to( $term_link );

		$this->assertSame(
			array( 'taxonomy-pa_slice038.php', 'taxonomy.php', 'archive.php', 'taxonomy-product_attribute', 'archive-product', 'index.php' ),
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Applying the core hierarchy filter is the behavior under test.
			apply_filters( 'taxonomy_template_hierarchy', $base_hierarchy )
		);
		$this->assertGreaterThan( 0, $template_id );
	}

	/**
	 * Snapshot exact option existence and value.
	 *
	 * @param string $option_name Option name.
	 * @return array{exists: bool, value: mixed}
	 */
	private function snapshot_option( string $option_name ): array {
		$sentinel = new \stdClass();
		$value    = get_option( $option_name, $sentinel );

		return array(
			'exists' => $sentinel !== $value,
			'value'  => $value,
		);
	}

	/**
	 * Create a published page and track it for cleanup.
	 *
	 * @param string $slug Page slug.
	 * @return int
	 */
	private function create_page( string $slug ): int {
		$post_id          = self::factory()->post->create(
			array(
				'post_name'   => $slug,
				'post_status' => 'publish',
				'post_type'   => 'page',
			)
		);
		$this->post_ids[] = $post_id;

		return $post_id;
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
		$this->post_ids[] = $post_id;

		$term = get_term_by( 'name', $theme, 'wp_theme', ARRAY_A );
		if ( ! $term ) {
			$term = wp_insert_term( $theme, 'wp_theme' );
			if ( is_wp_error( $term ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the exact fixture error in the test failure.
				throw new \RuntimeException( $term->get_error_message() );
			}
			$this->term_ids['wp_theme'][] = (int) $term['term_id'];
		}

		$result = wp_set_post_terms( $post_id, array( (int) $term['term_id'] ), 'wp_theme' );
		if ( is_wp_error( $result ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the exact fixture error in the test failure.
			throw new \RuntimeException( $result->get_error_message() );
		}

		return $post_id;
	}
}
