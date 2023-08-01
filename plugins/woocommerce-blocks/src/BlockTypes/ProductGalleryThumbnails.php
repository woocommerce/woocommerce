<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

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
				$attachment_ids = $product->get_gallery_image_ids();
				if ( $attachment_ids && $post_thumbnail_id ) {
					$html                .= wc_get_gallery_image_html( $post_thumbnail_id, true );
					$number_of_thumbnails = isset( $block->context['thumbnailsNumberOfThumbnails'] ) ? $block->context['thumbnailsNumberOfThumbnails'] : 3;
					$thumbnails_count     = 1;

					foreach ( $attachment_ids as $attachment_id ) {
						if ( $thumbnails_count >= $number_of_thumbnails ) {
							break;
						}

						/**
						 * Filter the HTML markup for a single product image thumbnail in the gallery.
						 *
						 * @param string $thumbnail_html The HTML markup for the thumbnail.
						 * @param int    $attachment_id  The attachment ID of the thumbnail.
						 *
						 * @since 7.9.0
						 */
						$html .= apply_filters( 'woocommerce_single_product_image_thumbnail_html', wc_get_gallery_image_html( $attachment_id ), $attachment_id ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped

						$thumbnails_count++;
					}
				}

				return sprintf(
					'<div class="wc-block-components-product-gallery-thumbnails %1$s" style="%2$s">
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
