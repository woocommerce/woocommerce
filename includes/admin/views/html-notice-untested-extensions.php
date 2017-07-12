<?php
/**
 * Admin View: Notice - Untested extensions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="wc-untested-extensions-notice" style="display:none" class="wc-untested-extensions-notice">
	<div class="wc-untested-extensions-notice--contents">
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
	</div>
</div>
