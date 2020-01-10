<?php
/**
 * Admin View: Notice - Missing license key
 *
 * @package WooCommerce\Admin
 */

defined( 'ABSPATH' ) || exit;

?>

<div id="message" class="updated woocommerce-message">
	<a class="woocommerce-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'maxmind_license_key' ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'woocommerce' ); ?></a>

	<p>
		<strong><?php esc_html_e( 'Geolocation has not been configured.', 'woocommerce' ); ?></strong>
	</p>

	<p>
		<?php
		echo wp_kses_post(
			sprintf(
				/* translators: %s: integration page */
				__( 'You must enter a valid license key on the <a href="%s">MaxMind integration settings page</a> in order to use the geolocation service.', 'woocommerce' ),
				admin_url( 'admin.php?page=wc-settings&tab=integration&section=maxmind_geolocation' )
			)
		);
		?>
	</p>
</div>
