<?php
/**
 * Server-side rendering of the `woocommerce/product-review-content` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

/**
 * Renders the `woocommerce/product-review-content` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block content.
 */
function render_block_woocommerce_product_review_content( $attributes, $content, $block ): string {
	if ( ! $block instanceof WP_Block || ! isset( $block->context['commentId'] ) ) {
		return '';
	}

	$comment = get_comment( $block->context['commentId'] );
	if ( ! $comment instanceof WP_Comment ) {
		return '';
	}

	$commenter          = wp_get_current_commenter();
	$show_pending_links = isset( $commenter['comment_author'] ) && $commenter['comment_author'];
	$args               = array();
	$comment_text       = get_comment_text( $comment, $args );

	if ( ! $comment_text ) {
		return '';
	}

	/**
	 * Filters the text of a comment.
	 *
	 * @since 1.2.0
	 *
	 * @param string     $comment_text Text of the comment.
	 * @param WP_Comment $comment      The comment object.
	 * @param array      $args         An array of arguments.
	 */
	$comment_text = apply_filters( 'comment_text', $comment_text, $comment, $args );

	$moderation_note = '';
	if ( '0' === $comment->comment_approved ) {
		$moderation_note = ! empty( $commenter['comment_author_email'] )
			? __( 'Your review is awaiting moderation.', 'woocommerce' )
			: __( 'Your review is awaiting moderation. This is a preview; your review will be visible after it has been approved.', 'woocommerce' );
		$moderation_note = '<p><em class="review-awaiting-moderation">' . esc_html( $moderation_note ) . '</em></p>';

		if ( ! $show_pending_links ) {
			$comment_text = wp_kses( $comment_text, array() );
		}
	}

	$classes = array();
	if ( isset( $attributes['textAlign'] ) && is_string( $attributes['textAlign'] ) ) {
		$classes[] = 'has-text-align-' . $attributes['textAlign'];
	}

	if ( isset( $attributes['style']['elements']['link']['color']['text'] ) ) {
		$classes[] = 'has-link-color';
	}

	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => implode( ' ', $classes ) ) );

	return sprintf(
		'<div %1$s>%2$s%3$s</div>',
		$wrapper_attributes,
		$moderation_note,
		$comment_text
	);
}

/**
 * Registers the `woocommerce/product-review-content` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_review_content(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_review_content',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_review_content' );
