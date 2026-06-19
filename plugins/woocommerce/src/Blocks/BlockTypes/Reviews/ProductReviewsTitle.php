<?php declare( strict_types = 1 );
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
	 * Get the reviews title.
	 *
	 * @param array      $attributes Block attributes.
	 * @param WC_Product $product Product instance.
	 * @return string
	 */
	private function get_reviews_title( $attributes, $product ) {
		$show_product_title = ! empty( $attributes['showProductTitle'] ) && $attributes['showProductTitle'];
		$show_reviews_count = ! empty( $attributes['showReviewsCount'] ) && $attributes['showReviewsCount'];
		$reviews_count      = $product->get_review_count();

		if ( $show_reviews_count && $show_product_title ) {
			return 1 === $reviews_count
				/* translators: %s: Product title. */
				? sprintf( __( 'One review for %s', 'woocommerce' ), $product->get_title() )
				: sprintf(
					/* translators: 1: Number of reviews, 2: Product title. */
					_n(
						'%1$s review for %2$s',
						'%1$s reviews for %2$s',
						$reviews_count,
						'woocommerce'
					),
					number_format_i18n( $reviews_count ),
					$product->get_title()
				);
		}

		if ( ! $show_reviews_count && $show_product_title ) {
			return 1 === $reviews_count
				/* translators: %s: Product title. */
				? sprintf( __( 'Review for %s', 'woocommerce' ), $product->get_title() )
				: sprintf(
					/* translators: %s: Product title. */
					__( 'Reviews for %s', 'woocommerce' ),
					$product->get_title()
				);
		}

		if ( $show_reviews_count && ! $show_product_title ) {
			return 1 === $reviews_count
				/* translators: %s: Number of reviews. */
				? __( 'One review', 'woocommerce' )
				: sprintf(
					/* translators: %s: Number of reviews. */
					_n( '%s review', '%s reviews', $reviews_count, 'woocommerce' ),
					number_format_i18n( $reviews_count )
				);
		}

		if ( 1 === $reviews_count ) {
			return __( 'Review', 'woocommerce' );
		}

		return __( 'Reviews', 'woocommerce' );
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
		if ( post_password_required() ) {
			return;
		}
		$post_id = $block->context['postId'];
		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return '';
		}

		$align_class_name   = empty( $attributes['textAlign'] ) ? '' : "has-text-align-{$attributes['textAlign']}";
		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $align_class_name ) );
		$reviews_count      = $product->get_review_count();
		$tag_name           = 'h2';
		if ( isset( $attributes['level'] ) ) {
			$tag_name = 'h' . $attributes['level'];
		}

		$reviews_title = $this->get_reviews_title( $attributes, $product );

		return sprintf(
			'<%1$s id="reviews" %2$s>%3$s</%1$s>',
			$tag_name,
			$wrapper_attributes,
			$reviews_title
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
