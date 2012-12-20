<?php
/**
 * Single Product Thumbnails
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $post, $product, $woocommerce;
?>
<div class="thumbnails"><?php

	$attachment_ids = $product->get_images();

	if ( $attachment_ids ) {

		$loop = 0;
		$columns = apply_filters( 'woocommerce_product_thumbnails_columns', 3 );

		foreach ( $attachment_ids as $id ) {

			$attachment = get_post( $id );

			$classes = array( 'zoom' );

			if ( $loop == 0 || $loop % $columns == 0 )
				$classes[] = 'first';

			if ( ( $loop + 1 ) % $columns == 0 )
				$classes[] = 'last';

			printf( '<a href="%s" title="%s" rel="thumbnails" class="%s">%s</a>', wp_get_attachment_url( $attachment->ID ), esc_attr( $attachment->post_title ), implode(' ', $classes), wp_get_attachment_image( $attachment->ID, apply_filters( 'single_product_small_thumbnail_size', 'shop_thumbnail' ) ) );

			$loop++;
		}

	}
?></div>