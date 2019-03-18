<?php
/**
 * Admin View: Notice - Regenerating thumbnails.
 *
 * @package WooCommerce/admin
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="message" class="updated woocommerce-message">
	<p>
		<strong><?php esc_html_e( 'WooCommerce is updating product data in the background.', 'woocommerce' ); ?></strong>&nbsp;
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
		&nbsp;<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-status&tab=action-scheduler&s=wc_update_product_lookup_tables&status=pending' ) ); ?>"><?php esc_html_e( 'View progress &rarr;', 'woocommerce' ); ?></a>
	</p>
</div>
