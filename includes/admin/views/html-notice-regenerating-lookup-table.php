<?php
/**
 * Admin View: Notice - Regenerating product lookup table.
 *
 * @package WooCommerce/admin
 */

defined( 'ABSPATH' ) || exit;

$pending_actions_url = admin_url( 'admin.php?page=wc-status&tab=action-scheduler&s=wc_update_product_lookup_tables&status=pending' );
$cron_disabled       = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
?>
<div id="message" class="updated woocommerce-message">
	<p>
		<strong><?php esc_html_e( 'WooCommerce is updating product data in the background.', 'woocommerce' ); ?></strong><br/>
		<?php
		esc_html_e( 'Product display, sorting, and reports may not be accurate until this finishes. It will take a few minutes and this notice will disappear when complete.', 'woocommerce' );

		if ( $cron_disabled ) {
			echo '<br><span style="color:red;">' . esc_html__( 'Warning: WP CRON has been disabled on your install which may prevent this update from completing.', 'woocommerce' ) . '</span>';
		}
		?>
		&nbsp;<a href="<?php echo esc_url( $pending_actions_url ); ?>">
			<?php
			if ( $cron_disabled ) {
				esc_html_e( 'You can manually run queued updates here.', 'woocommerce' );
			} else {
				esc_html_e( 'View progress &rarr;', 'woocommerce' );
			}
			?>
		</a>
	</p>
</div>
