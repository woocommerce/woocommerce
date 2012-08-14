<?php
/**
 * Customer new account email
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates/Emails
 * @version     1.6.4
 */

if (!defined('ABSPATH')) exit; ?>

<?php do_action('woocommerce_email_header', $email_heading); ?>

<p><?php printf(__("Thanks for creating an account on %s. Your username is <strong>%s</strong>.", 'woocommerce'), $blogname, $user_login); ?></p>

<p><?php printf(__("You can access your account area here: %s.", 'woocommerce'), get_permalink(woocommerce_get_page_id('myaccount'))); ?></p>

<div style="clear:both;"></div>

<?php do_action('woocommerce_email_footer'); ?>