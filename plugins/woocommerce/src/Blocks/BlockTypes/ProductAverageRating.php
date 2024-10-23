<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductAverageRating class.
 */
class ProductAverageRating extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-average-rating';

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
					'text'                            => true,
					'background'                      => true,
					'__experimentalSkipSerialization' => true,
				),
			'spacing'                =>
				array(
					'margin'                          => true,
					'padding'                         => true,
					'__experimentalSkipSerialization' => true,
				),
			'typography'             =>
				array(
					'fontSize'                        => true,
					'__experimentalFontWeight'        => true,
					'__experimentalSkipSerialization' => true,
				),
			'__experimentalSelector' => '.wc-block-components-product-average-rating',
		);
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
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
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
		$post_id = $block->context['postId'];
		$product = wc_get_product( $post_id );

		if ( ! $product || ! $product->get_review_count() ) {
			return '';
		}

		$styles_and_classes            = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );
		$text_align_styles_and_classes = StyleAttributesUtils::get_text_align_class_and_style( $attributes );

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => implode(
					' ',
					array_filter(
						[
							'wc-block-components-product-average-rating-counter',
							esc_attr( $text_align_styles_and_classes['class'] ?? '' ),
							esc_attr( $styles_and_classes['classes'] ),
						]
					)
				),
				'style' => esc_attr( $styles_and_classes['styles'] ?? '' ),
			)
		);

		return sprintf(
			'<div %1$s>%2$s</div>',
			$wrapper_attributes,
			$product->get_average_rating()
		);
	}
}
