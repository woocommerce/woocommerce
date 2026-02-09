<?php declare( strict_types = 1 );
namespace Automattic\WooCommerce\Blocks\BlockTypes\Reviews;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;

/**
 * ProductReviewAuthorName class.
 */
class ProductReviewAuthorName extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-review-author-name';

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

		$classes = array();
		if ( isset( $attributes['textAlign'] ) ) {
			$classes[] = 'has-text-align-' . $attributes['textAlign'];
		}
		if ( isset( $attributes['style']['elements']['link']['color']['text'] ) ) {
			$classes[] = 'has-link-color';
		}

		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => implode( ' ', $classes ) ) );
		$comment_author     = get_comment_author( $comment );
		$link               = get_comment_author_url( $comment );

		if ( ! empty( $link ) && ! empty( $attributes['isLink'] ) && ! empty( $attributes['linkTarget'] ) ) {
			$comment_author = sprintf( '<a rel="external nofollow ugc" href="%1s" target="%2s" >%3s</a>', esc_url( $link ), esc_attr( $attributes['linkTarget'] ), $comment_author );
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
