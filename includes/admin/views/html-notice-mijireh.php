<?php
/**
 * Admin View: Notice - Mijireh
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$plugin_slug = 'woocommerce-mijireh-checkout';

if ( current_user_can( 'install_plugins' ) ) {
	$url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $plugin_slug ), 'install-plugin_' . $plugin_slug );
} else {
	$url = 'http://wordpress.org/plugins/' . $plugin_slug;
}
?>

<div id="message" class="updated woocommerce-message wc-connect">
	<p><?php _e( '<strong>The Mijireh payment gateway is deprecated</strong> &#8211; It is recommended that you install the replacement Mijireh plugin from WordPress.org as soon as possible. Mijireh will be removed from WC core completely as part of a future update.', 'woocommerce' ); ?></p>

	<p class="submit"><a href="<?php echo esc_url( $url ); ?>" class="wc-update-now button-primary"><?php _e( 'Install the new Mijireh plugin', 'woocommerce' ); ?></a></p>
</div>
