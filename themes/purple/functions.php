<?php
/**
 * purple functions and definitions
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
		// Simple sites
		purple_unregister_patterns();
		add_filter( 'wp_loaded', function () {
			// Atomic sites
			purple_unregister_patterns();
		} );
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

if ( ! function_exists( 'purple_hidden_pattern_categories' ) ) :
	/**
	 * Block pattern categories the theme doesn't want to expose in the inserter.
	 *
	 * @return string[] Category slugs.
	 */
	function purple_hidden_pattern_categories() {
		return array(
			'banner',
			'call-to-action',
			'featured',
			'featured-selling',
			'gallery',
			'intro',
			'media',
			'posts',
			'text',
		);
	}

endif;

if ( ! function_exists( 'purple_unregister_pattern_categories' ) ) :
	/**
	 * Hide the pattern categories the theme doesn't want to expose.
	 *
	 * Unregistering a category is not enough on its own: the inserter rebuilds
	 * a category from any pattern still tagged with it (showing the raw slug as
	 * the label when there is no registered category). So we both unregister the
	 * category and strip the slug from every pattern that references it.
	 *
	 * Hooked late so it runs after core, the theme and plugins (e.g. WooCommerce)
	 * have registered their patterns and categories.
	 */
	function purple_unregister_pattern_categories() {
		$hidden = purple_hidden_pattern_categories();

		$category_registry = \WP_Block_Pattern_Categories_Registry::get_instance();
		foreach ( $hidden as $cat ) {
			if ( $category_registry->is_registered( $cat ) ) {
				unregister_block_pattern_category( $cat );
			}
		}

		$pattern_registry = \WP_Block_Patterns_Registry::get_instance();
		foreach ( $pattern_registry->get_all_registered() as $pattern ) {
			$categories = $pattern['categories'] ?? array();
			if ( empty( $categories ) ) {
				continue;
			}

			$kept = array_values( array_diff( $categories, $hidden ) );
			if ( count( $kept ) === count( $categories ) ) {
				continue;
			}

			$pattern['categories'] = $kept;
			unregister_block_pattern( $pattern['name'] );
			register_block_pattern( $pattern['name'], $pattern );
		}
	}

endif;

add_action( 'init', 'purple_unregister_pattern_categories', 100 );

if ( ! function_exists( 'purple_styles' ) ) :
	/**
	 * Enqueue styles.
	 *
	 * @since purple 1.0
	 *
	 * @return void
	 */
	function purple_styles() {

		// Register theme stylesheet.
		wp_register_style(
			'purple-style',
			get_stylesheet_directory_uri() . '/style.css',
			array(),
			wp_get_theme()->get( 'Version' )
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
			get_stylesheet_directory_uri() . '/assets/js/navigation-dropdown.js',
			array(),
			wp_get_theme()->get( 'Version' ),
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
