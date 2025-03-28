<?php
/**
 * Admin View: Notice - WooCommerce Email sender options.
 *
 * @package WooCommerce\Admin\Notices
 */

use Automattic\WooCommerce\Internal\EmailEditor\Integration;

defined( 'ABSPATH' ) || exit;

?>
<div id="message" class="updated woocommerce-message">
	<a class="woocommerce-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'woocommerce_email_sender_options' ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'woocommerce' ); ?></a>

	<p>
	<?php
		echo wp_kses_post(
			sprintf(
			/* translators: %s: documentation URL */
				__( 'Email sender options have been moved. You can access these settings via <a href="%s">Email Template</a>.', 'woocommerce' ),
				admin_url( 'edit.php?post_type=' . Integration::EMAIL_POST_TYPE )
			)
		);
		?>
	</p>
</div>
