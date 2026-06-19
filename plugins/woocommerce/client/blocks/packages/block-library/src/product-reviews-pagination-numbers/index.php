<?php
/**
 * Server-side rendering of the `woocommerce/product-reviews-pagination-numbers` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

/**
 * Renders the `woocommerce/product-reviews-pagination-numbers` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block content.
 */
function render_block_woocommerce_product_reviews_pagination_numbers( $attributes, $content, $block ): string {
	if ( ! $block instanceof WP_Block || empty( $block->context['postId'] ) ) {
		return '';
	}

	$comment_vars = build_comment_query_vars_from_block( $block );
	$total        = ( new WP_Comment_Query( $comment_vars ) )->max_num_pages;
	$current      = ! empty( $comment_vars['paged'] ) ? absint( $comment_vars['paged'] ) : 0;

	$pagination_args = array(
		'total'     => $total,
		'prev_next' => false,
		'echo'      => false,
	);
	if ( $current ) {
		$pagination_args['current'] = $current;
	}

	$pagination_links = paginate_comments_links( $pagination_args );

	if ( ! is_string( $pagination_links ) || '' === $pagination_links ) {
		return '';
	}

	return sprintf(
		'<div %1$s>%2$s</div>',
		get_block_wrapper_attributes(),
		$pagination_links
	);
}

/**
 * Registers the `woocommerce/product-reviews-pagination-numbers` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_reviews_pagination_numbers(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_reviews_pagination_numbers',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_reviews_pagination_numbers' );
