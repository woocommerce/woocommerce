<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Enums\ProductType;

/**
 * ProductPrice class.
 */
class ProductPrice extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;


	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-price';

	/**
	 * API version name.
	 *
	 * @var string
	 */
	protected $api_version = '3';

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}

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

		if ( $product ) {
			$styles_and_classes = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );

			$is_descendant_of_product_collection       = isset( $block->context['query']['isProductCollectionBlock'] );
			$is_descendant_of_grouped_product_selector = isset( $block->context['isDescendantOfGroupedProductSelector'] );
			$is_interactive                            = ! $is_descendant_of_product_collection && ! $is_descendant_of_grouped_product_selector && $product->is_type( ProductType::VARIABLE );

			$wrapper_attributes     = array(
				'style' => $styles_and_classes['styles'] ?? '',
				'class' => $styles_and_classes['classes'] ?? '',
			);
			$interactive_attributes = '';
			$context_directive      = '';

			if ( $is_interactive ) {
				// phpcs:ignore Generic.Commenting.DocComment.MissingShort -- Type hint for PHPStan.
				/** @var \WC_Product_Variable $product */
				// Check if variation prices differ (replicates logic from WC_Product_Variable::get_available_variation).
				$prices_vary = $product->get_variation_sale_price( 'min' ) !== $product->get_variation_sale_price( 'max' )
					|| $product->get_variation_regular_price( 'min' ) !== $product->get_variation_regular_price( 'max' );

				$formatted_variations_data = array();

				if ( $prices_vary ) {
					$variations_data = $product->get_available_variations( 'objects' );

					foreach ( $variations_data as $variation ) {
						/**
						 * Filter whether to show variation price.
						 * Replicates the filter from WC_Product_Variable::get_available_variation().
						 *
						 * @since 2.4.0
						 *
						 * @param bool                  $show_price Whether to show the price.
						 * @param \WC_Product_Variable  $product    The variable product.
						 * @param \WC_Product_Variation $variation  The variation.
						 */
						$show_variation_price = apply_filters(
							'woocommerce_show_variation_price',
							true,
							$product,
							$variation
						);

						if ( ! $show_variation_price ) {
							continue;
						}

						$formatted_variations_data[ $variation->get_id() ] = array(
							'price_html' => '<span class="price">' . $variation->get_price_html() . '</span>',
						);
					}
				}

				if ( empty( $formatted_variations_data ) ) {
					$is_interactive = false;
				} else {
					wp_interactivity_config(
						'woocommerce',
						array(
							'products' => array(
								$product->get_id() => array(
									'price_html' => $product->get_price_html(),
									'variations' => $formatted_variations_data,
								),
							),
						)
					);

					wp_enqueue_script_module( 'woocommerce/product-elements' );
					$wrapper_attributes['data-wp-interactive'] = 'woocommerce/product-elements';
					$context_directive                         = wp_interactivity_data_wp_context(
						array(
							'productElementKey' => 'price_html',
						)
					);
					$interactive_attributes                    = 'data-wp-watch="callbacks.updateValue" aria-live="polite" aria-atomic="true"';
				}
			}

			return sprintf(
				'<div %1$s %2$s><div class="wc-block-components-product-price wc-block-grid__product-price" %3$s>
					%4$s
				</div></div>',
				get_block_wrapper_attributes( $wrapper_attributes ),
				$context_directive,
				$interactive_attributes,
				$product->get_price_html()
			);
		}
	}
}
