<?php
/**
 * Plugin Name: WooCommerce Gutenberg Products Block
 * Plugin URI: https://github.com/woocommerce/woocommerce-gutenberg-products-block
 * Description: WooCommerce Products block for the Gutenberg editor.
 * Version: 1.1.0
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Text Domain:  woocommerce
 * Domain Path:  /languages
 * WC requires at least: 3.3
 * WC tested up to: 3.4
 */

defined( 'ABSPATH' ) || die();

define( 'WGPB_VERSION', '1.1.0' );

/**
 * Load up the assets if Gutenberg is active.
 */
function wgpb_initialize() {

	if ( function_exists( 'register_block_type' ) ) {
		add_action( 'init', 'wgpb_register_products_block' );
		add_action( 'rest_api_init', 'wgpb_register_api_routes' );
	}

}
add_action( 'woocommerce_loaded', 'wgpb_initialize' );

/**
 * Register the Products block and its scripts.
 */
function wgpb_register_products_block() {
	register_block_type( 'woocommerce/products', array(
		'editor_script' => 'woocommerce-products-block-editor',
		'editor_style'  => 'woocommerce-products-block-editor',
	) );
}

/**
 * Register extra scripts needed.
 */
function wgpb_extra_gutenberg_scripts() {
	wp_enqueue_script(
		'react-transition-group',
		plugins_url( 'assets/js/vendor/react-transition-group.js', __FILE__ ),
		array( 'wp-blocks', 'wp-element' ),
		'2.2.1'
	);

	wp_register_script(
		'woocommerce-products-block-editor',
		plugins_url( 'assets/js/products-block.js', __FILE__ ),
		array( 'wp-blocks', 'wp-element', 'react-transition-group' ),
		WGPB_VERSION
	);

	$product_block_data = array(
		'min_columns' => wc_get_theme_support( 'product_grid::min_columns', 1 ),
		'max_columns' => wc_get_theme_support( 'product_grid::max_columns', 6 ),
		'default_columns' => wc_get_default_products_per_row(),
		'min_rows' => wc_get_theme_support( 'product_grid::min_rows', 1 ),
		'max_rows' => wc_get_theme_support( 'product_grid::max_rows', 6 ),
		'default_rows' => wc_get_default_product_rows_per_page(),
	);
	wp_localize_script( 'woocommerce-products-block-editor', 'wc_product_block_data', $product_block_data );

	wp_enqueue_script( 'woocommerce-products-block-editor' );

	wp_enqueue_style(
		'woocommerce-products-block-editor',
		plugins_url( 'assets/css/gutenberg-products-block.css', __FILE__ ),
		array( 'wp-edit-blocks' ),
		WGPB_VERSION
	);
}
add_action( 'enqueue_block_editor_assets', 'wgpb_extra_gutenberg_scripts' );

/**
 * Register extra API routes with functionality not available in WC core yet.
 *
 * @todo Remove this function when merging into core because it won't be necessary.
 */
function wgpb_register_api_routes() {
	include_once( dirname( __FILE__ ) . '/includes/class-wgpb-products-controller.php' );
	$controller = new WGPB_Products_Controller();
	$controller->register_routes();
}

/**
 * Brings some extra required shortcode features from WC core 3.4+ to this feature plugin.
 *
 * @todo Remove this function when merging into core because it won't be necessary.
 *
 * @param array $args WP_Query args.
 * @param array $attributes Shortcode attributes.
 * @param string $type Type of shortcode currently processing.
 */
function wgpb_extra_shortcode_features( $args, $attributes, $type ) {
	if ( 'products' !== $type ) {
		return $args;
	}

	// Enable term ids in the category shortcode.
	if ( ! empty( $attributes['category'] ) ) {
		$categories = array_map( 'sanitize_title', explode( ',', $attributes['category'] ) );
		$field      = 'slug';

		if ( empty( $args['tax_query'] ) ) {
			$args['tax_query'] = array();
		}

		// Unset old category tax query.
		foreach ( $args['tax_query'] as $index => $tax_query ) {
			if ( 'product_cat' === $tax_query['taxonomy'] ) {
				unset( $args['tax_query'][ $index ] );
			}
		}

		if ( is_numeric( $categories[0] ) ) {
			$categories = array_map( 'absint', $categories );
			$field      = 'term_id';
		}
		$args['tax_query'][] = array(
			'taxonomy' => 'product_cat',
			'terms'    => $categories,
			'field'    => $field,
			'operator' => $attributes['cat_operator'],
		);
	}

	// Enable term ids in the attributes shortcode and just-attribute queries.
	if ( ! empty( $attributes['attribute'] ) || ! empty( $attributes['terms'] ) ) {
		$taxonomy = strstr( $attributes['attribute'], 'pa_' ) ? sanitize_title( $attributes['attribute'] ) : 'pa_' . sanitize_title( $attributes['attribute'] );
		$terms    = $attributes['terms'] ? array_map( 'sanitize_title', explode( ',', $attributes['terms'] ) ) : array();
		$field    = 'slug';

		if ( empty( $args['tax_query'] ) ) {
			$args['tax_query'] = array();
		}

		// Unset old attribute tax query.
		foreach ( $args['tax_query'] as $index => $tax_query ) {
			if ( $taxonomy === $tax_query['taxonomy'] ) {
				unset( $args['tax_query'][ $index ] );
			}
		}

		if ( $terms && is_numeric( $terms[0] ) ) {
			$terms = array_map( 'absint', $terms );
			$field = 'term_id';
		}

		// If no terms were specified get all products that are in the attribute taxonomy.
		if ( ! $terms ) {
			$terms = get_terms(
				array(
					'taxonomy' => $taxonomy,
					'fields'   => 'ids',
				)
			);
			$field = 'term_id';
		}

		$args['tax_query'][] = array(
			'taxonomy' => $taxonomy,
			'terms'    => $terms,
			'field'    => $field,
			'operator' => $attributes['terms_operator'],
		);
	}

	return $args;
}
add_filter( 'woocommerce_shortcode_products_query', 'wgpb_extra_shortcode_features', 10, 3 );