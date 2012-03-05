<?php
/**
 * Single Product Image
 */

global $post, $woocommerce;
?>
<div class="images">

	<?php if (has_post_thumbnail()) : $thumb_id = get_post_thumbnail_id(); $large_thumbnail_size = apply_filters('single_product_large_thumbnail_size', 'shop_single'); ?>

		<a itemprop="image" href="<?php echo wp_get_attachment_url($thumb_id); ?>" class="zoom" rel="thumbnails" title="<?php echo get_the_title( $thumb_id ); ?>"><?php echo get_the_post_thumbnail($post->ID, $large_thumbnail_size) ?></a>

	<?php else : ?>
	
		<img src="<?php echo woocommerce_placeholder_img_src(); ?>" alt="Placeholder" />
	
	<?php endif; ?>

	<?php do_action('woocommerce_product_thumbnails'); ?>

</div>