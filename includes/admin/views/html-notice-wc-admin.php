<?php
/**
 * Admin View: Notice - WooCommerce Admin Feature Plugin
 *
 * @package admin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated woocommerce-message woocommerce-admin-promo-messages">
	<a class="woocommerce-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'wc_admin' ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'woocommerce' ); ?></a>

	<p>
		<?php echo wp_kses_post( __( 'Test drive the future of WooCommerce. A quicker, javascript powered interface with exciting new features and reports.', 'woocommerce' ) ); ?>
	</p>
	<?php if ( file_exists( WP_PLUGIN_DIR . '/woocommerce-admin/woocommerce-admin.php' ) && ! is_plugin_active( 'woocommerce-admin/woocommerce-admin.php' ) && current_user_can( 'activate_plugin', 'woocommerce-admin/woocommerce-admin.php' ) ) : ?>
		<p>
			<a href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=woocommerce-admin/woocommerce-admin.php&plugin_status=active' ), 'activate-plugin_woocommerce-admin/woocommerce-admin.php' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Activate WooCommerce Admin', 'woocommerce' ); ?></a>
		</p>
	<?php else : ?>
		<?php
		if ( current_user_can( 'install_plugins' ) ) {
			$url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce-admin' ), 'install-plugin_woocommerce-admin' );
		} else {
			$url = 'https://wordpress.org/plugins/woocommerce-admin/';
		}
		?>
		<p>
			<a href="<?php echo esc_url( $url ); ?>" class="button button-primary"><?php esc_html_e( 'Install WooCommerce Admin', 'woocommerce' ); ?></a>
		</p>
	<?php endif; ?>
</div>
