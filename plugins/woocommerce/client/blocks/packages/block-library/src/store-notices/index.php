<?php
/**
 * Server-side rendering of the `woocommerce/store-notices` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * Renders the `woocommerce/store-notices` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered store notices block.
 */
function render_block_woocommerce_store_notices( $attributes, $content, $block ) {
	if ( ! function_exists( 'wc_print_notices' ) ) {
		return $content;
	}

	ob_start();
	woocommerce_output_all_notices();
	$notices = ob_get_clean();

	if ( ! $notices ) {
		return '';
	}

	$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );

	return sprintf(
		'<div %1$s>%2$s</div>',
		get_block_wrapper_attributes(
			array(
				'class' => 'wc-block-store-notices woocommerce ' . esc_attr( $classes_and_styles['classes'] ),
			)
		),
		wc_kses_notice( $notices )
	);
}

/**
 * Registers the `woocommerce/store-notices` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_store_notices(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_store_notices',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_store_notices' );
