<?php
/**
 * Admin View: Notice - Updating
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated woocommerce-message wc-connect">
	<p><strong><?php _e( 'WooCommerce Data Update', 'woocommerce' ); ?></strong> &#8211; <?php _e( 'Your database is being updated in the background.', 'woocommerce' ); ?> <a href="<?php echo esc_url( add_query_arg( 'force_update_woocommerce', 'true', admin_url( 'admin.php?page=wc-settings' ) ) ); ?>"><?php _e( 'Taking a while? Click here to run it now.', 'woocommerce' ); ?></a></p>
</div>
