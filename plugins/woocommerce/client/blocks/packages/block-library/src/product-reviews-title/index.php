<?php
/**
 * Server-side rendering of the `woocommerce/product-reviews-title` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

/**
 * Gets the product reviews title.
 *
 * @since 11.0.0
 *
 * @param array      $attributes Block attributes.
 * @param WC_Product $product    Product instance.
 * @return string Reviews title.
 */
function get_block_woocommerce_product_reviews_title_text( array $attributes, WC_Product $product ): string {
	$show_product_title = ! empty( $attributes['showProductTitle'] );
	$show_reviews_count = ! empty( $attributes['showReviewsCount'] );
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
 * Renders the `woocommerce/product-reviews-title` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block content.
 */
function render_block_woocommerce_product_reviews_title( $attributes, $content, $block ): string {
	if ( post_password_required() || ! $block instanceof WP_Block || ! isset( $block->context['postId'] ) ) {
		return '';
	}

	$post_id = absint( $block->context['postId'] );
	$product = wc_get_product( $post_id );

	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$align_class_name   = isset( $attributes['textAlign'] ) && is_string( $attributes['textAlign'] ) ? 'has-text-align-' . sanitize_key( $attributes['textAlign'] ) : '';
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $align_class_name ) );
	$level              = isset( $attributes['level'] ) ? max( 1, min( 6, absint( $attributes['level'] ) ) ) : 2;
	$tag_name           = 'h' . $level;
	$reviews_title      = get_block_woocommerce_product_reviews_title_text( $attributes, $product );

	return sprintf(
		'<%1$s id="reviews" %2$s>%3$s</%1$s>',
		$tag_name,
		$wrapper_attributes,
		esc_html( $reviews_title )
	);
}

/**
 * Registers the `woocommerce/product-reviews-title` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_reviews_title(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_reviews_title',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_reviews_title' );
