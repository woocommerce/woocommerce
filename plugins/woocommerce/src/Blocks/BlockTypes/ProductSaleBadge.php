<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductSaleBadge class.
 */
class ProductSaleBadge extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-sale-badge';

	/**
	 * API version name.
	 *
	 * @var string
	 */
	protected $api_version = '3';

	/**
	 * Overwrite parent method to prevent script registration.
	 *
	 * It is necessary to register and enqueues assets during the render
	 * phase because we want to load assets only if the block has the content.
	 */
	protected function register_block_type_assets() {
		return null;
	}

	/**
	 * Register the context.
	 */
	protected function get_block_type_uses_context() {
		return [ 'query', 'queryId', 'postId' ];
	}

	/**
	 * Include and render the block.
	 *
	 * @param array    $attributes Block attributes. Default empty array.
	 * @param string   $content    Block content. Default empty string.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$post_id = isset( $block->context['postId'] ) ? $block->context['postId'] : '';
		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return null;
		}

		$is_on_sale = $product->is_on_sale();

		if ( ! $is_on_sale ) {
			return null;
		}

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );

		$classname = StyleAttributesUtils::get_classes_by_attributes( $attributes, array( 'extra_classes' ) );

		$align = isset( $attributes['align'] ) ? $attributes['align'] : '';

		/**
		 * Filters the product sale badge text.
		 *
		 * @hook woocommerce_sale_badge_text
		 * @since 10.0.0
		 *
		 * @param string $sale_text The sale badge text.
		 * @param WC_Product $product The product object.
		 * @return string The filtered sale badge text.
		 */
		$sale_text = apply_filters( 'woocommerce_sale_badge_text', __( 'Sale', 'woocommerce' ), $product );

		$output  = '<div class="wp-block-woocommerce-product-sale-badge ' . esc_attr( $classname ) . '">';
		$output .= sprintf( '<div class="wc-block-components-product-sale-badge %1$s wc-block-components-product-sale-badge--align-%2$s" style="%3$s">', esc_attr( $classes_and_styles['classes'] ), esc_attr( $align ), esc_attr( $classes_and_styles['styles'] ) );
		$output .= '<span class="wc-block-components-product-sale-badge__text" aria-hidden="true">' . esc_html( $sale_text ) . '</span>';
		$output .= '<span class="screen-reader-text">'
						. __( 'Product on sale', 'woocommerce' )
					. '</span>';
		$output .= '</div></div>';

		return $output;
	}
}
