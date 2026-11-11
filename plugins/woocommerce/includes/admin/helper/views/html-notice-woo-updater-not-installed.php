<?php
/**
 * Helper Admin Notice - Woo Updater Plugin is not Installed.
 *
 * @package WooCommerce\Views
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="message" class="error woocommerce-message">
	<a class="woocommerce-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'woo_updater_not_installed' ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'woocommerce' ); ?></a>
	<p>
	<?php
	if ( WC_Woo_Update_Manager_Plugin::needs_manual_install() ) {
		/* translators: 1: Woo Update Manager plugin download URL 2: Upload plugin screen URL */
		$message = __( 'Please <a href="%1$s">download the WooCommerce.com Update Manager</a> and <a href="%2$s">upload it on the Add Plugins screen</a> to continue receiving the updates and streamlined support included in your WooCommerce.com subscriptions. WooCommerce.com can’t reach local sites, so it can’t install it for you.', 'woocommerce' );
		$links   = array( WC_Woo_Update_Manager_Plugin::WOO_UPDATE_MANAGER_DOWNLOAD_URL, network_admin_url( 'plugin-install.php?tab=upload' ) );
	} else {
		/* translators: 1: Woo Update Manager plugin install URL 2: Woo Update Manager plugin download URL */
		$message = __( 'Please <a href="%1$s">Install the WooCommerce.com Update Manager</a> to continue receiving the updates and streamlined support included in your WooCommerce.com subscriptions. Alternatively, you can <a href="%2$s">download</a> and install it manually.', 'woocommerce' );
		$links   = array( WC_Woo_Update_Manager_Plugin::generate_install_url(), WC_Woo_Update_Manager_Plugin::WOO_UPDATE_MANAGER_DOWNLOAD_URL );
	}

	echo wp_kses_post( sprintf( $message, esc_url( $links[0] ), esc_url( $links[1] ) ) );
	?>
	</p>
</div>
