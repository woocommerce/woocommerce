<?php
/**
 * Admin View: Notice - Untested extensions.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="wc_untested_extensions_modal">
	<div class="wc_untested_extensions_modal--content">
		<h1><?php _e( "This is a major update, are you sure you're ready?", 'woocommerce' ); ?></h1>
		<div class="wc_plugin_upgrade_notice extensions_warning">
			<p><?php
					/* translators: %s: version number */
					printf(
						__( 'The following active plugin(s) have not declared compatibility with WooCommerce %s yet and should be updated and examined further before you proceed:', 'woocommerce' ),
						esc_html( $new_version )
					);
			?></p>

			<div class="plugin-details-table-container">
				<table class="plugin-details-table" cellspacing="0">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Plugin', 'woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Tested up to WooCommerce version', 'woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $plugins as $plugin ) : ?>
							<tr>
								<td><?php echo esc_html( $plugin['Name'] ); ?></td>
								<td><?php echo esc_html( $plugin['WC tested up to'] ); ?></td>
							</tr>
						<?php endforeach ?>
					</tbody>
				</table>
			</div>

			<p><?php esc_html_e( 'As this is a major update, we strongly recommend creating a backup of your site before updating.', 'woocommerce' ); ?> <a href="https://woocommerce.com/2017/05/create-use-backups-woocommerce/" target="_blank"><?php _e( 'Learn more', 'woocommerce' ); ?></a></p>

			<?php if ( current_user_can( 'update_plugins' ) ): ?>
				<div class="actions">
					<a href="#" class="button button-secondary cancel"><?php esc_html_e( 'Cancel', 'woocommerce' ); ?></a>
					<a class="button button-primary accept" href="#"><?php esc_html_e( 'Update now', 'woocommerce' ); ?></a>
				</div>
			<?php endif ?>
		</div>
	</div>
</div>
