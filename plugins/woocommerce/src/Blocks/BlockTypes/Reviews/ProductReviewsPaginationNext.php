<?php declare( strict_types = 1 );
namespace Automattic\WooCommerce\Blocks\BlockTypes\Reviews;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;

/**
 * ProductReviewsPaginationNext class.
 */
class ProductReviewsPaginationNext extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-reviews-pagination-next';

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

		$comment_vars     = build_comment_query_vars_from_block( $block );
		$max_page         = ( new \WP_Comment_Query( $comment_vars ) )->max_num_pages;
		$default_label    = __( 'Newer Reviews', 'woocommerce' );
		$label            = isset( $attributes['label'] ) && ! empty( $attributes['label'] ) ? $attributes['label'] : $default_label;
		$pagination_arrow = $this->get_pagination_arrow( $block );

		$filter_link_attributes = static function () {
			return get_block_wrapper_attributes();
		};
		add_filter( 'next_comments_link_attributes', $filter_link_attributes );

		if ( $pagination_arrow ) {
			$label .= $pagination_arrow;
		}

		$next_comments_link = get_next_comments_link( $label, $max_page, $comment_vars['paged'] ?? null );

		remove_filter( 'next_posts_link_attributes', $filter_link_attributes );

		if ( ! isset( $next_comments_link ) ) {
			return '';
		}
		return $next_comments_link;
	}

	/**
	 * Get the pagination arrow.
	 *
	 * @param \WP_Block $block Block instance.
	 * @return string|null
	 */
	protected function get_pagination_arrow( $block ) {
		$arrow_map = array(
			'none'    => '',
			'arrow'   => '→',
			'chevron' => '»',
		);
		if ( ! empty( $block->context['reviews/paginationArrow'] ) && ! empty( $arrow_map[ $block->context['reviews/paginationArrow'] ] ) ) {
			$arrow_attribute = $block->context['reviews/paginationArrow'];
			$arrow           = $arrow_map[ $block->context['reviews/paginationArrow'] ];
			$arrow_classes   = "wp-block-woocommerce-product-reviews-pagination-next-arrow is-arrow-$arrow_attribute";
			return "<span class='$arrow_classes' aria-hidden='true'>$arrow</span>";
		}
		return null;
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
