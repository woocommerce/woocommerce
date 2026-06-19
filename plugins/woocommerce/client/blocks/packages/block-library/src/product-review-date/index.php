<?php
/**
 * Server-side rendering of the `woocommerce/product-review-date` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

/**
 * Renders the `woocommerce/product-review-date` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block content.
 */
function render_block_woocommerce_product_review_date( $attributes, $content, $block ): string {
	if ( ! $block instanceof WP_Block || ! isset( $block->context['commentId'] ) ) {
		return '';
	}

	$comment = get_comment( $block->context['commentId'] );
	if ( ! $comment instanceof WP_Comment ) {
		return '';
	}

	$classes            = isset( $attributes['style']['elements']['link']['color']['text'] ) ? 'has-link-color' : '';
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classes ) );
	$format             = $attributes['format'] ?? '';

	if ( 'human-diff' === $format ) {
		$comment_timestamp = get_comment_date( 'U', $comment );
		$comment_timestamp = is_numeric( $comment_timestamp ) ? (int) $comment_timestamp : time();

		// translators: %s: human-readable time difference.
		$formatted_date = sprintf( __( '%s ago', 'woocommerce' ), human_time_diff( $comment_timestamp ) );
	} else {
		$formatted_date = get_comment_date( is_string( $format ) ? $format : '', $comment );
	}

	if ( ! empty( $attributes['isLink'] ) ) {
		$formatted_date = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( get_comment_link( $comment ) ),
			$formatted_date
		);
	}

	return sprintf(
		'<div %1$s><time datetime="%2$s">%3$s</time></div>',
		$wrapper_attributes,
		esc_attr( get_comment_date( 'c', $comment ) ),
		$formatted_date
	);
}

/**
 * Registers the `woocommerce/product-review-date` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_review_date(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_review_date',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_review_date' );
