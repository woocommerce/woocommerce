<?php
/**
 * Admin View: Notice - Secure connection.
 *
 * @package WooCommerce\Admin\Notices
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="message" class="updated woocommerce-message">
	<a class="woocommerce-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'no_secure_connection' ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'woocommerce' ); ?></a>

	<p>
	<?php
		echo wp_kses_post( sprintf(
			/* translators: %s: documentation URL */
			__( 'Your store is not using HTTPS, this is requeried to sell in some countries and to protect your customer data. <a href="%s">Learn more about HTTPS and SSL Certificates.</a>', 'woocommerce' ),
			'https://docs.woocommerce.com/document/ssl-and-https/'
		) );
	?>
	</p>
</div>
