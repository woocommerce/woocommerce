<?php
/**
 * Server-side rendering of the `woocommerce/product-description` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

/**
 * Renders the `woocommerce/product-description` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block output.
 */
function render_block_woocommerce_product_description( $attributes, $content, $block ): string {
	static $seen_ids = array();

	if ( ! $block instanceof WP_Block || ! isset( $block->context['postId'] ) ) {
		return '';
	}

	$product_id = (int) $block->context['postId'];

	if ( isset( $seen_ids[ $product_id ] ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ) {
			return __( '[product description rendering halted]', 'woocommerce' );
		}
		return '';
	}

	$seen_ids[ $product_id ] = true;

	$product = wc_get_product( $product_id );
	if ( ! $product instanceof WC_Product ) {
		unset( $seen_ids[ $product_id ] );
		return '';
	}

	$description = $product->get_description();
	/**
	 * This filter is documented in wp-includes/post-template.php.
	 * We follow core/content block to replace ]]> with ]&gt;.
	 */
	// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
	$description = apply_filters( 'the_content', str_replace( ']]>', ']]&gt;', $description ) );
	if ( empty( $description ) ) {
		unset( $seen_ids[ $product_id ] );
		return '';
	}

	unset( $seen_ids[ $product_id ] );

	$wrapper_attributes = get_block_wrapper_attributes(
		array( 'class' => 'wc-block-product-description' )
	);

	return sprintf(
		'<div %1$s>%2$s</div>',
		$wrapper_attributes,
		$description
	);
}

/**
 * Registers the `woocommerce/product-description` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_description(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_description',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_description' );
