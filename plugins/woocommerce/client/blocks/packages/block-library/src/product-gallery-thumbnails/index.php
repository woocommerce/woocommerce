<?php
/**
 * Server-side rendering of the `woocommerce/product-gallery-thumbnails` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

$post_id = $block->context['postId'] ?? 0;

if ( ! $post_id ) {
	return;
}

$product = wc_get_product( $post_id );

if ( ! $product instanceof \WC_Product ) {
	return;
}

$aspect_ratio           = $attributes['aspectRatio'] ?? '1';
$thumbnail_size         = str_replace( '%', '', $attributes['thumbnailSize'] ?? '25%' );
$active_thumbnail_style = $attributes['activeThumbnailStyle'] ?? 'overlay';
$classes_and_styles     = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );
$image_size             = '1' === $aspect_ratio ? 'woocommerce_thumbnail' : 'woocommerce_single';
$product_gallery_images = ProductGalleryUtils::get_product_gallery_image_data( $product, $image_size );

if ( count( $product_gallery_images ) <= 1 ) {
	return;
}

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
					data-wp-watch="callbacks.syncThumbnailState"
					decoding="async"
					tabindex="<?php echo 0 === $index ? '0' : '-1'; ?>"
					draggable="false"
					loading="lazy"
					role="option"
					style="aspect-ratio: <?php echo esc_attr( $aspect_ratio ); ?>" />
			</div>
		<?php endforeach; ?>
	</div>
</div>
<?php

echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
