<?php
/**
 * External Add to Cart
 */
 
global $woocommerce, $product_url;
?>

<?php do_action('woocommerce_before_add_to_cart_button'); ?>

<p class="cart"><a href="<?php echo $product_url; ?>" rel="nofollow" class="button alt"><?php _e('Buy product', 'woothemes'); ?></a></p>

<?php do_action('woocommerce_after_add_to_cart_button'); ?>