<?php
/**
 * Admin View: Notice - Translation Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<div id="message" class="updated woocommerce-message wc-connect">
	<p><?php printf( __( '<strong>WooCommerce Translation Available</strong> &#8211; Install or update your <code>%s</code> translation to version <code>%s</code>.', 'woocommerce' ), WC_Language_Pack_Upgrader::get_language(), WC_VERSION ); ?></p>
	<p><a href="<?php echo wp_nonce_url( add_query_arg( array( 'action' => 'do-translation-upgrade' ), admin_url( 'update-core.php' ) ), 'upgrade-translations' ); ?>" class="button-primary"><?php printf( __( 'Update translation', 'woocommerce' ), WC_Language_Pack_Upgrader::get_language() ); ?></a></p>
</div>
