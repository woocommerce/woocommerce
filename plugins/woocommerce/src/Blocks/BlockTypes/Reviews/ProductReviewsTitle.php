<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes\Reviews;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;

/**
 * ProductReviewsTitle class.
 */
class ProductReviewsTitle extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-reviews-title';

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
		if ( post_password_required() ) {
			return;
		}
		$post_id = $block->context['postId'];
		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return '';
		}
	
		$align_class_name   = empty( $attributes['textAlign'] ) ? '' : "has-text-align-{$attributes['textAlign']}";
		$show_product_title = ! empty( $attributes['showProductTitle'] ) && $attributes['showProductTitle'];
		$show_reviews_count = ! empty( $attributes['showReviewsCount'] ) && $attributes['showReviewsCount'];
		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $align_class_name ) );
		$reviews_count      = $product->get_review_count();
		/* translators: %s: Product title. */
		$product_title = sprintf( __( '&#8220;%s&#8221;', 'woocommerce' ), $product->get_title() );
		$tag_name   = 'h2';
		if ( isset( $attributes['level'] ) ) {
			$tag_name = 'h' . $attributes['level'];
		}
	
		if ( '0' === $reviews_count ) {
			return;
		}
	
		if ( $show_reviews_count ) {
			if ( $show_product_title ) {
				if ( '1' === $reviews_count ) {
					/* translators: %s: Post title. */
					$reviews_title = sprintf( __( 'One review for %s', 'woocommerce' ), $product_title );
				} else {
					$reviews_title = sprintf(
						/* translators: 1: Number of reviews, 2: Product title. */
						_n(
							'%1$s review for %2$s',
							'%1$s reviews for %2$s',
							$reviews_count,
							'woocommerce'
						),
						number_format_i18n( $reviews_count ),
						$product_title
					);
				}
			} elseif ( '1' === $reviews_count ) {
				$reviews_title = __( 'One review', 'woocommerce' );
			} else {
				$reviews_title = sprintf(
					/* translators: %s: Number of reviews. */
					_n( '%s review', '%s reviews', $reviews_count, 'woocommerce' ),
					number_format_i18n( $reviews_count )
				);
			}
		} elseif ( $show_product_title ) {
			if ( '1' === $reviews_count ) {
				/* translators: %s: Product title. */
				$reviews_title = sprintf( __( 'Review for %s', 'woocommerce' ), $product_title );
			} else {
				/* translators: %s: Product title. */
				$reviews_title = sprintf( __( 'Reviews for %s', 'woocommerce' ), $product_title );
			}
		} elseif ( '1' === $reviews_count ) {
			$reviews_title = __( 'Review', 'woocommerce' );
		} else {
			$reviews_title = __( 'Reviews', 'woocommerce' );
		}
	
		return sprintf(
			'<%1$s id="reviews" %2$s>%3$s</%1$s>',
			$tag_name,
			$wrapper_attributes,
			$reviews_title
		);
	}
}
