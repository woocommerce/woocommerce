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
		<?php echo wc_clean( $message ) ?>
	</strong><br />

	<?php foreach ( $plugins as $plugin ): ?>
		<span><?php echo wc_clean( $plugin['Name'] ); ?></span>
	<?php endforeach ?>
</p>
