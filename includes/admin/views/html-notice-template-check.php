<?php
/**
 * Admin View: Notice - Template Check
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$theme = wp_get_theme();
?>
<div id="message" class="updated woocommerce-message">
	<a class="woocommerce-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'template_files' ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>"><?php _e( 'Dismiss', 'woocommerce' ); ?></a>

	<p><?php printf( __( '<strong>Your theme (%1$s) contains outdated copies of some WooCommerce template files.</strong> These files may need updating to ensure they are compatible with the current version of WooCommerce. You can see which files are affected from the <a href="%2$s">system status page</a>. If in doubt, check with the author of the theme.', 'woocommerce' ), esc_html( $theme['Name'] ), esc_url( admin_url( 'admin.php?page=wc-status' ) ) ); ?></p>
	<p class="submit"><a class="button-primary" href="https://docs.woocommerce.com/document/template-structure/" target="_blank"><?php _e( 'Learn more about templates', 'woocommerce' ); ?></a></p>
</div>
