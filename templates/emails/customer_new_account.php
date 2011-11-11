<?php if (!defined('ABSPATH')) exit; ?>

<?php global $user_login, $user_pass, $blogname; ?>

<?php do_action('woocommerce_email_header'); ?>

<p><?php echo sprintf(__("Thanks for registering on %s. Your login details are below:", 'woothemes'), $blogname); ?></p>

<ul>
	<li><?php echo sprintf(__('Username: %s'), $user_login); ?></li>
	<li><?php echo sprintf(__('Password: %s'), $user_pass); ?></li>
</ul>

<p><?php echo sprintf(__("You can login to your account area here: %s.", 'woothemes'), get_permalink(get_option('woocommerce_myaccount_page_id'))); ?></p>

<div style="clear:both;"></div>

<?php do_action('woocommerce_email_footer'); ?>