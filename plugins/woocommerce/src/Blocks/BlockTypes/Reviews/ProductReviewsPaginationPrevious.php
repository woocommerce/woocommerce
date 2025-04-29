<?php declare( strict_types = 1 );
namespace Automattic\WooCommerce\Blocks\BlockTypes\Reviews;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;

/**
 * ProductReviewsPaginationPrevious class.
 */
class ProductReviewsPaginationPrevious extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-reviews-pagination-previous';

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$default_label    = __( 'Older Reviews', 'woocommerce' );
		$label            = isset( $attributes['label'] ) && ! empty( $attributes['label'] ) ? $attributes['label'] : $default_label;
		$pagination_arrow = get_comments_pagination_arrow( $block, 'previous' );
		if ( $pagination_arrow ) {
			$label = $pagination_arrow . $label;
		}

		$filter_link_attributes = static function () {
			return get_block_wrapper_attributes();
		};
		add_filter( 'previous_comments_link_attributes', $filter_link_attributes );

		$comment_vars           = build_comment_query_vars_from_block( $block );
		$previous_comments_link = get_previous_comments_link( $label, $comment_vars['paged'] ?? null );

		remove_filter( 'previous_comments_link_attributes', $filter_link_attributes );

		if ( ! isset( $previous_comments_link ) ) {
			return '';
		}

		return $previous_comments_link;
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

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return string|null
	 */
	protected function get_block_type_style() {
		return null;
	}
}
