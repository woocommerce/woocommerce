<?php
/**
 * Admin View: Notice - Untested extensions.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p class="wc_plugin_upgrade_notice extensions_warning <?php echo esc_attr( $upgrade_type ) ?>">
	<strong><?php echo esc_html( $message ) ?></strong><br />

	<?php foreach ( $plugins as $plugin ): ?>
		<span><?php echo esc_html( $plugin['Name'] ); ?></span>
	<?php endforeach ?>
</p>
