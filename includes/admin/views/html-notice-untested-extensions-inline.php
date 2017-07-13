<?php
/**
 * Admin View: Notice - Untested extensions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<p class="wc_plugin_upgrade_notice extensions_warning <?php echo esc_attr( $upgrade_type ) ?>">
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
</p>
