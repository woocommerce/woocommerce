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
		<?php
		printf(
			/* translators: %s: Gutenberg Products Block plugin URL */
			wp_kses_post( __( 'Make sure you try the <a href="%s" target="_blank" rel="nofollow">WooCommerce Gutenberg Products Block</a> for a powerful new way to feature products in your Gutenberg posts!', 'woocommerce' ) ),
			'https://wordpress.org/plugins/woo-gutenberg-products-block/'
		);
		?>
	</p>
</div>
