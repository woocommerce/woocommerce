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
		<div class="wc_plugin_upgrade_notice extensions_warning">
			<h1><?php _e( 'Warning', 'woocommerce' ); ?></h1>

			<h4>
				<?php
					/* translators: %s: version number */
					printf(
						__( 'These plugins are not listed compatible with WooCommerce %s yet. This is a major update. If you upgrade without updating these extensions first, you may experience issues:', 'woocommerce' ),
						esc_html( $new_version )
					);
				?>
			</h4>

			<?php foreach ( $plugins as $plugin ): ?>
				<div class="plugin-details">
					<?php echo esc_html( $plugin['Name'] ); ?>
					&mdash;
					<?php
					/* translators: %s: version number */
					echo esc_html( sprintf( __( 'Tested up to WooCommerce %s', 'woocommerce' ), wc_clean( $plugin['WC tested up to'] ) ) );
					?>
				</div>
			<?php endforeach ?>

			<?php if ( current_user_can( 'update_plugins' ) ): ?>
				<div class="actions">
					<p class="woocommerce-actions cancel">
						<a href="#"><?php _e( 'Cancel', 'woocommerce' ); ?></a>
					</p>
					<p class="woocommerce-actions update-anyways">
						<a class="button-primary accept" href="#">
							<?php _e( 'I understand and wish to update', 'woocommerce' ); ?>
						</a>
					</p>
				</div>
			<?php endif ?>
	</div>
</div>
