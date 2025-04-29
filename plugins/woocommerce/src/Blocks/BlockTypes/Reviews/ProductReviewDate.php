<?php declare( strict_types = 1 );
namespace Automattic\WooCommerce\Blocks\BlockTypes\Reviews;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;

/**
 * ProductReviewDate class.
 */
class ProductReviewDate extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-review-date';

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

		$comment = get_comment( $block->context['commentId'] );
		if ( empty( $comment ) ) {
			return '';
		}

		$classes = ( isset( $attributes['style']['elements']['link']['color']['text'] ) ) ? 'has-link-color' : '';

		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classes ) );
		if ( isset( $attributes['format'] ) && 'human-diff' === $attributes['format'] ) {
			// translators: %s: human-readable time difference.
			$formatted_date = sprintf( __( '%s ago', 'woocommerce' ), human_time_diff( get_comment_date( 'U', $comment ) ) );
		} else {
			$formatted_date = get_comment_date( empty( $attributes['format'] ) ? '' : $attributes['format'], $comment );
		}
		$link = get_comment_link( $comment );

		if ( ! empty( $attributes['isLink'] ) ) {
			$formatted_date = sprintf( '<a href="%1s">%2s</a>', esc_url( $link ), $formatted_date );
		}

		return sprintf(
			'<div %1$s><time datetime="%2$s">%3$s</time></div>',
			$wrapper_attributes,
			esc_attr( get_comment_date( 'c', $comment ) ),
			$formatted_date
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
