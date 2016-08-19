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
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post, $product, $woocommerce;
$attachment_ids = $product->get_gallery_attachment_ids();

if ( $attachment_ids ) {
	foreach ( $attachment_ids as $attachment_id ) {
		$full_size_image = wp_get_attachment_image_src( $attachment_id, 'full' );
		$thumbnail       = wp_get_attachment_image_src( $attachment_id, 'shop_thumbnail' );

		$full_size_width  = $full_size_image[1];
		$full_size_height = $full_size_image[2];

		echo '<figure data-thumb="' . esc_url( $thumbnail[0] ) . '" class="woocommerce-product-gallery__image">';
			echo '<a href="' . esc_url( $full_size_image[0] ) . '" data-size="' . esc_attr( $full_size_width ) . 'x' . esc_attr( $full_size_height ) . '" class="woocommerce-product-gallery__image-link">';
				echo wp_get_attachment_image( $attachment_id, 'shop_single' );
			echo '</a>';
		echo '</figure>';
	}
}
