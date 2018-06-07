<?php
/**
 * Admin View: Notice - Updating
 *
 * @package WooCommerce\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated woocommerce-message wc-connect">
	<p>
		<strong><?php esc_html_e( 'WooCommerce data update', 'woocommerce' ); ?></strong> &#8211; <?php esc_html_e( 'Your database is being updated in the background.', 'woocommerce' ); ?>
		<a href="<?php echo esc_url( add_query_arg( 'force_update_woocommerce', 'true', admin_url( 'admin.php?page=wc-settings' ) ) ); ?>">
			<?php esc_html_e( 'Taking a while? Click here to run it now.', 'woocommerce' ); ?>
		</a>
	</p>
</div>
