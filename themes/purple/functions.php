<?php
/**
 * Purple functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package purple
 * @since purple 1.0
 */

declare( strict_types = 1 );

if ( ! function_exists( 'purple_unregister_patterns' ) ) :
	/**
	 * Unregister Jetpack patterns, WooCommerce patterns, and core patterns bundled in WordPress.
	 */
	function purple_unregister_patterns() {
		$pattern_names = array(
			// Jetpack form patterns.
			'contact-form',
			'newsletter-form',
			'rsvp-form',
			'registration-form',
			'appointment-form',
			'feedback-form',
			// WooCommerce patterns. Keep only a curated set in the WooCommerce
			// inserter category; everything else gets unregistered.
			'woocommerce-blocks/banner',
			'woocommerce-blocks/centered-content-with-image-below',
			'woocommerce-blocks/content-right-with-image-left',
			'woocommerce-blocks/featured-category-cover-image',
			'woocommerce-blocks/featured-category-triple',
			'woocommerce-blocks/footer-large',
			'woocommerce-blocks/footer-simple-menu',
			'woocommerce-blocks/footer-with-3-menus',
			'woocommerce-blocks/four-image-grid-content-left',
			'woocommerce-blocks/header-centered-menu',
			'woocommerce-blocks/header-distraction-free',
			'woocommerce-blocks/header-essential',
			'woocommerce-blocks/header-large',
			'woocommerce-blocks/header-minimal',
			'woocommerce-blocks/heading-with-three-columns-of-content-with-link',
			'woocommerce-blocks/hero-product-3-split',
			'woocommerce-blocks/hero-product-chessboard',
			'woocommerce-blocks/just-arrived-full-hero',
			'woocommerce-blocks/product-collection-3-columns',
			'woocommerce-blocks/product-collection-5-columns',
			'woocommerce-blocks/product-collection-featured-products-5-columns',
			'woocommerce-blocks/product-query-product-gallery',
			'woocommerce-blocks/related-products',
			'woocommerce-blocks/social-follow-us-in-social-media',
			'woocommerce-blocks/testimonials-3-columns',
			'woocommerce-blocks/testimonials-single',
			'woocommerce-blocks/three-columns-with-images-and-content',
			'woocommerce/coming-soon',
			'woocommerce/coming-soon-entire-site',
			'woocommerce/coming-soon-store-only',
			'woocommerce/no-products-found',
			'woocommerce/no-products-found-clear-filters',
			'woocommerce/page-coming-soon-default',
			'woocommerce/page-coming-soon-image-gallery',
			'woocommerce/page-coming-soon-minimal-left-image',
			'woocommerce/page-coming-soon-modern-black',
			'woocommerce/page-coming-soon-split-right-image',
			'woocommerce/page-coming-soon-with-header-footer',
			'woocommerce/product-search-form',
			// Patterns bundled in WordPress core.
			// These would be removed by remove_theme_support( 'core-block-patterns' )
			// if it's called on the init action with priority 9 from a plugin, not from a theme.
			'core/query-standard-posts',
			'core/query-medium-posts',
			'core/query-small-posts',
			'core/query-grid-posts',
			'core/query-large-title-posts',
			'core/query-offset-posts',
			'core/social-links-shared-background-color',
		);
		foreach ( $pattern_names as $pattern_name ) {
			$pattern = \WP_Block_Patterns_Registry::get_instance()->get_registered( $pattern_name );
			if ( $pattern ) {
				unregister_block_pattern( $pattern_name );
			}
		}
	}

endif;

if ( ! function_exists( 'purple_hide_woocommerce_template_parts' ) ) :
	/**
	 * Hide WooCommerce template parts the theme doesn't use from the Site Editor.
	 *
	 * Companion to purple_unregister_patterns(): template parts have no
	 * unregister API — WooCommerce injects them into template queries with its
	 * own get_block_templates filter (priority 10) — so they are filtered out
	 * of query results here instead. This only affects listings; rendering a
	 * template part goes through get_block_file_template and is unaffected.
	 * Copies customized in the editor have the 'custom' source and are kept,
	 * so user-edited content is never hidden.
	 *
	 * @param WP_Block_Template[] $templates     Found templates.
	 * @param array               $query         Template query arguments.
	 * @param string              $template_type wp_template or wp_template_part.
	 * @return WP_Block_Template[]
	 */
	function purple_hide_woocommerce_template_parts( array $templates, array $query, string $template_type ): array {
		if ( 'wp_template_part' !== $template_type ) {
			return $templates;
		}

		$hidden_template_part_slugs = array(
			// Referenced only by WooCommerce's coming-soon patterns, which
			// purple_unregister_patterns() removes; Purple ships its own
			// coming-soon template.
			'coming-soon-social-links',
		);

		return array_values(
			array_filter(
				$templates,
				static function ( $template ) use ( $hidden_template_part_slugs ) {
					return 'custom' === $template->source
						|| ! in_array( $template->slug, $hidden_template_part_slugs, true );
				}
			)
		);
	}

