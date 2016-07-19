<?php
/**
 * Admin View: Notice - Theme Support
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated woocommerce-message wc-connect">
	<a class="woocommerce-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'theme_support' ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>"><?php _e( 'Dismiss', 'woocommerce' ); ?></a>

	<p><?php printf( __( '<strong>Your theme does not declare WooCommerce support</strong> &#8211; Please read our %sintegration%s guide or check out our %sStorefront%s theme which is totally free to download and designed specifically for use with WooCommerce.', 'woocommerce' ), '<a target="_blank" href="' . esc_url( apply_filters( 'woocommerce_docs_url', 'https://docs.woocommerce.com/document/third-party-custom-theme-compatibility/', 'theme-compatibility' ) ) . '">', '</a>', '<a target="_blank" href="' . esc_url( admin_url( 'theme-install.php?theme=storefront' ) ) . '">', '</a>' ); ?></p>
	<p class="submit">
		<a href="https://woocommerce.com/storefront/?utm_source=notice&amp;utm_medium=product&amp;utm_content=storefront&amp;utm_campaign=woocommerceplugin" class="button-primary" target="_blank"><?php _e( 'Read More About Storefront', 'woocommerce' ); ?></a>
		<a href="<?php echo esc_url( apply_filters( 'woocommerce_docs_url', 'http://docs.woocommerce.com/document/third-party-custom-theme-compatibility/?utm_source=notice&utm_medium=product&utm_content=themecompatibility&utm_campaign=woocommerceplugin' ) ); ?>" class="button-secondary" target="_blank"><?php _e( 'Theme Integration Guide', 'woocommerce' ); ?></a>
	</p>
</div>
