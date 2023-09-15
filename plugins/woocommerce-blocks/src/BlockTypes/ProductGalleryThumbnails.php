<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;

/**
 * ProductGalleryLargeImage class.
 */
class ProductGalleryThumbnails extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-gallery-thumbnails';

	/**
	 * It isn't necessary register block assets because it is a server side block.
	 */
	protected function register_block_type_assets() {
		return null;
	}

	/**
	 *  Register the context
	 *
	 * @return string[]
	 */
	protected function get_block_type_uses_context() {
		return [ 'productGalleryClientId', 'postId', 'thumbnailsNumberOfThumbnails', 'thumbnailsPosition' ];
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
		if ( isset( $block->context['thumbnailsPosition'] ) && '' !== $block->context['thumbnailsPosition'] && 'off' !== $block->context['thumbnailsPosition'] ) {
			if ( ! empty( $content ) ) {
				parent::register_block_type_assets();
				$this->register_chunk_translations( [ $this->block_name ] );
				return $content;
			}

			$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );

			$post_id           = isset( $block->context['postId'] ) ? $block->context['postId'] : '';
			$product           = wc_get_product( $post_id );
			$post_thumbnail_id = $product->get_image_id();
			$html              = '';

			if ( $product ) {
				$post_thumbnail_id      = $product->get_image_id();
				$product_gallery_images = ProductGalleryUtils::get_product_gallery_images( $post_id, 'thumbnail', array() );
				if ( $product_gallery_images && $post_thumbnail_id ) {
					$number_of_thumbnails = isset( $block->context['thumbnailsNumberOfThumbnails'] ) ? $block->context['thumbnailsNumberOfThumbnails'] : 3;
					$thumbnails_count     = 1;

					foreach ( $product_gallery_images as $product_gallery_image_html ) {
						if ( $thumbnails_count > $number_of_thumbnails ) {
							break;
						}

						$html .= '<div class="wp-block-woocommerce-product-gallery-thumbnails__thumbnail">';

						$processor = new \WP_HTML_Tag_Processor( $product_gallery_image_html );

						if ( $processor->next_tag() ) {
							$processor->set_attribute(
								'data-wc-on--click',
								'actions.woocommerce.thumbnails.handleClick'
							);

							$html .= $processor->get_updated_html();
						}

						$html .= '</div>';

						$thumbnails_count++;
					}
				}

				return sprintf(
					'<div class="wc-block-components-product-gallery-thumbnails wp-block-woocommerce-product-gallery-thumbnails %1$s" style="%2$s">
						%3$s
					</div>',
					esc_attr( $classes_and_styles['classes'] ),
					esc_attr( $classes_and_styles['styles'] ),
					$html
				);
			}
		}
	}
}