endif;

add_filter( 'get_block_templates', 'purple_hide_woocommerce_template_parts', 20, 3 );

if ( ! function_exists( 'purple_hide_store_templates_from_template_picker' ) ) :
	/**
	 * Keep store templates out of the post editor's "Change template" picker.
	 *
	 * Core treats every non-hierarchy template file without a postTypes
	 * declaration as "available for all post types", so the theme's WooCommerce
	 * template overrides (Product Catalog, Page: Cart, Single Product, …) are
	 * offered as page templates. They never make sense there — WooCommerce
	 * routes to them by URL, not by page-template assignment — and their store
	 * blocks can't even render in the picker's preview. Only queries that
	 * specify a post_type come from the picker, so Site Editor listings (no
	 * post_type) are unaffected and the templates stay editable there.
	 *
	 * @param WP_Block_Template[] $templates     Found templates.
	 * @param array               $query         Template query arguments.
	 * @param string              $template_type wp_template or wp_template_part.
	 * @return WP_Block_Template[]
	 */
	function purple_hide_store_templates_from_template_picker( array $templates, array $query, string $template_type ): array {
		if ( 'wp_template' !== $template_type || empty( $query['post_type'] ) ) {
			return $templates;
		}

		$hidden_template_slugs = array(
			'archive-product',
			'coming-soon',
			'order-confirmation',
			'page-cart',
			'page-checkout',
			'product-search-results',
			'single-product',
			'taxonomy-product_attribute',
		);

		return array_values(
			array_filter(
				$templates,
				static function ( $template ) use ( $hidden_template_slugs ) {
					return ! in_array( $template->slug, $hidden_template_slugs, true );
				}
			)
		);
	}

endif;

add_filter( 'get_block_templates', 'purple_hide_store_templates_from_template_picker', 20, 3 );

