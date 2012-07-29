<?php
/**
 * External Add to Cart
 */
?>
<?php do_action('woocommerce_before_add_to_cart_button'); ?>

<p class="cart"><a href="<?php echo $product_url; ?>" rel="nofollow" class="button alt" onClick="<?php echo apply_filters( 'woocommerce_onclick_javascript', '', 'add_to_cart', $product->id ); ?>"><?php echo apply_filters('single_add_to_cart_text', $button_text, 'external'); ?></a></p>

<?php do_action('woocommerce_after_add_to_cart_button'); ?>