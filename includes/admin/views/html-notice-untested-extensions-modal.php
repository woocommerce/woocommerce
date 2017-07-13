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
			<h4>
				<?php
					/* translators: %s: version number */
					printf(
						__( 'Some of your plugins are not listed compatible with WooCommerce %s yet. If you upgrade without upgrading these extensions first, you may experience issues:', 'woocommerce' ),
						wc_clean( $new_version )
					);
				?>
			</h4>

			<div class="keys">
				<div class="heading">Key</div>
				<div class="key current">&#x2587; Up to date.</div>
				<div class="key minor">&#x2587; Not up-to-date with the latest minor WooCommerce release.</div>
				<div class="key major">&#x2587; Not up-to-date with the latest major WooCommerce release.</div>
				<div class="key unknown">&#x2587; Unknown</div>
			</div>

			<?php foreach ( $plugins as $plugin ): ?>
				<div class="plugin-status <?php echo esc_attr( $plugin['UpgradeType'] ); ?>">&#x2587; <?php echo wc_clean( $plugin['Name'] ); ?></div>
			<?php endforeach ?>
		</div>

		<?php if ( current_user_can( 'update_plugins' ) ): ?>
			<p class="woocommerce-actions update-anyways">
				<a class="button-primary accept" href="#">
					<?php _e( 'I understand and wish to update anyways', 'woocommerce' ); ?>
				</a>
			</p>
		<?php endif ?>
	</div>
</div>
