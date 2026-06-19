<?php
/**
 * Server-side rendering of the `woocommerce/product-results-count` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * Renders the `woocommerce/product-results-count` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered product results count block.
 */
function render_block_woocommerce_product_results_count( $attributes, $content, $block ) {
	ob_start();
	echo '<div>';
	woocommerce_result_count();
	echo '</div>';
	$product_results_count = ob_get_clean();

	if ( false === $product_results_count ) {
		return '';
	}

	$processor = new WP_HTML_Tag_Processor( $product_results_count );
	$processor->next_tag( array( 'tag_name' => 'div' ) );

	$parsed_style_attributes = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );
	$classes                 = array_merge(
		explode( ' ', $parsed_style_attributes['classes'] ),
		array(
			'woocommerce',
			'wc-block-product-results-count',
			'wp-block-woocommerce-product-results-count',
		)
	);

	$processor->set_attribute( 'class', implode( ' ', $classes ) );
	$processor->set_attribute( 'style', $parsed_style_attributes['styles'] );
	$processor->set_attribute( 'data-wp-interactive', 'woocommerce/product-results-count' );
	$processor->set_attribute(
		'data-wp-router-region',
		'wc-product-results-count-' . ( isset( $block->context['queryId'] ) ? $block->context['queryId'] : 0 )
	);

	return $processor->get_updated_html();
}

/**
 * Registers the `woocommerce/product-results-count` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_results_count(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_results_count',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_results_count' );
