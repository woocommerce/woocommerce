<?php declare( strict_types = 1 );
namespace Automattic\WooCommerce\Blocks\BlockTypes\Reviews;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use WP_Comment_Query;
use WP_Block;
/**
 * ProductReviewTemplate class.
 */
class ProductReviewTemplate extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-review-template';

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

	/**
	 * Function that recursively renders a list of nested reviews.
	 *
	 * @since 6.3.0 Changed render_block_context priority to `1`.
	 *
	 * @param WP_Comment[] $comments        The array of comments.
	 * @param WP_Block     $block           Block instance.
	 * @return string
	 */
	protected function block_product_review_template_render_comments( $comments, $block ) {
		$content = '';
		foreach ( $comments as $comment ) {
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
			* Use an early priority to so that other 'render_block_context' filters
			* have access to the values.
			*/
			add_filter( 'render_block_context', $filter_block_context, 1 );

			/*
			* We construct a new WP_Block instance from the parsed block so that
			* it'll receive any changes made by the `render_block_data` filter.
			*/
			$block_content = ( new WP_Block( $block->parsed_block ) )->render( array( 'dynamic' => false ) );

			remove_filter( 'render_block_context', $filter_block_context, 1 );

			$comment_classes = comment_class( '', $comment->comment_ID, $comment->comment_post_ID, false );

			$content .= sprintf( '<li id="comment-%1$s" %2$s>%3$s</li>', $comment->comment_ID, $comment_classes, $block_content );
		}

		return $content;
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 * @return string Rendered block content.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( empty( $block->context['postId'] ) ) {
			return '';
		}

		if ( post_password_required( $block->context['postId'] ) ) {
			return;
		}

		$comment_query = new WP_Comment_Query(
			build_comment_query_vars_from_block( $block )
		);

		// Get an array of comments for the current post.
		$comments = $comment_query->get_comments();
		if ( count( $comments ) === 0 ) {
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
			$this->block_product_review_template_render_comments( $comments, $block )
		);
	}
}
