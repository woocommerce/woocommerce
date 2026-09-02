<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks;

use Automattic\WooCommerce\Blocks\BlockTemplatesController;
use Automattic\WooCommerce\Blocks\BlockTemplatesRegistry;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use WP_Block_Template;
use WP_UnitTestCase;

/**
 * Integration tests for the block templates controller.
 */
class BlockTemplatesControllerTest extends WP_UnitTestCase {

	/**
	 * Original active theme stylesheet.
	 *
	 * @var string
	 */
	private $original_stylesheet;

	/**
	 * Posts created by the test.
	 *
	 * @var int[]
	 */
	private $post_ids = array();

	/**
	 * Terms created by the test.
	 *
	 * @var array<string, int[]>
	 */
	private $term_ids = array();

	/**
	 * Set up a block theme and isolated template caches.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->original_stylesheet = get_stylesheet();
		switch_theme( 'twentytwentytwo' );
		wp_cache_delete_multiple( array( 'wp_template-ids', 'wp_template_part-ids' ), 'woocommerce_blocks' );
	}

	/**
	 * Restore theme, posts, terms, and object caches.
	 */
	protected function tearDown(): void {
		foreach ( array_reverse( $this->post_ids ) as $post_id ) {
			wp_delete_post( $post_id, true );
		}

		foreach ( $this->term_ids as $taxonomy => $term_ids ) {
			foreach ( array_reverse( $term_ids ) as $term_id ) {
				wp_delete_term( $term_id, $taxonomy );
			}
		}

		wp_cache_delete_multiple( array( 'wp_template-ids', 'wp_template_part-ids' ), 'woocommerce_blocks' );
		switch_theme( $this->original_stylesheet );

		parent::tearDown();
	}

	/**
	 * @testdox WooCommerce registers its default templates and exposes exact file-backed template and part objects.
	 */
	public function test_registered_template_catalog_and_directories(): void {
		global $wp_filter;

		$wp_registry       = \WP_Block_Templates_Registry::get_instance();
		$registered_before = $wp_registry->get_all_registered();
		$hooks_before      = array();
		foreach ( $wp_filter as $hook_name => $hook ) {
			$hooks_before[ $hook_name ] = clone $hook;
		}

		$registered_names = array(
			'woocommerce//archive-product',
			'woocommerce//coming-soon',
			'woocommerce//order-confirmation',
			'woocommerce//page-cart',
			'woocommerce//page-checkout',
			'woocommerce//product-search-results',
			'woocommerce//single-product',
		);

		foreach ( $registered_names as $registered_name ) {
			$this->assertArrayNotHasKey( $registered_name, $registered_before, "{$registered_name} must start unregistered." );
		}

		try {
			( new BlockTemplatesRegistry() )->init();

			foreach ( $registered_names as $registered_name ) {
				$template = $wp_registry->get_registered( $registered_name );
				$this->assertInstanceOf( WP_Block_Template::class, $template );
				$this->assertSame( get_stylesheet() . '//' . $template->slug, $template->id );
				$this->assertSame( get_stylesheet(), $template->theme );
				$this->assertSame( 'woocommerce', $template->plugin );
				$this->assertSame( 'plugin', $template->source );
				$this->assertSame( 'plugin', $template->origin );
				$this->assertSame( 'wp_template', $template->type );
				$this->assertSame( 'publish', $template->status );
				$this->assertNotSame( '', trim( $template->content ) );
			}

			$controller     = new BlockTemplatesController();
			$template_slugs = array(
				'archive-product',
				'product-search-results',
				'taxonomy-product_attribute',
				'single-product',
				'page-cart',
				'page-checkout',
				'order-confirmation',
			);
			foreach ( $template_slugs as $template_slug ) {
				$this->assert_file_template_contract( $controller, $template_slug, 'wp_template' );
			}

			foreach ( array( 'mini-cart', 'external-product-add-to-cart-with-options', 'checkout-header' ) as $template_part_slug ) {
				$this->assert_file_template_contract( $controller, $template_part_slug, 'wp_template_part' );
			}

			$plugin_root = dirname( __DIR__, 4 );
			$this->assertFileExists( $plugin_root . '/templates/templates/archive-product.html' );
			$this->assertFileExists( $plugin_root . '/templates/parts/external-product-add-to-cart-with-options.html' );
			$this->assertFileExists( $plugin_root . '/tests/e2e/themes/blocks/theme-with-woo-templates/block-templates/archive-product.html' );
			$this->assertFileExists( $plugin_root . '/tests/e2e/themes/blocks/theme-with-woo-templates/block-template-parts/external-product-add-to-cart-with-options.html' );
		} finally {
			$registered_after = $wp_registry->get_all_registered();
			foreach ( array_diff( array_keys( $registered_after ), array_keys( $registered_before ) ) as $registered_name ) {
				unregister_block_template( $registered_name );
			}

			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact pre-test hook registry.
			$wp_filter = $hooks_before;
		}
	}