if ( ! function_exists( 'purple_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * @since purple 1.0
	 *
	 * @return void
	 */
	function purple_setup() {

		// Enqueue editor styles.
		add_editor_style( 'style.css' );
		// Unregister Jetpack form patterns and core patterns bundled in WordPress.
		// Simple sites.
		purple_unregister_patterns();
		add_filter(
			'wp_loaded',
			function () {
				// Atomic sites.
				purple_unregister_patterns();
			}
		);
		// Remove theme support for the core and featured patterns coming from the Dotorg pattern directory.
		remove_theme_support( 'core-block-patterns' );
	}

endif;

add_action( 'after_setup_theme', 'purple_setup' );

if ( ! function_exists( 'purple_register_pattern_categories' ) ) :
	/**
	 * Register the "Purple" block pattern category so the theme's
	 * internally-used patterns can be grouped together in the inserter.
	 */
	function purple_register_pattern_categories() {
		register_block_pattern_category(
			'purple',
			array( 'label' => __( 'Purple', 'purple' ) )
		);
	}

endif;

add_action( 'init', 'purple_register_pattern_categories' );

if ( ! function_exists( 'purple_rename_woocommerce_pattern_category' ) ) :
	/**
	 * Rename the "WooCommerce" pattern category to "Shop" in the inserter.
	 *
	 * The slug stays "woo-commerce" so patterns categorised under it
	 * (Purple's and the WC plugin's) don't need re-tagging; only the
	 * label displayed in the editor changes. Hooked late so it runs
	 * after WooCommerce's own registration.
	 */
	function purple_rename_woocommerce_pattern_category() {
		$registry = \WP_Block_Pattern_Categories_Registry::get_instance();
		if ( $registry->is_registered( 'woo-commerce' ) ) {
			unregister_block_pattern_category( 'woo-commerce' );
		}
		register_block_pattern_category(
			'woo-commerce',
			array( 'label' => __( 'Shop', 'purple' ) )
		);
	}

endif;

add_action( 'init', 'purple_rename_woocommerce_pattern_category', 99 );

if ( ! function_exists( 'purple_styles' ) ) :
	/**
	 * Enqueue styles.
	 *
	 * @since purple 1.0
	 *
	 * @return void
	 */
	function purple_styles() {

		// Register theme stylesheet. Use the template (parent) directory and
		// version so the file still resolves when a child theme is active.
		wp_register_style(
			'purple-style',
			get_template_directory_uri() . '/style.css',
			array(),
			wp_get_theme( get_template() )->get( 'Version' )
		);

		// Enqueue theme stylesheet.
		wp_enqueue_style( 'purple-style' );
	}

endif;

add_action( 'wp_enqueue_scripts', 'purple_styles' );

if ( ! function_exists( 'purple_scripts' ) ) :
	/**
	 * Enqueue front-end scripts.
	 *
	 * Loads the small helper that aligns the navigation dropdowns with the
	 * bottom of the header (see assets/js/navigation-dropdown.js).
	 *
	 * @since purple 1.0
	 *
	 * @return void
	 */
	function purple_scripts() {
		wp_enqueue_script(
			'purple-navigation-dropdown',
			get_template_directory_uri() . '/assets/js/navigation-dropdown.js',
			array(),
			wp_get_theme( get_template() )->get( 'Version' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

endif;

add_action( 'wp_enqueue_scripts', 'purple_scripts' );

if ( ! function_exists( 'purple_remove_upsells' ) ) :
	/**
	 * Remove upsells from product description.
	 *
	 * @since purple 1.0
	 *
	 * @return void
	 */
	function purple_remove_upsells() {
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
	}

endif;

add_action( 'init', 'purple_remove_upsells' );

if ( ! function_exists( 'purple_woocommerce_locate_template' ) ) :
	/**
	 * Serve WooCommerce template overrides from flat files in includes/
	 * instead of the nested woocommerce/ template-path convention. WooCommerce
	 * requests templates by their path-shaped name (e.g. loop/orderby.php),
	 * so each override is mapped here explicitly.
	 *
	 * @param string $template      Full path WooCommerce resolved.
	 * @param string $template_name Template name relative to WooCommerce's templates dir.
	 * @return string
	 */
	function purple_woocommerce_locate_template( string $template, string $template_name ): string {
		if ( 'loop/orderby.php' === $template_name ) {
			return get_theme_file_path( 'includes/orderby.php' );
		}
		return $template;
	}

endif;

add_filter( 'woocommerce_locate_template', 'purple_woocommerce_locate_template', 10, 2 );

require_once get_template_directory() . '/includes/cart-page-content.php';

if ( ! function_exists( 'purple_filter_woocommerce_create_pages' ) ) :
	/**
	 * Seed Purple's cart block markup when WooCommerce creates store pages.
	 *
	 * @param array<string, array<string, mixed>> $pages Page definitions keyed by slug.
	 * @return array<string, array<string, mixed>>
	 */
	function purple_filter_woocommerce_create_pages( array $pages ): array {
		if ( isset( $pages['cart'] ) ) {
			$pages['cart']['content'] = purple_get_cart_page_content();
		}

		return $pages;
	}

endif;

add_filter( 'woocommerce_create_pages', 'purple_filter_woocommerce_create_pages' );

if ( ! function_exists( 'purple_sync_cart_page_content' ) ) :
	/**
	 * Sync the Cart page post content to Purple's default markup once per site.
	 *
	 * Runs on theme activation so existing stores pick up the cart block without
	 * duplicating it in the block template.
	 */
	function purple_sync_cart_page_content(): void {
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return;
		}

		if ( (int) get_theme_mod( 'purple_cart_page_content_version', 0 ) >= 1 ) {
			return;
		}

		$cart_page_id = wc_get_page_id( 'cart' );
		if ( $cart_page_id <= 0 ) {
			return;
		}

		wp_update_post(
			array(
				'ID'           => $cart_page_id,
				'post_content' => purple_get_cart_page_content(),
			)
		);

		set_theme_mod( 'purple_cart_page_content_version', 1 );
	}

endif;

add_action( 'after_switch_theme', 'purple_sync_cart_page_content' );
