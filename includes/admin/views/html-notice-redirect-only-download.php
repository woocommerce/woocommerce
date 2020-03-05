<?php
/**
 * Admin View: Notice - Redirect only download method is selected.
 *
 * @package WooCommerce\Admin\Notices
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="updated woocommerce-message">
	<a class="woocommerce-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'redirect_download_method' ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'woocommerce' ); ?></a>
	<p>
		<?php
		echo wp_kses_post(
			sprintf(
				/* translators: %s: Link to settings page. */
				__( 'Your store is configured to serve digital products using "Redirect only" method. This method is deprecated, <a href="%s">please switch to  a different method instead.</a>', 'woocommerce' ),
				'/wp-admin/admin.php?page=wc-settings&tab=products&section=downloadable'
			)
		);
		?>
	</p>
</div>
