<?php
/**
 * Admin View: Notice - Tracking
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated woocommerce-message woocommerce-tracker">
	<p><?php printf( __( 'Want to help make WooCommerce even more awesome? Allow WooCommerce to collect non-sensitive diagnostic data and usage information, and get %1$s discount on your next WooThemes purchase. <a href="%2$s" target="_blank">Find out more</a>.', 'woocommerce' ), '20%', 'https://woocommerce.com/usage-tracking/' ); ?></p>
	<p class="submit">
		<a class="button-primary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc_tracker_optin', 'true' ), 'wc_tracker_optin', 'wc_tracker_nonce' ) ); ?>"><?php _e( 'Allow', 'woocommerce' ); ?></a>
		<a class="skip button-secondary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'tracking' ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>"><?php _e( 'No, do not bother me again', 'woocommerce' ); ?></a>
	</p>
</div>
