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
$columns = apply_filters( 'woocommerce_product_thumbnails_columns', 3 );
?>
<figure class="woocommerce-product-gallery <?php echo 'woocommerce-product-gallery--columns-' . sanitize_html_class( $columns ) . ' columns-' . sanitize_html_class( $columns ); ?> images">
	<?php
	if ( has_post_thumbnail() ) {
		echo get_the_post_thumbnail( $post->ID, 'shop_single' );
	} else {
		echo sprintf( '<img src="%s" alt="%s" />', esc_url( wc_placeholder_img_src() ), esc_html__( 'Awaiting product image', 'woocommerce' ) );
	}

	do_action( 'woocommerce_product_thumbnails' );
	?>
</figure>
