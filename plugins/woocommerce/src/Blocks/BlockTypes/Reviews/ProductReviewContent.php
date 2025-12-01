<?php declare( strict_types = 1 );
namespace Automattic\WooCommerce\Blocks\BlockTypes\Reviews;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;

/**
 * ProductReviewContent class.
 */
class ProductReviewContent extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-review-content';

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 * @return string Rendered block content.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( ! isset( $block->context['commentId'] ) ) {
			return '';
		}

		$comment            = get_comment( $block->context['commentId'] );
		$commenter          = wp_get_current_commenter();
		$show_pending_links = isset( $commenter['comment_author'] ) && $commenter['comment_author'];
		if ( empty( $comment ) ) {
			return '';
		}

		$args         = array();
		$comment_text = get_comment_text( $comment, $args );
		if ( ! $comment_text ) {
			return '';
		}

		/**
		 * This filter is documented in wp-includes/comment-template.php
		 *
		 * @since 1.2.0
		 */
		$comment_text = apply_filters( 'comment_text', $comment_text, $comment, $args );

		$moderation_note = '';
		if ( '0' === $comment->comment_approved ) {
			if ( $commenter['comment_author_email'] ) {
				$moderation_note = __( 'Your review is awaiting moderation.', 'woocommerce' );
			} else {
				$moderation_note = __( 'Your review is awaiting moderation. This is a preview; your review will be visible after it has been approved.', 'woocommerce' );
			}
			$moderation_note = '<p><em class="review-awaiting-moderation">' . esc_html( $moderation_note ) . '</em></p>';
			if ( ! $show_pending_links ) {
				$comment_text = wp_kses( $comment_text, array() );
			}
		}

		$classes = array();
		if ( isset( $attributes['textAlign'] ) ) {
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
	 * Get the frontend script handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string|null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}
}
