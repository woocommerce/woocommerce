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
		<strong><?php esc_html_e( 'WooCommerce database update', 'woocommerce' ); ?></strong> &#8211; <?php esc_html_e( 'WooCommerce is updating the database in the background. The database update process may take a little while, so please be patient.', 'woocommerce' ); ?>
	</p>
</div>
