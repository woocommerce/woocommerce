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
		$pagination_arrow = $this->get_pagination_arrow( $block );

		if ( $pagination_arrow ) {
			$label = $pagination_arrow . $label;
		}

		$comment_vars  = build_comment_query_vars_from_block( $block );
		$comment_order = strtolower( (string) get_option( 'comment_order' ) );

		// With DESC ordering the default page is 1 (newest), so "Older" advances to higher pages.
		if ( 'desc' === $comment_order && ! get_query_var( 'cpage' ) ) {
			$comment_vars['paged'] = 1;
		}

		$filter_link_attributes = static function () {
			return get_block_wrapper_attributes();
		};

		if ( 'desc' === $comment_order ) {
			add_filter( 'next_comments_link_attributes', $filter_link_attributes );
			$max_page               = (int) ( new \WP_Comment_Query( $comment_vars ) )->max_num_pages;
			$page                   = (int) ( $comment_vars['paged'] ?? 1 );
			$previous_comments_link = $this->get_older_reviews_link_for_desc( $label, $page, $max_page );
			remove_filter( 'next_comments_link_attributes', $filter_link_attributes );
		} else {
			add_filter( 'previous_comments_link_attributes', $filter_link_attributes );
			$previous_comments_link = get_previous_comments_link( $label, $comment_vars['paged'] ?? null );
			remove_filter( 'previous_comments_link_attributes', $filter_link_attributes );
		}

		if ( empty( $previous_comments_link ) ) {
			return '';
		}

		return $previous_comments_link;
	}

	/**
	 * Build an "Older Reviews" link for newest-first (DESC) pagination.
	 *
	 * Core's get_comments_pagenum_link() omits cpage when the target is the last page and
	 * default_comments_page is newest. With DESC ordering that bare product URL means page 1,
	 * so the final older page must always carry an explicit cpage.
	 *
	 * @param string $label    Link label.
	 * @param int    $page     Current page number.
	 * @param int    $max_page Total comment pages.
	 * @return string
	 */
	private function get_older_reviews_link_for_desc( string $label, int $page, int $max_page ): string {
		if ( $page < 1 ) {
			$page = 1;
		}

		$next_page = $page + 1;
		if ( $next_page > $max_page ) {
			return '';
		}

		global $wp_rewrite;

		$permalink = get_permalink();
		if ( ! is_string( $permalink ) || '' === $permalink ) {
			return '';
		}

		if ( $wp_rewrite->using_permalinks() ) {
			$url = user_trailingslashit( trailingslashit( $permalink ) . $wp_rewrite->comments_pagination_base . '-' . $next_page, 'commentpaged' );
		} else {
			$url = add_query_arg( 'cpage', $next_page, $permalink );
		}

		$url .= '#comments';

		/**
		 * Filters the anchor tag attributes for the next comments page link.
		 *
		 * Reused here because DESC "Older Reviews" advances the page number the same way
		 * get_next_comments_link() does for ASC "Newer" navigation.
		 *
		 * @since 2.7.0
		 *
		 * @param string $attributes Attributes for the anchor tag.
		 */
		$attr = apply_filters( 'next_comments_link_attributes', '' );

		return sprintf(
			'<a href="%1$s" %2$s>%3$s</a>',
			esc_url( $url ),
			$attr,
			preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label )
		);
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
			'arrow'   => '←',
			'chevron' => '«',
		);

		if ( ! empty( $block->context['reviews/paginationArrow'] ) && ! empty( $arrow_map[ $block->context['reviews/paginationArrow'] ] ) ) {
			$arrow_attribute = $block->context['reviews/paginationArrow'];
			$arrow           = $arrow_map[ $block->context['reviews/paginationArrow'] ];
			$arrow_classes   = "wp-block-woocommerce-product-reviews-pagination-previous-arrow is-arrow-$arrow_attribute";
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
