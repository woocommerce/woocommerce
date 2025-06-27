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
		return array( 'postId' );
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

		// We crop the images to square only if the aspect ratio is 1:1.
		// Otherwise, we show the uncropped and use object-fit to crop them.
		$image_size             = '1' === $attributes['aspectRatio'] ? 'woocommerce_thumbnail' : 'woocommerce_single';
		$product_gallery_images = ProductGalleryUtils::get_product_gallery_image_data( $product, $image_size );

		// Don't show the thumbnails block if there is only one image.
		if ( count( $product_gallery_images ) <= 1 ) {
			return '';
		}

		$thumbnail_size   = str_replace( '%', '', $attributes['thumbnailSize'] ?? '25%' );
		$thumbnails_class = 'wc-block-product-gallery-thumbnails--thumbnails-size-' . $thumbnail_size;

		$img_class = 'wc-block-product-gallery-thumbnails__thumbnail__image';

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
				data-wp-init="callbacks.initResizeObserver"
				data-wp-on--scroll="actions.onScroll"
				role="listbox">
				<?php foreach ( $product_gallery_images as $index => $image ) : ?>
					<div class="wc-block-product-gallery-thumbnails__thumbnail">
						<img
							class="<?php echo 0 === $index ? esc_attr( $img_class . ' wc-block-product-gallery-thumbnails__thumbnail__image--is-active' ) : esc_attr( $img_class ); ?>"
							data-image-id="<?php echo esc_attr( $image['id'] ); ?>"
							src="<?php echo esc_attr( $image['src'] ); ?>"
							srcset="<?php echo esc_attr( $image['srcset'] ); ?>"
							sizes="<?php echo esc_attr( $image['sizes'] ); ?>"
							alt="<?php echo esc_attr( $image['alt'] ); ?>"
							data-wp-on--click="actions.selectCurrentImage"
							data-wp-on--keydown="actions.onThumbnailsArrowsKeyDown"
							data-wp-watch="callbacks.toggleActiveThumbnailAttributes"
							decoding="async"
							tabindex="<?php echo 0 === $index ? '0' : '-1'; ?>"
							draggable="false"
							loading="lazy"
							role="option"
							style="aspect-ratio: <?php echo esc_attr( $attributes['aspectRatio'] ); ?>" />
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		$template = ob_get_clean();

		return $template;
	}
}
