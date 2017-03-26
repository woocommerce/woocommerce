<?php
/**
 * Admin View: Notice - Tracking
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated woocommerce-message woocommerce-tracker">
	<p><?php printf( __( 'Want to help make WooCommerce even more awesome? Allow WooThemes to collect non-sensitive diagnostic data and usage information, and get %s discount on your next WooThemes purchase. %sFind out more%s.', 'woocommerce' ), '20%', '<a href="https://woocommerce.com/usage-tracking/" target="_blank">', '</a>' ); ?></p>
	<p class="submit">
		<a class="button-primary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc_tracker_optin', 'true' ), 'wc_tracker_optin', 'wc_tracker_nonce' ) ); ?>"><?php _e( 'Allow', 'woocommerce' ); ?></a>
		<a class="skip button-secondary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'tracking' ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>"><?php _e( 'No, do not bother me again', 'woocommerce' ); ?></a>
	</p>
</div>