	/**
	 * @testdox Theme customizations suppress duplicate Woo customizations until the theme customization is deleted.
	 */
	public function test_resolves_saved_template_and_part_precedence(): void {
		$theme_slug = get_stylesheet();
		$controller = new BlockTemplatesController();

		$template_woo_id        = $this->create_template_post(
			'archive-product',
			'Woo customized catalog',
			'wp_template',
			BlockTemplateUtils::PLUGIN_SLUG
		);
		$template_theme_id      = $this->create_template_post(
			'archive-product',
			'Theme customized catalog',
			'wp_template',
			$theme_slug
		);
		$template_current_id    = $this->create_template_post(
			'page-cart',
			'Woo current-origin cart',
			'wp_template',
			BlockTemplateUtils::PLUGIN_SLUG
		);
		$template_deprecated_id = $this->create_template_post(
			'product-search-results',
			'Woo deprecated-origin search',
			'wp_template',
			BlockTemplateUtils::DEPRECATED_PLUGIN_SLUG
		);

		$theme_template         = $this->build_template_result( $template_theme_id );
		$theme_template->origin = 'theme';
		$template_query         = array(
			'slug__in' => array( 'archive-product', 'page-cart', 'product-search-results' ),
		);

		$template_results = $controller->add_db_templates_with_woo_slug(
			array( $theme_template ),
			$template_query,
			'wp_template'
		);

		$this->assert_same_template_sequence(
			$template_results,
			$controller->add_db_templates_with_woo_slug( array( $theme_template ), $template_query, 'wp_template' )
		);
		$this->assertSame(
			array( 'archive-product', 'page-cart', 'product-search-results' ),
			$this->sorted_slugs( $template_results )
		);
		$this->assert_template_contract(
			$this->find_template( $template_results, 'archive-product' ),
			$template_theme_id,
			$theme_slug,
			'theme',
			'Theme customized catalog',
			'wp_template'
		);
		$this->assert_template_contract(
			$this->find_template( $template_results, 'page-cart' ),
			$template_current_id,
			BlockTemplateUtils::PLUGIN_SLUG,
			'plugin',
			'Woo current-origin cart',
			'wp_template'
		);
		$this->assert_template_contract(
			$this->find_template( $template_results, 'product-search-results' ),
			$template_deprecated_id,
			BlockTemplateUtils::DEPRECATED_PLUGIN_SLUG,
			'plugin',
			'Woo deprecated-origin search',
			'wp_template'
		);

		wp_delete_post( $template_theme_id, true );
		$template_results_without_theme = $controller->add_db_templates_with_woo_slug(
			array(),
			$template_query,
			'wp_template'
		);
		$this->assert_template_contract(
			$this->find_template( $template_results_without_theme, 'archive-product' ),
			$template_woo_id,
			BlockTemplateUtils::PLUGIN_SLUG,
			'plugin',
			'Woo customized catalog',
			'wp_template'
		);

		$part_woo_id        = $this->create_template_post(
			'external-product-add-to-cart-with-options',
			'Woo customized external options',
			'wp_template_part',
			BlockTemplateUtils::PLUGIN_SLUG
		);
		$part_theme_id      = $this->create_template_post(
			'external-product-add-to-cart-with-options',
			'Theme customized external options',
			'wp_template_part',
			$theme_slug
		);
		$part_deprecated_id = $this->create_template_post(
			'mini-cart',
			'Woo deprecated-origin mini-cart',
			'wp_template_part',
			BlockTemplateUtils::DEPRECATED_PLUGIN_SLUG
		);

		$theme_part         = $this->build_template_result( $part_theme_id );
		$theme_part->origin = 'theme';
		$part_query         = array(
			'slug__in' => array( 'external-product-add-to-cart-with-options', 'mini-cart' ),
		);
		$part_results       = $controller->add_db_templates_with_woo_slug(
			array( $theme_part ),
			$part_query,
			'wp_template_part'
		);

		$this->assert_same_template_sequence(
			$part_results,
			$controller->add_db_templates_with_woo_slug( array( $theme_part ), $part_query, 'wp_template_part' )
		);
		$this->assertSame(
			array( 'external-product-add-to-cart-with-options', 'mini-cart' ),
			$this->sorted_slugs( $part_results )
		);
		$this->assert_template_contract(
			$this->find_template( $part_results, 'external-product-add-to-cart-with-options' ),
			$part_theme_id,
			$theme_slug,
			'theme',
			'Theme customized external options',
			'wp_template_part'
		);
		$this->assert_template_contract(
			$this->find_template( $part_results, 'mini-cart' ),
			$part_deprecated_id,
			BlockTemplateUtils::DEPRECATED_PLUGIN_SLUG,
			'plugin',
			'Woo deprecated-origin mini-cart',
			'wp_template_part'
		);

		wp_delete_post( $part_theme_id, true );
		$part_results_without_theme = $controller->add_db_templates_with_woo_slug(
			array(),
			$part_query,
			'wp_template_part'
		);
		$this->assert_template_contract(
			$this->find_template( $part_results_without_theme, 'external-product-add-to-cart-with-options' ),
			$part_woo_id,
			BlockTemplateUtils::PLUGIN_SLUG,
			'plugin',
			'Woo customized external options',
			'wp_template_part'
		);
	}

