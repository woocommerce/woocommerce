<?php
/**
 * Checkout login form
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( is_user_logged_in() ) return;
if ( get_option('woocommerce_enable_signup_and_login_from_checkout') == "no" ) return;

$info_message = apply_filters('woocommerce_checkout_login_message', __('Already registered?', 'woocommerce'));
?>

<p class="woocommerce_info"><?php echo $info_message; ?> <a href="#" class="showlogin"><?php _e('Click here to login', 'woocommerce'); ?></a></p>

<?php woocommerce_login_form( array( 'message' => __('If you have shopped with us before, please enter your details in the boxes below. If you are a new customer please proceed to the Billing &amp; Shipping section.', 'woocommerce'), 'redirect' => get_permalink(woocommerce_get_page_id('checkout')) ) ); ?>