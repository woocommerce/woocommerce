<?php
/**
 * Server-side rendering of the `woocommerce/product-reviews-pagination-next` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

/**
 * Gets the next reviews pagination arrow.
 *
 * @since 11.0.0
 *
 * @param WP_Block $block Block instance.
 * @return string Next reviews pagination arrow markup.
 */
function get_block_woocommerce_product_reviews_pagination_next_arrow( WP_Block $block ): string {
	$arrow_map = array(
		'none'    => '',
		'arrow'   => '→',
		'chevron' => '»',
	);

	if ( empty( $block->context['reviews/paginationArrow'] ) || ! is_string( $block->context['reviews/paginationArrow'] ) ) {
		return '';
	}

	$arrow_attribute = $block->context['reviews/paginationArrow'];
	if ( empty( $arrow_map[ $arrow_attribute ] ) ) {
		return '';
	}

	$arrow         = $arrow_map[ $arrow_attribute ];
	$arrow_classes = 'wp-block-woocommerce-product-reviews-pagination-next-arrow is-arrow-' . $arrow_attribute;

	return sprintf(
		'<span class="%1$s" aria-hidden="true">%2$s</span>',
		esc_attr( $arrow_classes ),
		esc_html( $arrow )
	);
}

/**
 * Renders the `woocommerce/product-reviews-pagination-next` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block content.
 */
function render_block_woocommerce_product_reviews_pagination_next( $attributes, $content, $block ): string {
	if ( ! $block instanceof WP_Block || empty( $block->context['postId'] ) ) {
		return '';
	}

	$comment_vars     = build_comment_query_vars_from_block( $block );
	$max_page         = ( new WP_Comment_Query( $comment_vars ) )->max_num_pages;
	$default_label    = __( 'Newer Reviews', 'woocommerce' );
	$label            = isset( $attributes['label'] ) && is_string( $attributes['label'] ) && '' !== $attributes['label'] ? $attributes['label'] : $default_label;
	$pagination_arrow = get_block_woocommerce_product_reviews_pagination_next_arrow( $block );

	$filter_link_attributes = static function () {
		return get_block_wrapper_attributes();
	};
	add_filter( 'next_comments_link_attributes', $filter_link_attributes );

	if ( $pagination_arrow ) {
		$label .= $pagination_arrow;
	}

	$next_comments_link = get_next_comments_link( $label, $max_page, $comment_vars['paged'] ?? null );

	remove_filter( 'next_comments_link_attributes', $filter_link_attributes );

	if ( ! isset( $next_comments_link ) ) {
		return '';
	}

	return $next_comments_link;
}

/**
 * Registers the `woocommerce/product-reviews-pagination-next` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_reviews_pagination_next(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_reviews_pagination_next',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_reviews_pagination_next' );
