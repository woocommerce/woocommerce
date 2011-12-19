<?php
/**
 * Empty Cart Page
 */
?>

<p><?php _e('Your cart is currently empty.', 'woothemes') ?></p>

<?php do_action('woocommerce_cart_is_empty'); ?>

<p><a class="button" href="<?php echo get_permalink(get_option('woocommerce_shop_page_id')); ?>"><?php _e('&larr; Return To Shop', 'woothemes') ?></a></p>