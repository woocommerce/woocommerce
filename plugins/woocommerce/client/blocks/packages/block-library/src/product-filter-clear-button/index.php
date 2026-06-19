<?php
/**
 * Server-side rendering of the `woocommerce/product-filter-clear-button` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

/**
 * Renders the `woocommerce/product-filter-clear-button` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block output.
 */
function render_block_woocommerce_product_filter_clear_button( $attributes, $content, $block ): string {
	if ( is_admin() || wp_doing_ajax() || ! $block instanceof WP_Block ) {
		return '';
	}

	$removable_context = $block->context['woocommerce/removableItems'] ?? null;
	if ( empty( $removable_context ) || empty( $removable_context['items'] ) ) {
		return '';
	}

	$processor = new WP_HTML_Tag_Processor( $content );
	if ( $processor->next_tag( array( 'class_name' => 'wp-block-button__link' ) ) ) {
		$processor->set_attribute( 'data-wp-on--click', 'actions.removeAll' );
		$content = $processor->get_updated_html();
	}

	$content = str_replace( array( '<a', '</a>' ), array( '<button', '</button>' ), $content );

	return sprintf(
		'<div %1$s>%2$s</div>',
		get_block_wrapper_attributes(),
		$content
	);
}

/**
 * Registers the `woocommerce/product-filter-clear-button` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_filter_clear_button(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_filter_clear_button',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_filter_clear_button' );
