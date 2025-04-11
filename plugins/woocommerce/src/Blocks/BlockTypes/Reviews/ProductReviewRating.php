<?php declare( strict_types = 1 );
namespace Automattic\WooCommerce\Blocks\BlockTypes\Reviews;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;

/**
 * ProductReviewRating class.
 */
class ProductReviewRating extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-review-rating';

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return string[]|null
	 */
	protected function get_block_type_style() {
		return null;
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
		if ( ! isset( $block->context['commentId'] ) ) {
			return '';
		}

		$rating = intval( get_comment_meta( $block->context['commentId'], 'rating', true ) );

		$html = '';

		if ( 0 < $rating ) {
			// translators: %s: Rating.
			$label = sprintf( __( 'Rated %s out of 5', 'woocommerce' ), $rating );
			$html  = sprintf(
				'<div class="wc-block-product-review-rating__container">
					<div class="wc-block-product-review-rating__stars" role="img" aria-label="%1$s">
						%2$s
					</div>
				</div>
				',
				esc_attr( $label ),
				wc_get_star_rating_html( $rating )
			);
		}

		return sprintf(
			'<div %1$s>
				%2$s
			</div>',
			get_block_wrapper_attributes(),
			$html
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
