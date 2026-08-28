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
	 * Unregister Jetpack patterns and core patterns bundled in WordPress.
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
	 * Template parts have no unregister API — WooCommerce injects them into
	 * template queries with its own get_block_templates filter (priority 10) —
	 * so they are filtered out of query results here instead. This only affects
	 * listings; rendering a template part goes through get_block_file_template
	 * and is unaffected.
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
			// Purple ships its own coming-soon template. WooCommerce's patterns
			// can still render this part when inserted into a page.
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