	/**
	 * Build a template result from a fixture post with explicit type guards.
	 *
	 * @param int $post_id Template post ID.
	 * @return WP_Block_Template
	 */
	private function build_template_result( int $post_id ): WP_Block_Template {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			throw new \RuntimeException( 'The template fixture post is unavailable.' );
		}

		$template = BlockTemplateUtils::build_template_result_from_post( $post );
		if ( is_wp_error( $template ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the exact fixture error in the test failure.
			throw new \RuntimeException( $template->get_error_message() );
		}

		return $template;
	}

	/**
	 * Assert a WooCommerce file-backed template contract.
	 *
	 * @param BlockTemplatesController $controller Controller instance.
	 * @param string                   $slug Template slug.
	 * @param string                   $type Template type.
	 */
	private function assert_file_template_contract( BlockTemplatesController $controller, string $slug, string $type ): void {
		$template = $controller->get_block_file_template(
			null,
			BlockTemplateUtils::PLUGIN_SLUG . '//' . $slug,
			$type
		);

		$this->assertInstanceOf( WP_Block_Template::class, $template );
		$this->assertSame( BlockTemplateUtils::PLUGIN_SLUG . '//' . $slug, $template->id );
		$this->assertSame( BlockTemplateUtils::PLUGIN_SLUG, $template->theme );
		$this->assertSame( $slug, $template->slug );
		$this->assertSame( $type, $template->type );
		$this->assertSame( 'plugin', $template->source );
		$this->assertSame( 'plugin', $template->origin );
		$this->assertNotSame( '', trim( $template->content ) );
	}

