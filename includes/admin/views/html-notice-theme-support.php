<?php
/**
 * Admin View: Notice - Theme Support
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated woocommerce-message wc-connect">
	<p><?php printf( __( '<strong>Your theme does not declare WooCommerce support</strong> &#8211; Please read our %sintegration%s guide or check out our %sStorefront%s theme which is totally free to download and designed specifically for use with WooCommerce.', 'woocommerce' ), '<a href="' . esc_url( apply_filters( 'woocommerce_docs_url', 'http://docs.woothemes.com/document/third-party-custom-theme-compatibility/', 'theme-compatibility' ) ) . '">', '</a>', '<a href="' . esc_url( admin_url( 'theme-install.php?theme=storefront' ) ) . '">', '</a>' ); ?></p>
	<p class="submit">
		<a href="http://www.woothemes.com/storefront/?utm_source=wpadmin&amp;utm_medium=notice&amp;utm_campaign=Storefront" class="button-primary" target="_blank"><?php _e( 'Read More About Storefront', 'woocommerce' ); ?></a>
		<a href="<?php echo esc_url( apply_filters( 'woocommerce_docs_url', 'http://docs.woothemes.com/document/third-party-custom-theme-compatibility/', 'theme-compatibility' ) ); ?>" class="button-secondary" target="_blank"><?php _e( 'Theme Integration Guide', 'woocommerce' ); ?></a>
		<a class="button-secondary skip" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'theme_support' ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>"><?php _e( 'Hide This Notice', 'woocommerce' ); ?></a>
	</p>
</div>
