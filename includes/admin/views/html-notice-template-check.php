<?php
/**
 * Admin View: Notice - Template Check
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<div id="message" class="updated woocommerce-message wc-connect">
	<p><?php _e( '<strong>Your theme has bundled outdated copies of WooCommerce template files.</strong> If you notice an issue on your site, this could be the reason. Please contact your theme developer for further assistance. You can review the System Status report for full details or <a href="http://docs.woothemes.com/document/template-structure/" target="_blank">learn more about WooCommerce Template Structure here</a>.', 'woocommerce' ); ?></p>
	<p class="submit"><a class="button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-status' ) ); ?>"><?php _e( 'System Status', 'woocommerce' ); ?></a> <a class="skip button-primary" href="<?php echo esc_url( add_query_arg( 'wc-hide-notice', 'template_files' ) ); ?>"><?php _e( 'Hide this notice', 'woocommerce' ); ?></a></p>
</div>
