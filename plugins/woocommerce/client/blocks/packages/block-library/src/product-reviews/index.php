<?php
/**
 * Server-side rendering of the `woocommerce/product-reviews` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * Renders the legacy `woocommerce/product-reviews` block output.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered legacy block output.
 */
function render_block_woocommerce_product_reviews_legacy( $attributes, $content, $block ): string {
	if ( ! is_singular( 'product' ) ) {
		return $content;
	}

	ob_start();

	rewind_posts();
	while ( have_posts() ) {
		the_post();
		comments_template();
	}

	$reviews = ob_get_clean();
	if ( ! is_string( $reviews ) ) {
		$reviews = '';
	}

	return sprintf(
		'<div class="wp-block-woocommerce-product-reviews %1$s">
			%2$s
		</div>',
		StyleAttributesUtils::get_classes_by_attributes( $attributes, array( 'extra_classes' ) ),
		$reviews
	);
}

/**
 * Renders the `woocommerce/product-reviews` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block content.
 */
function render_block_woocommerce_product_reviews( $attributes, $content, $block ): string {
	if ( ! $block instanceof WP_Block || empty( $block->parsed_block['innerBlocks'] ) ) {
		return render_block_woocommerce_product_reviews_legacy( $attributes, $content, $block );
	}

	if ( ! comments_open() ) {
		return '';
	}

	$processor = new WP_HTML_Tag_Processor( $content );
	if ( ! $processor->next_tag() ) {
		return $content;
	}

	$processor->set_attribute( 'data-wp-interactive', 'woocommerce/product-reviews' );
	$processor->set_attribute( 'data-wp-router-region', 'woocommerce/product-reviews' );

	return $processor->get_updated_html();
}

/**
 * Registers the `woocommerce/product-reviews` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_reviews(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_reviews',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_reviews' );
