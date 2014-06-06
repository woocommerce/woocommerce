<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div id="message" class="updated woocommerce-message wc-connect">
	<p><?php _e( '<strong>WooCommerce Translation Available</strong> &#8211; do the upgrade / installation of your translations.', 'woocommerce' ); ?></p>
	<form method="post" action="<?php echo add_query_arg( array( 'action' => 'do-translation-upgrade' ), admin_url( 'update-core.php' ) ); ?>" class="upgrade">
		<?php wp_nonce_field( 'upgrade-translations' ); ?>
		<p class="submit"><input class="button-primary" type="submit" value="<?php esc_attr_e( 'Update Translations', 'woocommerce' ); ?>" name="upgrade" /></p>
	</form>
</div>