	/**
	 * Create a real saved block template or template part.
	 *
	 * @param string $slug Template slug.
	 * @param string $content Template content.
	 * @param string $type Template post type.
	 * @param string $theme Theme term name.
	 * @return int Post ID.
	 */
	private function create_template_post( string $slug, string $content, string $type, string $theme ): int {
		$post_id = wp_insert_post(
			array(
				'post_name'    => $slug,
				'post_title'   => $slug,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => $type,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the exact fixture error in the test failure.
			throw new \RuntimeException( $post_id->get_error_message() );
		}

		$this->post_ids[] = $post_id;
		$this->assign_term( $post_id, $theme, 'wp_theme' );

		if ( 'wp_template_part' === $type ) {
			$this->assign_term( $post_id, 'general', 'wp_template_part_area' );
		}

		return $post_id;
	}

	/**
	 * Assign a taxonomy term, recording it when the test creates it.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $term_name Term name.
	 * @param string $taxonomy Taxonomy name.
	 */
	private function assign_term( int $post_id, string $term_name, string $taxonomy ): void {
		$term = get_term_by( 'name', $term_name, $taxonomy, ARRAY_A );
		if ( ! $term ) {
			$term = wp_insert_term( $term_name, $taxonomy );
			if ( is_wp_error( $term ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the exact fixture error in the test failure.
				throw new \RuntimeException( $term->get_error_message() );
			}
			$this->term_ids[ $taxonomy ][] = (int) $term['term_id'];
		}

		$result = wp_set_post_terms( $post_id, array( (int) $term['term_id'] ), $taxonomy );
		if ( is_wp_error( $result ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the exact fixture error in the test failure.
			throw new \RuntimeException( $result->get_error_message() );
		}
	}

	/**
	 * Find one template by slug and prove duplicate suppression.
	 *
	 * @param WP_Block_Template[] $templates Templates.
	 * @param string              $slug Template slug.
	 * @return WP_Block_Template
	 */
	private function find_template( array $templates, string $slug ): WP_Block_Template {
		$matches = array_values(
			array_filter(
				$templates,
				static function ( $template ) use ( $slug ) {
					return $slug === $template->slug;
				}
			)
		);

		$this->assertCount( 1, $matches, "Expected exactly one {$slug} result." );

		return $matches[0];
	}

	/**
	 * Assert the externally visible saved-template contract.
	 *
	 * @param WP_Block_Template $template Template result.
	 * @param int               $post_id Expected post ID.
	 * @param string            $theme Expected theme.
	 * @param string            $origin Expected origin.
	 * @param string            $content Expected content.
	 * @param string            $type Expected type.
	 */
	private function assert_template_contract( WP_Block_Template $template, int $post_id, string $theme, string $origin, string $content, string $type ): void {
		$this->assertSame( $post_id, $template->wp_id );
		$this->assertSame( $theme . '//' . $template->slug, $template->id );
		$this->assertSame( $theme, $template->theme );
		$this->assertSame( $origin, $template->origin );
		$this->assertSame( $content, $template->content );
		$this->assertSame( $type, $template->type );
		$this->assertSame( 'custom', $template->source );
		$this->assertSame( 'publish', $template->status );
	}

	/**
	 * Return sorted slugs from template results.
	 *
	 * @param WP_Block_Template[] $templates Templates.
	 * @return string[]
	 */
	private function sorted_slugs( array $templates ): array {
		$slugs = array_column( $templates, 'slug' );
		sort( $slugs );

		return $slugs;
	}

	/**
	 * Assert two calls preserve exact result order and identities.
	 *
	 * @param WP_Block_Template[] $expected First results.
	 * @param WP_Block_Template[] $actual Second results.
	 */
	private function assert_same_template_sequence( array $expected, array $actual ): void {
		$this->assertSame( array_column( $expected, 'id' ), array_column( $actual, 'id' ) );
	}
}
