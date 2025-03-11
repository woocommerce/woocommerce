<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes\Reviews;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

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
	 * @param array $attributes Block attributes.
	 * @return string Rendered block content.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( ! isset( $block->context['commentId'] ) ) {
			return '';
		}

		$rating = intval( get_comment_meta( $block->context['commentId'], 'rating', true ) );

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );

		$html = '';

		if ( 0 < $rating ) {
			$label = sprintf( __( 'Rated %s out of 5', 'woocommerce' ), $rating );
			$html  = sprintf(
				'<div class="wc-block-components-product-review-rating__container">
					<div class="wc-block-components-product-review-rating__stars" role="img" aria-label="%1$s">
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
			get_block_wrapper_attributes(
				array(
					'class' => esc_attr( $classes_and_styles['classes'] ),
					'style' => $classes_and_styles['styles'],
				)
			),
			$html
		);
	}
}
