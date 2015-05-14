<?php
/**
 * Single Product Image
 *
 * @author      WooThemes
 * @package     WooCommerce/Templates
 * @version     2.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $post, $woocommerce, $product;

?>
<div class="images">

	<?php
		if ( has_post_thumbnail() ) {

			$thumbnail_id   = get_post_thumbnail_id();
			$image_title    = esc_attr( get_the_title( $thumbnail_id ) );
			$image_alt      = esc_attr( get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ) );
			$image_alt      = ! empty( $image_alt ) ? $image_alt : $image_title;
			$image_caption  = get_post( $thumbnail_id )->post_excerpt;
			$image_link     = wp_get_attachment_url( $thumbnail_id );
			$image          = get_the_post_thumbnail( $post->ID, apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ), array(
				'title' => $image_title,
				'alt'   => $image_alt
			) );

			$attachment_count = count( $product->get_gallery_attachment_ids() );

			if ( $attachment_count > 0 ) {
				$gallery = '[product-gallery]';
			} else {
				$gallery = '';
			}

			echo apply_filters( 'woocommerce_single_product_image_html', sprintf( '<a href="%s" itemprop="image" class="woocommerce-main-image zoom" title="%s" data-rel="prettyPhoto' . $gallery . '">%s</a>', $image_link, $image_caption, $image ), $post->ID );

		} else {

			echo apply_filters( 'woocommerce_single_product_image_html', sprintf( '<img src="%s" alt="%s" />', wc_placeholder_img_src(), __( 'Placeholder', 'woocommerce' ) ), $post->ID );

		}
	?>

	<?php do_action( 'woocommerce_product_thumbnails' ); ?>

</div>
