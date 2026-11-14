<?php
/**
 * Single Product Thumbnails
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-thumbnails.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     11.1.0
 */

use Automattic\WooCommerce\Internal\ProductGallery\ProductMediaGallery;

defined( 'ABSPATH' ) || exit;

// Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior. This check protects against theme overrides being used on older versions of WC.
if ( ! function_exists( 'wc_get_gallery_image_html' ) ) {
	return;
}

global $product;

if ( ! $product || ! $product instanceof WC_Product ) {
	return '';
}

$media_items = ProductMediaGallery::get_product_media_gallery_items_for_display( $product );

if ( count( $media_items ) > 1 ) {
	foreach ( array_slice( $media_items, 1 ) as $key => $media_item ) {
		$attachment_id = isset( $media_item['id'] ) ? absint( $media_item['id'] ) : 0;

		if ( ! $attachment_id ) {
			continue;
		}

		$is_video = 'video' === ( $media_item['media_type'] ?? '' );

		if ( $is_video ) {
			$html = ProductMediaGallery::get_gallery_video_html( $media_item, false );
		} else {
			$html = wc_get_gallery_image_html( $attachment_id, false, $key );
		}

		if ( $is_video ) {
			/**
			 * Filter product video thumbnail HTML string.
			 *
			 * @since 11.0.0
			 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
			 *
			 * @param string $html          Product video thumbnail HTML string.
			 * @param int    $attachment_id Video attachment ID.
			 * @param array  $media_item    Product media gallery item.
			 */
			echo apply_filters( 'woocommerce_single_product_video_thumbnail_html', $html, $attachment_id, $media_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			/**
			 * Filter product image thumbnail HTML string.
			 *
			 * @since 1.6.4
			 *
			 * @param string $html          Product image thumbnail HTML string.
			 * @param int    $attachment_id Attachment ID.
			 */
			echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', $html, $attachment_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
