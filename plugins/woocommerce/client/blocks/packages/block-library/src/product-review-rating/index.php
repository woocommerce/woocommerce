<?php
/**
 * Server-side rendering of the `woocommerce/product-review-rating` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

/**
 * Renders the `woocommerce/product-review-rating` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block content.
 */
function render_block_woocommerce_product_review_rating( $attributes, $content, $block ): string {
	if ( ! $block instanceof WP_Block || ! isset( $block->context['commentId'] ) ) {
		return '';
	}

	$rating = intval( get_comment_meta( $block->context['commentId'], 'rating', true ) );
	$html   = '';

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
 * Registers the `woocommerce/product-review-rating` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_review_rating(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_review_rating',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_review_rating' );
