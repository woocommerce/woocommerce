<?php
/**
 * Admin View: Notice - Wootenberg Feature Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated woocommerce-message woocommerce-wootenberg-promo-messages">
	<a class="woocommerce-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'wootenberg' ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'woocommerce' ); ?></a>

	<p>
		<?php echo wp_kses_post( __( "We noticed you're experimenting with Gutenberg: Try the WooCommerce Gutenberg Products Block for a powerful new way to feature products in posts.", 'woocommerce' ) ); ?>
	</p>
	<?php if ( file_exists( WP_PLUGIN_DIR . '/woo-gutenberg-products-block/woocommerce-gutenberg-products-block.php' ) && ! is_plugin_active( 'woo-gutenberg-products-block/woocommerce-gutenberg-products-block.php' ) && current_user_can( 'activate_plugin', 'woo-gutenberg-products-block/woocommerce-gutenberg-products-block.php' ) ) : ?>
		<p>
			<a href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=woo-gutenberg-products-block/woocommerce-gutenberg-products-block.php&plugin_status=active' ), 'activate-plugin_woo-gutenberg-products-block/woocommerce-gutenberg-products-block.php' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Activate the Gutenberg Products Block', 'woocommerce' ); ?></a>
		</p>
	<?php else : ?>
		<?php
		if ( current_user_can( 'install_plugins' ) ) {
			$url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woo-gutenberg-products-block' ), 'install-plugin_woo-gutenberg-products-block' );
		} else {
			$url = 'https://wordpress.org/plugins/woo-gutenberg-products-block/';
		}
		?>
		<p>
			<a href="<?php echo esc_url( $url ); ?>" class="button button-primary"><?php esc_html_e( 'Install the WooCommerce Gutenberg Products Block', 'woocommerce' ); ?></a>
		</p>
	<?php endif; ?>
</div>
