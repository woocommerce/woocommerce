<?php
/**
 * Server-side rendering of the `woocommerce/product-review-author-name` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

/**
 * Renders the `woocommerce/product-review-author-name` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block content.
 */
function render_block_woocommerce_product_review_author_name( $attributes, $content, $block ): string {
	if ( ! $block instanceof WP_Block || ! isset( $block->context['commentId'] ) ) {
		return '';
	}

	$comment = get_comment( $block->context['commentId'] );
	if ( ! $comment instanceof WP_Comment ) {
		return '';
	}

	$commenter          = wp_get_current_commenter();
	$show_pending_links = isset( $commenter['comment_author'] ) && $commenter['comment_author'];
	$classes            = array();

	if ( isset( $attributes['textAlign'] ) && is_string( $attributes['textAlign'] ) ) {
		$classes[] = 'has-text-align-' . $attributes['textAlign'];
	}

	if ( isset( $attributes['style']['elements']['link']['color']['text'] ) ) {
		$classes[] = 'has-link-color';
	}

	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => implode( ' ', $classes ) ) );
	$comment_author     = get_comment_author( $comment );
	$link               = get_comment_author_url( $comment );
	$link_target        = $attributes['linkTarget'] ?? '';

	if ( ! empty( $link ) && ! empty( $attributes['isLink'] ) && is_string( $link_target ) && ! empty( $link_target ) ) {
		$comment_author = sprintf(
			'<a rel="external nofollow ugc" href="%1$s" target="%2$s" >%3$s</a>',
			esc_url( $link ),
			esc_attr( $link_target ),
			$comment_author
		);
	}

	if ( '0' === $comment->comment_approved && ! $show_pending_links ) {
		$comment_author = wp_kses( $comment_author, array() );
	}

	return sprintf(
		'<div %1$s>%2$s</div>',
		$wrapper_attributes,
		$comment_author
	);
}

/**
 * Registers the `woocommerce/product-review-author-name` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_review_author_name(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_review_author_name',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_review_author_name' );
