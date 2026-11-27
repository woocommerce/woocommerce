<?php declare( strict_types = 1 );
namespace Automattic\WooCommerce\Blocks\BlockTypes\Reviews;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;

/**
 * ProductReviewsPaginationNumbers class.
 */
class ProductReviewsPaginationNumbers extends AbstractBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-reviews-pagination-numbers';

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		// Bail out early if the post ID is not set for some reason.
		if ( empty( $block->context['postId'] ) ) {
			return '';
		}

		$comment_vars = build_comment_query_vars_from_block( $block );

		$total   = ( new \WP_Comment_Query( $comment_vars ) )->max_num_pages;
		$current = ! empty( $comment_vars['paged'] ) ? $comment_vars['paged'] : null;

		// Render links.
		$content = paginate_comments_links(
			array(
				'total'     => $total,
				'current'   => $current,
				'prev_next' => false,
				'echo'      => false,
			)
		);

		if ( empty( $content ) ) {
			return '';
		}

		$wrapper_attributes = get_block_wrapper_attributes();

		return sprintf(
			'<div %1$s>%2$s</div>',
			$wrapper_attributes,
			$content
		);
	}
}
