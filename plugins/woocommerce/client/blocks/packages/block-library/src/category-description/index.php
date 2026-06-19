<?php
/**
 * Server-side rendering of the `woocommerce/category-description` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

/**
 * Renders the `woocommerce/category-description` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered category description block.
 */
function render_block_woocommerce_category_description( $attributes, $content, $block ) {
	$term_id       = isset( $block->context['termId'] ) ? absint( $block->context['termId'] ) : 0;
	$term_taxonomy = isset( $block->context['termTaxonomy'] ) ? sanitize_key( $block->context['termTaxonomy'] ) : 'product_cat';
	$text_align    = isset( $attributes['textAlign'] ) ? sanitize_key( $attributes['textAlign'] ) : '';

	if ( ! $term_id ) {
		return '';
	}

	$term = get_term( $term_id, $term_taxonomy );
	if ( ! $term || is_wp_error( $term ) ) {
		return '';
	}

	$description = $term->description;
	if ( empty( trim( $description ) ) ) {
		return '';
	}

	$classes = $text_align ? 'has-text-align-' . $text_align : '';

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => $classes,
		)
	);

	return sprintf(
		'<div %1$s>%2$s</div>',
		$wrapper_attributes,
		wp_kses_post( wc_format_content( $description ) )
	);
}

/**
 * Registers the `woocommerce/category-description` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_category_description(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_category_description',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_category_description' );
