<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Enums\ProductType;
use WP_Block;

/**
 * ProductSKU class.
 */
class ProductSKU extends AbstractBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-sku';

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
		if ( ! empty( $content ) ) {
			parent::register_block_type_assets();
			$this->register_chunk_translations( [ $this->block_name ] );
			return $content;
		}

		$post_id = isset( $block->context['postId'] ) ? $block->context['postId'] : '';
		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return '';
		}

		$product_sku                         = $product->get_sku();
		$is_descendant_of_product_collection = isset( $block->context['query']['isProductCollectionBlock'] );
		$is_interactive                      = ! $is_descendant_of_product_collection && $product->is_type( ProductType::VARIABLE );

		if ( '' === $product_sku && ! $is_interactive ) {
			return '';
		}

		$hide_when_unavailable  = '' === $product_sku && ( $attributes['hideWhenUnavailable'] ?? true );
		$interactive_attributes = '';
		$sku_fallback           = '';

		if ( $is_interactive ) {
			wp_enqueue_script_module( 'woocommerce/product-elements' );
			$interactive_attributes = 'data-wp-interactive="woocommerce/products" data-wp-text="state.productInContext.sku"';

			if ( ! $hide_when_unavailable ) {
				$sku_fallback = sprintf(
					'<span class="wc-block-components-product-sku__fallback">%s</span>',
					'' === $product_sku ? esc_html__( 'N/A', 'woocommerce' ) : esc_html( $product_sku )
				);
			}
		}

		$styles_and_classes = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );
		if ( $hide_when_unavailable ) {
			$styles_and_classes['classes'] .= ' wc-block-components-product-sku--hide-when-unavailable';
		}

		$prefix = isset( $attributes['prefix'] ) ? wp_kses_post( ( $attributes['prefix'] ) ) : __( 'SKU: ', 'woocommerce' );
		if ( ! empty( $prefix ) ) {
			$prefix = sprintf( '<span class="wp-block-post-terms__prefix">%s</span>', $prefix );
		}

		$suffix = isset( $attributes['suffix'] ) ? wp_kses_post( ( $attributes['suffix'] ) ) : '';
		if ( ! empty( $suffix ) ) {
			$suffix = sprintf( '<span class="wp-block-post-terms__suffix">%s</span>', $suffix );
		}

		return sprintf(
			'<div class="wc-block-components-product-sku wc-block-grid__product-sku wp-block-woocommerce-product-sku product_meta wp-block-post-terms %1$s" style="%2$s">
				%3$s
				<span class="sku" %4$s>%5$s</span>%6$s
				%7$s
			</div>',
			esc_attr( $styles_and_classes['classes'] ),
			esc_attr( $styles_and_classes['styles'] ?? '' ),
			$prefix,
			$interactive_attributes,
			esc_html( $product_sku ),
			$sku_fallback,
			$suffix
		);
	}
}
