<?php
/**
 * Server-side rendering of the `woocommerce/product-reviews-pagination` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

/**
 * Renders the `woocommerce/product-reviews-pagination` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block content.
 */
function render_block_woocommerce_product_reviews_pagination( $attributes, $content, $block ): string {
	if ( '' === trim( $content ) || post_password_required() ) {
		return '';
	}

	$classes            = isset( $attributes['style']['elements']['link']['color']['text'] ) ? 'has-link-color' : '';
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class'               => $classes,
			'data-wp-interactive' => 'woocommerce/product-reviews',
		)
	);

	$processor = new WP_HTML_Tag_Processor( $content );
	while ( $processor->next_tag( array( 'tag_name' => 'a' ) ) ) {
		$processor->set_attribute( 'data-wp-on--click', 'actions.navigate' );
	}

	return sprintf(
		'<div %1$s>%2$s</div>',
		$wrapper_attributes,
		$processor->get_updated_html()
	);
}

/**
 * Registers the `woocommerce/product-reviews-pagination` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_reviews_pagination(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_reviews_pagination',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_reviews_pagination' );
