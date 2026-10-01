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

if ( is_admin() ) {
	require_once get_template_directory() . '/inc/woocommerce-compatibility.php';
}

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

		foreach ( purple_get_woocommerce_stylesheets() as $relative_path ) {
			add_editor_style( $relative_path );
		}
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

if ( ! function_exists( 'purple_get_woocommerce_stylesheets' ) ) :
	/**
	 * WooCommerce compatibility stylesheets that apply to the installed version.
	 *
	 * Keys are the releases that contain the upstream fix. A sheet is included
	 * only while the installed version is older than that.
	 *
	 * @since purple 1.0
	 *
	 * @return array<string, string> Map of fixed-in version to stylesheet path.
	 */
	function purple_get_woocommerce_stylesheets(): array {
		if ( ! defined( 'WC_VERSION' ) ) {
			return array();
		}

		$stylesheets = array(
			'11.2.0' => 'assets/css/woocommerce-11.1.css',
			'11.3.0' => 'assets/css/woocommerce-11.2.css',
		);

		$applicable = array();
		foreach ( $stylesheets as $version => $relative_path ) {
			if ( version_compare( WC_VERSION, $version, '<' ) ) {
				$applicable[ $version ] = $relative_path;
			}
		}

		return $applicable;
	}

endif;

if ( ! function_exists( 'purple_styles' ) ) :
	/**
	 * Enqueue styles.
	 *
	 * @since purple 1.0
	 *
	 * @return void
	 */
	function purple_styles() {
		$theme_version = wp_get_theme( get_template() )->get( 'Version' );

		// Register theme stylesheet. Use the template (parent) directory and
		// version so the file still resolves when a child theme is active.
		wp_register_style(
			'purple-style',
			get_template_directory_uri() . '/style.css',
			array(),
			$theme_version
		);

		// Enqueue theme stylesheet.
		wp_enqueue_style( 'purple-style' );

		foreach ( purple_get_woocommerce_stylesheets() as $version => $relative_path ) {
			wp_enqueue_style(
				'purple-woocommerce-' . str_replace( '.', '-', $version ),
				get_template_directory_uri() . '/' . $relative_path,
				array(),
				$theme_version
			);
		}
	}

endif;

add_action( 'wp_enqueue_scripts', 'purple_styles' );

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
