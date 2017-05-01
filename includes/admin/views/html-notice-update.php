<?php
/**
 * Admin View: Notice - Update
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated woocommerce-message wc-connect">
	<p><strong><?php _e( 'WooCommerce data update', 'woocommerce' ); ?></strong> &#8211; <?php _e( 'We need to update your store\'s database to the latest version.', 'woocommerce' ); ?></p>
	<p class="submit"><a href="<?php echo esc_url( add_query_arg( 'do_update_woocommerce', 'true', admin_url( 'admin.php?page=wc-settings' ) ) ); ?>" class="wc-update-now button-primary"><?php _e( 'Run the updater', 'woocommerce' ); ?></a></p>
</div>
<script type="text/javascript">
	jQuery( '.wc-update-now' ).click( 'click', function() {
		return window.confirm( '<?php echo esc_js( __( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'woocommerce' ) ); ?>' ); // jshint ignore:line
	});
</script>
