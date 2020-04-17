<?php
/**
 * Admin View: Notice - Uploads directory is public.
 *
 * @package WooCommerce\Admin\Notices
 * @since   4.2.0
 */

defined( 'ABSPATH' ) || exit;

$uploads = wp_get_upload_dir();

if ( $uploads['error'] ) {
	return;
}

?>
<div id="message" class="updated woocommerce-message">
	<a class="woocommerce-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'uploads_directory_is_public' ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'woocommerce' ); ?></a>

	<p>
	<?php
		echo wp_kses_post(
			sprintf(
				/* translators: 1: uploads directory path 2: documentation URL */
				__( 'Your store\'s uploads directory (<code>%1$s</code>) is public. We highly recommend serving your entire website over an HTTPS connection to help keep customer data secure. <a href="%2$s">Learn more here.</a>', 'woocommerce' ),
				$uploads['basedir'],
				'https://docs.woocommerce.com/document/ssl-and-https/'
			)
		);
		?>
	</p>
</div>
