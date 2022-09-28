<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductButton class.
 */
class ProductButton extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-button';

	/**
	 * API version name.
	 *
	 * @var string
	 */
	protected $api_version = '2';

	/**
	 * Get block supports. Shared with the frontend.
	 * IMPORTANT: If you change anything here, make sure to update the JS file too.
	 *
	 * @return array
	 */
	protected function get_block_type_supports() {
		return array(
			'color'                  =>
			array(
				'background'                      => true,
				'link'                            => false,
				'text'                            => true,
				'__experimentalSkipSerialization' => true,
			),
			'__experimentalBorder'   =>
			array(
				'radius'                          => true,
				'__experimentalSkipSerialization' => true,
			),
			'typography'             =>
			array(
				'fontSize'                        => true,
				'__experimentalFontWeight'        => true,
				'__experimentalSkipSerialization' => true,
			),
			'__experimentalSelector' => '.wp-block-button.wc-block-components-product-button .wc-block-components-product-button__button',
		);
	}

	/**
	 * It is necessary to register and enqueues assets during the render phase because we want to load assets only if the block has the content.
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
	 * Include and render the block
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

		$post_id = $block->context['postId'];
		$product = wc_get_product( $post_id );

		if ( $product ) {
			$cart_redirect_after_add = get_option( 'woocommerce_cart_redirect_after_add' ) === 'yes';
			$html_element            = ( ! $product->has_options() && $product->is_purchasable() && $product->is_in_stock() && ! $cart_redirect_after_add ) ? 'button' : 'a';
			$styles_and_classes      = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array( 'border_radius', 'font_size', 'text_color' ) );

			return apply_filters(
				'woocommerce_loop_add_to_cart_link',
				sprintf(
					'<div class="wp-block-button wc-block-components-product-button wc-block-grid__product-add-to-cart">
					<%s href="%s" rel="nofollow" data-product_id="%s" data-product_sku="%s" class="wp-block-button__link %s wc-block-components-product-button__button product_type_%s %s" style="%s">%s</%s>
				</div>',
					$html_element,
					esc_url( $product->add_to_cart_url() ),
					esc_attr( $product->get_id() ),
					esc_attr( $product->get_sku() ),
					$product->is_purchasable() ? 'ajax_add_to_cart add_to_cart_button' : '',
					esc_attr( $product->get_type() ),
					$styles_and_classes['classes'],
					$styles_and_classes['styles'],
					esc_html( $product->add_to_cart_text() ),
					$html_element
				),
				$product
			);
		}
	}
}
