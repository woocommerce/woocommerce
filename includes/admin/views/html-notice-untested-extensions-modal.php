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

		<?php if ( current_user_can( 'update_plugins' ) ): ?>
			<div class="update-anyways">
				<a class="accept" href="#">
					<?php _e( 'I have read the warning and wish to update anyways', 'woocommerce' ); ?>
				</a>
			</div>
		<?php endif ?>
	</div>
</div>
