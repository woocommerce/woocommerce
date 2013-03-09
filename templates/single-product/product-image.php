<?php
/**
 * Single Product Image
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $post, $woocommerce;

?>
<div class="images">

	<?php if ( has_post_thumbnail() ) : ?>

		<?php
		echo apply_filters( 'woocommerce_product_image_with_link', '<a class="woocommerce-main-image zoom" itemprop="image" href="' . wp_get_attachment_url( get_post_thumbnail_id() ) . '" rel="prettyPhoto[product-gallery]" title="' . get_the_title( get_post_thumbnail_id() ) . '">' . get_the_post_thumbnail( $post->ID, apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ) ) . '</a>', $post->ID );
		?>

	<?php else : ?>
		<?php
		echo apply_filters( 'woocommerce_product_image_placeholder', '<img src="' . woocommerce_placeholder_img_src() . '" alt="Placeholder" />', $post->ID );
		?>

	<?php endif; ?>

	<?php do_action('woocommerce_product_thumbnails'); ?>

</div>