<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;

/**
 * ProductGalleryThumbnails class.
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
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 *  Register the context
	 *
	 * @return string[]
	 */
	protected function get_block_type_uses_context() {
		return [ 'productGalleryClientId', 'postId', 'thumbnailsNumberOfThumbnails', 'thumbnailsPosition', 'mode', 'cropImages' ];
	}

	/**
	 * Generate the View All markup.
	 *
	 * @param int $remaining_thumbnails_count The number of thumbnails that are not displayed.
	 *
	 * @return string
	 */
	protected function generate_view_all_html( $remaining_thumbnails_count ) {
		$view_all_html = '<div class="wc-block-product-gallery-thumbnails__thumbnail__overlay wc-block-product-gallery-dialog-on-click" data-wc-on--click="actions.openDialog">
			<span class="wc-block-product-gallery-thumbnails__thumbnail__remaining-thumbnails-count wc-block-product-gallery-dialog-on-click">+%1$s</span>
			<span class="wc-block-product-gallery-thumbnails__thumbnail__view-all wc-block-product-gallery-dialog-on-click">%2$s</span>
			</div>';

		return sprintf(
			$view_all_html,
			esc_html( $remaining_thumbnails_count ),
			esc_html__( 'View all', 'woo-gutenberg-products-block' )
		);
	}

	/**
	 * Inject View All markup into the product thumbnail HTML.
	 *
	 * @param string $thumbnail_html The thumbnail HTML.
	 * @param string $view_all_html  The view all HTML.
	 *
	 * @return string
	 */
	protected function inject_view_all( $thumbnail_html, $view_all_html ) {

		// Find the position of the last </div>.
		$pos = strrpos( $thumbnail_html, '</div>' );

		if ( false !== $pos ) {
			// Inject the view_all_html at the correct position.
			$html = substr_replace( $thumbnail_html, $view_all_html, $pos, 0 );

			return $html;
		}
	}

	/**
	 * Check if the thumbnails should be limited.
	 *
	 * @param string $mode                 Mode of the gallery. Expected values: 'standard'.
	 * @param int    $thumbnails_count     Current count of processed thumbnails.
	 * @param int    $number_of_thumbnails Number of thumbnails configured to display.
	 *
	 * @return bool
	 */
	protected function should_limit_thumbnails( $mode, $thumbnails_count, $number_of_thumbnails ) {
		return 'standard' === $mode && $thumbnails_count > $number_of_thumbnails;
	}

	/**
	 * Check if View All markup should be displayed.
	 *
	 * @param string $mode                   Mode of the gallery. Expected values: 'standard'.
	 * @param int    $thumbnails_count       Current count of processed thumbnails.
	 * @param array  $product_gallery_images Array of product gallery image HTML strings.
	 * @param int    $number_of_thumbnails   Number of thumbnails configured to display.
	 *
	 * @return bool
	 */
	protected function should_display_view_all( $mode, $thumbnails_count, $product_gallery_images, $number_of_thumbnails ) {
		return 'standard' === $mode &&
		$thumbnails_count === $number_of_thumbnails &&
		count( $product_gallery_images ) > $number_of_thumbnails;
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

			$post_id = $block->context['postId'] ?? '';
			$product = wc_get_product( $post_id );

			if ( $product ) {
				$crop_images            = $block->context['cropImages'] ?? false;
				$product_gallery_images = ProductGalleryUtils::get_product_gallery_images( $post_id, 'full', array(), 'wc-block-product-gallery-thumbnails__thumbnail', $crop_images );

				if ( $product_gallery_images && count( $product_gallery_images ) > 1 ) {
					$html                 = '';
					$number_of_thumbnails = isset( $block->context['thumbnailsNumberOfThumbnails'] ) ? $block->context['thumbnailsNumberOfThumbnails'] : 3;
					$mode                 = $block->context['mode'] ?? '';
					$thumbnails_count     = 1;

					foreach ( $product_gallery_images as $product_gallery_image_html ) {
						// Limit the number of thumbnails only in the standard mode (and not in dialog).
						if ( $this->should_limit_thumbnails( $mode, $thumbnails_count, $number_of_thumbnails ) ) {
							break;
						}

						// If not in dialog and it's the last thumbnail and the number of product gallery images is greater than the number of thumbnails settings output the View All markup.
						if ( $this->should_display_view_all( $mode, $thumbnails_count, $product_gallery_images, $number_of_thumbnails ) ) {
							$remaining_thumbnails_count = count( $product_gallery_images ) - $number_of_thumbnails;
							$product_gallery_image_html = $this->inject_view_all( $product_gallery_image_html, $this->generate_view_all_html( $remaining_thumbnails_count ) );
							$html                      .= $product_gallery_image_html;
						} else {
							$processor = new \WP_HTML_Tag_Processor( $product_gallery_image_html );

							if ( $processor->next_tag( 'img' ) ) {
								$processor->set_attribute(
									'data-wc-on--click',
									'actions.selectImage'
								);

								$html .= $processor->get_updated_html();
							}
						}

						$thumbnails_count++;
					}

					return sprintf(
						'<div class="wc-block-product-gallery-thumbnails wp-block-woocommerce-product-gallery-thumbnails %1$s" style="%2$s" data-wc-interactive=\'%4$s\'>
							%3$s
						</div>',
						esc_attr( $classes_and_styles['classes'] ),
						esc_attr( $classes_and_styles['styles'] ),
						$html,
						wp_json_encode( array( 'namespace' => 'woocommerce/product-gallery' ) )
					);
				}
			}
			return;
		}
	}
}
