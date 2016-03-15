<?php
/**
 * Admin View: Notice - Legacy Shipping.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="message" class="updated woocommerce-message woocommerce-legacy-shipping-notice">
	<a class="woocommerce-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'legacy_shipping' ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>"><?php _e( 'Dismiss', 'woocommerce' ); ?></a>

	<p class="main"><strong><?php _e( 'New Feature: Shipping Zones!', 'woocommerce' ); ?></strong></p>
	<p><?php _e( 'Shipping Zones let you group regions with similar Shipping Methods and rates.', 'woocommerce' ); ?></p>
	<p><?php _e( 'Legacy shipping methods (Flat Rate, International, Local Pickup and Delivery and Free Shipping) are deprecated and <em>will be removed in future versions of WooCommerce</em>, but will continue to function as normal for now. We recommend disabling these shipping methods and instead setting up new rates within Shipping Zones as soon as possible.', 'woocommerce' ); ?></p>

	<p class="submit">
		<?php if ( empty( $_GET['page'] ) || empty( $_GET['tab'] ) || 'wc-settings' !== $_GET['page'] || 'shipping' !== $_GET['tab'] ) : ?>
			<a class="button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ); ?>"><?php _e( 'Setup Shipping Zones', 'woocommerce' ); ?></a>
		<?php endif; ?>
		<a class="button-secondary" href="<?php echo esc_url( 'https://docs.woothemes.com/document/setting-up-shipping-zones/' ); ?>"><?php _e( 'Lean more about Shipping Zones', 'woocommerce' ); ?></a>
	</p>
</div>
