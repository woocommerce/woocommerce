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
		<h1><?php _e( 'Are you sure you want to update?', 'woocommerce' ); ?></h1>
		<div class="wc_plugin_upgrade_notice extensions_warning">
			<p><?php
					/* translators: %s: version number */
					printf(
						__( 'The following plugins have not declared compatibility with WooCommerce %s yet:', 'woocommerce' ),
						esc_html( $new_version )
					);
			?></p>

			<ul class="plugin-details-list">
				<?php foreach ( $plugins as $plugin ): ?>
					<li class="plugin-details">
						<?php echo esc_html( $plugin['Name'] ); ?>
						&mdash;
						<?php
						/* translators: %s: version number */
						echo esc_html( sprintf( __( 'Tested up to %s', 'woocommerce' ), wc_clean( $plugin['WC tested up to'] ) ) );
						?>
					</li>
				<?php endforeach ?>
			</ul>

			<p><?php
					/* translators: %s: version number */
					printf(
						__( 'This is a major update. Please update these extensions before proceeding or you may experience issues. We also recommend making a site backup.', 'woocommerce' ),
						esc_html( $new_version )
					);
			?></p>

			<?php if ( current_user_can( 'update_plugins' ) ): ?>
				<div class="actions">
					<a href="#" class="button button-secondary cancel"><?php esc_html_e( 'Cancel update', 'woocommerce' ); ?></a>
					<a class="button button-primary accept" href="#"><?php esc_html_e( 'Continue to update', 'woocommerce' ); ?></a>
				</div>
			<?php endif ?>
		</div>
	</div>
</div>
