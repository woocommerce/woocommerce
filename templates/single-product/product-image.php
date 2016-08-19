<?php
/**
 * Single Product Image
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-image.php.
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

global $post, $product;
$columns = apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
$post_thumbnail_id = get_post_thumbnail_id( $post->ID );

$full_size_image  = wp_get_attachment_image_src( $post_thumbnail_id, 'full' );
$full_size_width  = $full_size_image[1];
$full_size_height = $full_size_image[2];

?>
<div class="woocommerce-product-gallery <?php echo 'woocommerce-product-gallery--columns-' . sanitize_html_class( $columns ) . ' columns-' . sanitize_html_class( $columns ); ?> images">
	<figure class="woocommerce-product-gallery__wrapper">
		<?php
		if ( has_post_thumbnail() ) {
			echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', '<figure data-thumb="' . get_the_post_thumbnail_url( $post->ID, 'shop_thumbnail' ) . '" class="woocommerce-product-gallery__image"><a href="' . esc_url( $full_size_image[0] ) . '" data-size="' . esc_attr( $full_size_width ) . 'x' . esc_attr( $full_size_height ) . '" class="woocommerce-product-gallery__image-link">' . get_the_post_thumbnail( $post->ID, 'shop_single' ) . '</a></figure>' );
		} else {
			echo sprintf( '<figure><img src="%s" alt="%s" /></figure>', esc_url( wc_placeholder_img_src() ), esc_html__( 'Awaiting product image', 'woocommerce' ) );
		}

		do_action( 'woocommerce_product_thumbnails' );
		?>
	</figure>
</div>
