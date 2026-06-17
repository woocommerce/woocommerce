<?php
/**
 * Server-side rendering of the `woocommerce/category-title` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

/**
 * Renders the `woocommerce/category-title` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered category title block.
 */
function render_block_woocommerce_category_title( $attributes, $content, $block ) {
	$term_id       = isset( $block->context['termId'] ) ? absint( $block->context['termId'] ) : 0;
	$term_taxonomy = isset( $block->context['termTaxonomy'] ) ? sanitize_key( $block->context['termTaxonomy'] ) : 'product_cat';

	if ( ! $term_id ) {
		return '';
	}

	$category_term = get_term( $term_id, $term_taxonomy );
	if ( ! $category_term || is_wp_error( $category_term ) ) {
		return '';
	}

	$level      = isset( $attributes['level'] ) ? max( 0, min( 6, intval( $attributes['level'] ) ) ) : 2;
	$text_align = isset( $attributes['textAlign'] ) ? sanitize_key( $attributes['textAlign'] ) : '';
	$is_link    = ! empty( $attributes['isLink'] );
	$rel        = isset( $attributes['rel'] ) ? esc_attr( $attributes['rel'] ) : '';
	$target     = isset( $attributes['linkTarget'] ) ? esc_attr( $attributes['linkTarget'] ) : '_self';

	$tag_name           = 0 === $level ? 'p' : 'h' . $level;
	$classes            = $text_align ? 'has-text-align-' . $text_align : '';
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classes ) );

	$title_html = '';
	if ( $is_link ) {
		$term_link = get_term_link( $category_term );
		if ( ! is_wp_error( $term_link ) ) {
			$title_html = sprintf(
				'<%1$s %2$s><a href="%3$s" target="%4$s" rel="%5$s">%6$s</a></%1$s>',
				esc_attr( $tag_name ),
				$wrapper_attributes,
				esc_url( $term_link ),
				esc_attr( $target ),
				$rel,
				esc_html( $category_term->name )
			);
		}
	}

	if ( '' === $title_html ) {
		$title_html = sprintf(
			'<%1$s %2$s>%3$s</%1$s>',
			esc_attr( $tag_name ),
			$wrapper_attributes,
			esc_html( $category_term->name )
		);
	}

	return $title_html;
}

/**
 * Registers the `woocommerce/category-title` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_category_title(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_category_title',
		)
	);
}


add_action( 'init', 'register_block_woocommerce_category_title' );
