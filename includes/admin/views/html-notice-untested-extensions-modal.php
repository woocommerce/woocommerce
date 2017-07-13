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
			<strong>
				<?php
					/* translators: %s: version number */
					printf(
						__( 'Heads up! The following plugin(s) are not listed compatible with WooCommerce %s yet. If you upgrade without upgrading these extensions first, you may experience issues:', 'woocommerce' ),
						wc_clean( $new_version )
					);
				?>
			</strong><br />

			<?php foreach ( $plugins as $plugin ): ?>
				<span><?php echo wc_clean( $plugin['Name'] ); ?></span>
			<?php endforeach ?>
		</div>

		<div class="update-anyways">
			<a href="<?php echo wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=woocommerce/woocommerce.php' ), 'upgrade-plugin_woocommerce/woocommerce.php' ); ?>">
				<?php _e( 'Upgrade anyways', 'woocommerce' ); ?>
			</a>
		</div>
	</div>
</div>
