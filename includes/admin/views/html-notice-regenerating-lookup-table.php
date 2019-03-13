<?php
/**
 * Admin View: Notice - Regenerating thumbnails.
 *
 * @package WooCommerce/admin
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="message" class="updated woocommerce-message">
	<a class="woocommerce-message-close notice-dismiss" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-status&tab=action-scheduler&s=wc_update_product_lookup_tables&status=pending' ) ); ?>"><?php esc_html_e( 'View progress', 'woocommerce' ); ?></a>

	<p>
		<string><?php esc_html_e( 'WooCommerce is updating product data in the background. ', 'woocommerce' ); ?></strong>
		<?php
		echo wp_kses_post(
			sprintf(
				/* Translators: 1: Link, 2: link close. */
				__( 'Some queries and reports may not show accurate results until this finishes. It will take a few minutes and this notice will disappear when complete.', 'woocommerce' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=action-scheduler&s=wc_update_product_lookup_tables' ) ) . '">',
				'</a>'
			)
		);
		?>
	</p>
</div>
