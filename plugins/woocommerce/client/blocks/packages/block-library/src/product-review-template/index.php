<?php
/**
 * Server-side rendering of the `woocommerce/product-review-template` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

/**
 * Recursively renders a list of nested reviews.
 *
 * @since 11.0.0
 *
 * @param array<int, WP_Comment|int> $comments The array of comments.
 * @param WP_Block                   $block    Block instance.
 * @return string Rendered comments list items.
 */
function render_block_woocommerce_product_review_template_comments( array $comments, WP_Block $block ): string {
	$content = '';

	foreach ( $comments as $comment ) {
		if ( ! $comment instanceof WP_Comment ) {
			continue;
		}

		$comment_id           = $comment->comment_ID;
		$filter_block_context = static function ( $context ) use ( $comment_id ) {
			$context['commentId'] = $comment_id;
			return $context;
		};

		/*
		 * We set commentId context through the `render_block_context` filter so
		 * that dynamically inserted blocks (at `render_block` filter stage)
		 * will also receive that context.
		 *
		 * Use an early priority so that other 'render_block_context' filters
		 * have access to the values.
		 */
		add_filter( 'render_block_context', $filter_block_context, 1 );

		/*
		 * We construct a new WP_Block instance from the parsed block so that
		 * it'll receive any changes made by the `render_block_data` filter.
		 */
		$block_content = ( new WP_Block( $block->parsed_block ) )->render( array( 'dynamic' => false ) );

		remove_filter( 'render_block_context', $filter_block_context, 1 );

		$children = $comment->get_children();

		/*
		 * We need to create the CSS classes before recursing into the children.
		 * This is because comment_class() uses globals like `$comment_alt`
		 * and `$comment_thread_alt` which are order-sensitive.
		 */
		$comment_classes = comment_class(
			'',
			(int) $comment->comment_ID,
			(int) $comment->comment_post_ID,
			false
		);

		if ( ! empty( $children ) ) {
			$inner_content  = render_block_woocommerce_product_review_template_comments(
				$children,
				$block
			);
			$block_content .= sprintf( '<ol>%1$s</ol>', $inner_content );
		}

		$content .= sprintf( '<li id="comment-%1$s" %2$s>%3$s</li>', $comment->comment_ID, $comment_classes, $block_content );
	}

	return $content;
}

/**
 * Renders the `woocommerce/product-review-template` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block content.
 */
function render_block_woocommerce_product_review_template( $attributes, $content, $block ): string {
	if ( empty( $block->context['postId'] ) || post_password_required( $block->context['postId'] ) ) {
		return '';
	}

	$comment_query = new WP_Comment_Query(
		build_comment_query_vars_from_block( $block )
	);

	$comments = $comment_query->get_comments();
	if ( ! is_array( $comments ) || 0 === count( $comments ) ) {
		return '';
	}

	$comment_order = get_option( 'comment_order' );

	if ( 'desc' === $comment_order ) {
		$comments = array_reverse( $comments );
	}

	$wrapper_attributes = get_block_wrapper_attributes();

	return sprintf(
		'<ol %1$s>%2$s</ol>',
		$wrapper_attributes,
		render_block_woocommerce_product_review_template_comments( $comments, $block )
	);
}

/**
 * Registers the `woocommerce/product-review-template` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_review_template(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_review_template',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_review_template' );
