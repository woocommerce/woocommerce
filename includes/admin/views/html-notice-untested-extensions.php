<?php
/**
 * Admin View: Notice - Untested extensions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php /* Close previous message container. */ ?>
</p></div>

<div class="update-message notice inline notice-error notice-alt wc-untested-extensions-notice">
	<p>
		<strong>
			<?php
				/* translators: %s: version number */
				printf(
					__( 'Heads up! The following plugin(s) are not listed compatible with WooCommerce %s yet. If you upgrade without upgrading these extensions first, you may experience issues:', 'woocommerce' ),
					wc_clean( $response->new_version )
				);
			?>
		</strong><br />

		<?php foreach ( $plugins as $plugin ): ?>
			<span><?php echo wc_clean( $plugin['Name'] ); ?></span>
		<?php endforeach ?>

		<?php /* The container will get closed automatically after the 'in_plugin_update_message' hook. */ ?>
