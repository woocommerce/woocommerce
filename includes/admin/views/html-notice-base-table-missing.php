<?php
/**
 * Admin View: Notice - Base table missing.
 *
 * @package WooCommerce\Admin
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="updated woocommerce-message">
	<a class="woocommerce-message-close notice-dismiss"
	   href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'base_tables_missing' ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>">
			<?php esc_html_e( 'Dismiss', 'woocommerce' ); ?>
	</a>

	<p>
		<strong><?php esc_html_e( 'Database tables missing', 'woocommerce' ); ?></strong>
	</p>
	<p>
		<?php
			echo wp_kses_post(
				sprintf(
				/* translators: %1%s: WooCommerce status page %2$s: Link to check again */
					__( 'One or more tables required for WooCommerce to function are missing, some features may not work as expected. Please visit <a href="%1$s">WooCommerce status page</a> to see details, or <a href="%2$s"> check again.</a>', 'woocommerce' ),
					admin_url( 'admin.php?page=wc-status#status-database' ),
					wp_nonce_url( admin_url( 'admin.php?page=wc-status&tab=tools&action=verify_db_tables' ), 'debug_action' )
				)
			);
			?>
	</p>
</div>
