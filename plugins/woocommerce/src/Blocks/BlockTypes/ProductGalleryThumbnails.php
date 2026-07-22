<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;

/**
 * ProductGalleryThumbnails class.
 */
class ProductGalleryThumbnails extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-gallery-thumbnails';

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
		$image_size            = '1' === $attributes['aspectRatio'] ? 'woocommerce_thumbnail' : 'woocommerce_single';
		$product_gallery_media = ProductGalleryUtils::get_product_gallery_media_data( $product, $image_size );

		// Don't show the thumbnails block if there is only one media item.
		if ( count( $product_gallery_media ) <= 1 ) {
			return '';
		}

		$thumbnail_size         = str_replace( '%', '', $attributes['thumbnailSize'] ?? '25%' );
		$active_thumbnail_style = $attributes['activeThumbnailStyle'] ?? 'overlay';

		$img_class = 'wc-block-product-gallery-thumbnails__thumbnail__image';

		ob_start();
		?>
		<div
			class="wc-block-product-gallery-thumbnails wc-block-product-gallery-thumbnails--active-<?php echo esc_attr( $active_thumbnail_style ); ?> <?php echo esc_attr( $classes_and_styles['classes'] ); ?>"
			style="<?php echo '--wc-block-product-gallery-thumbnails-size:' . absint( $thumbnail_size ) . ';' . esc_attr( $classes_and_styles['styles'] ); ?>"
			data-wp-interactive="woocommerce/product-gallery"
			data-wp-bind--hidden="context.hideNextPreviousButtons"
			data-wp-class--wc-block-product-gallery-thumbnails--overflow-top="context.thumbnailsOverflow.top"
			data-wp-class--wc-block-product-gallery-thumbnails--overflow-bottom="context.thumbnailsOverflow.bottom"
			data-wp-class--wc-block-product-gallery-thumbnails--overflow-left="context.thumbnailsOverflow.left"
			data-wp-class--wc-block-product-gallery-thumbnails--overflow-right="context.thumbnailsOverflow.right">
			<div
				class="wc-block-product-gallery-thumbnails__scrollable"
				data-wp-init--init-resize-observer="callbacks.initResizeObserver"
				data-wp-init--hide-ghost-overflow="callbacks.hideGhostOverflow"
				data-wp-on--scroll="actions.onScroll"
				role="listbox">
				<?php foreach ( $product_gallery_media as $index => $media ) : ?>
					<?php
					$media_type             = isset( $media['media_type'] ) && is_string( $media['media_type'] ) ? sanitize_key( $media['media_type'] ) : 'image';
					$thumbnail_classes      = 'wc-block-product-gallery-thumbnails__thumbnail';
					$thumbnail_image_class  = 0 === $index ? $img_class . ' wc-block-product-gallery-thumbnails__thumbnail__image--is-active' : $img_class;
					$thumbnail_aspect_ratio = $attributes['aspectRatio'] ?? '1';
					$thumbnail_aspect_ratio = is_string( $thumbnail_aspect_ratio ) ? $thumbnail_aspect_ratio : '1';

					if ( 'video' === $media_type ) {
						$thumbnail_classes .= ' wc-block-product-gallery-thumbnails__thumbnail--video';
					}

					$thumbnail_attributes = array(
						'class'               => $thumbnail_image_class,
						'data-image-id'       => isset( $media['id'] ) ? absint( $media['id'] ) : 0,
						'data-media-type'     => $media_type,
						'data-wp-on--click'   => 'actions.selectCurrentImage',
						'data-wp-on--keydown' => 'actions.onThumbnailsArrowsKeyDown',
						'data-wp-watch'       => 'callbacks.syncThumbnailState',
						'draggable'           => 'false',
						'role'                => 'option',
						'style'               => 'aspect-ratio: ' . $thumbnail_aspect_ratio,
						'tabindex'            => 0 === $index ? '0' : '-1',
					);
					$show_video_preview   = 'video' === $media_type && empty( $media['poster_id'] ) && ! empty( $media['video_src'] );
					?>
					<div class="<?php echo esc_attr( $thumbnail_classes ); ?>">
						<?php if ( $show_video_preview ) : ?>
							<video
								<?php echo wc_implode_html_attributes( $thumbnail_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								src="<?php echo esc_url( $media['video_src'] ); ?>"
								aria-label="<?php echo esc_attr( $media['alt'] ); ?>"
								preload="metadata"
								muted="muted"
								playsinline="playsinline"></video>
						<?php else : ?>
							<img
								<?php echo wc_implode_html_attributes( $thumbnail_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								src="<?php echo esc_attr( $media['src'] ); ?>"
								srcset="<?php echo esc_attr( $media['srcset'] ); ?>"
								sizes="<?php echo esc_attr( $media['sizes'] ); ?>"
								alt="<?php echo esc_attr( $media['alt'] ); ?>"
								decoding="async"
								loading="lazy" />
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		$template = ob_get_clean();

		return $template;
	}
}
