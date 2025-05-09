<?php
declare( strict_types=1 );

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
		return [ 'postId', 'mode', 'cropImages' ];
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
		if ( ! isset( $block->context ) ) {
			return '';
		}

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );
		$post_id            = $block->context['postId'];

		if ( ! $post_id ) {
			return '';
		}

		$product = wc_get_product( $post_id );

		if ( ! $product instanceof \WC_Product ) {
			return '';
		}

		$product_gallery_images = ProductGalleryUtils::get_product_gallery_image_data( $product, 'woocommerce_thumbnail' );
		// Don't show the thumbnails block if there is only one image.
		if ( count( $product_gallery_images ) <= 1 ) {
			return '';
		}

		$thumbnail_size   = str_replace( '%', '', $attributes['thumbnailSize'] ?? '25%' );
		$thumbnails_class = 'wc-block-product-gallery-thumbnails--thumbnails-size-' . $thumbnail_size;

		ob_start();
		?>
		<div
			class="wc-block-product-gallery-thumbnails <?php echo esc_attr( $classes_and_styles['classes'] . ' ' . $thumbnails_class ); ?>"
			style="<?php echo esc_attr( $classes_and_styles['styles'] ); ?>"
			data-wp-interactive="woocommerce/product-gallery"
			data-wp-class--wc-block-product-gallery-thumbnails--overflow-top="context.thumbnailsOverflow.top"
			data-wp-class--wc-block-product-gallery-thumbnails--overflow-bottom="context.thumbnailsOverflow.bottom"
			data-wp-class--wc-block-product-gallery-thumbnails--overflow-left="context.thumbnailsOverflow.left"
			data-wp-class--wc-block-product-gallery-thumbnails--overflow-right="context.thumbnailsOverflow.right">
			<div
				class="wc-block-product-gallery-thumbnails__scrollable"
				data-wp-init="actions.onScroll"
				data-wp-on--scroll="actions.onScroll">
				<?php foreach ( $product_gallery_images as $index => $image ) : ?>
					<div class="wc-block-product-gallery-thumbnails__thumbnail">
						<img
							class="wc-block-product-gallery-thumbnails__thumbnail__image <?php echo 0 === $index ? 'is-active' : ''; ?>"
							data-image-id="<?php echo esc_attr( $image['id'] ); ?>"
							src="<?php echo esc_attr( $image['src'] ); ?>"
							srcset="<?php echo esc_attr( $image['srcset'] ); ?>"
							sizes="<?php echo esc_attr( $image['sizes'] ); ?>"
							data-wp-on--click="actions.selectCurrentImage"
							data-wp-on--keydown="actions.onThumbnailKeyDown"
							data-wp-watch="callbacks.toggleActiveImageAttributes"
							decoding="async"
							tabindex="0"
							draggable="false"
							loading="lazy" />
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		$template = ob_get_clean();

		return $template;
	}
}
