<?php if (!defined('ABSPATH')) exit; ?>

<?php do_action('woocommerce_email_header', $email_heading); ?>

<p><?php echo sprintf(__("Thanks for registering on %s. Your login details are below:", 'woocommerce'), $blogname); ?></p>

<ul>
	<li><?php echo sprintf(__('Username: %s', 'woocommerce'), $user_login); ?></li>
	<li><?php echo sprintf(__('Password: %s', 'woocommerce'), $user_pass); ?></li>
</ul>

<p><?php echo sprintf(__("You can login to your account area here: %s.", 'woocommerce'), get_permalink(woocommerce_get_page_id('myaccount'))); ?></p>

<div style="clear:both;"></div>

<?php do_action('woocommerce_email_footer'); ?>