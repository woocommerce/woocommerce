<?php
/**
 * Admin View: Notice - Translation Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated woocommerce-message wc-connect">
	<p><?php printf( __( '<strong>WooCommerce Translation Available</strong> &#8211; Install or update your <code>%s</code> translation to version <code>%s</code>.', 'woocommerce' ), get_locale(), WC_VERSION ); ?></p>

	<p>
		<?php if ( is_multisite() ) : ?>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wc-status&tab=tools&action=translation_upgrade' ), 'debug_action' ) ); ?>" class="button-primary"><?php _e( 'Update Translation', 'woocommerce' ); ?></a>
		<?php else : ?>
			<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'do-translation-upgrade' ), admin_url( 'update-core.php' ) ), 'upgrade-translations' ) ); ?>" class="button-primary"><?php _e( 'Update Translation', 'woocommerce' ); ?></a>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wc-status&tab=tools&action=translation_upgrade' ), 'debug_action' ) ); ?>" class="button-secondary"><?php _e( 'Force Update Translation', 'woocommerce' ); ?></a>
		<?php endif; ?>
		<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'translation_upgrade' ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>" class="button-secondary skip"><?php _e( 'Hide This Message', 'woocommerce' ); ?></a>
	</p>
</div>
