<?php
/**
 * Admin View: Notice - Untested extensions.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wc_plugin_upgrade_notice extensions_warning <?php echo esc_attr( $upgrade_type ) ?>">
	<p>
		<strong><?php echo esc_html( $message ) ?></strong>

		<ul>
			<?php foreach ( $plugins as $plugin ) : ?>
				<li><?php
					/* translators: 1: plugin name 2: tested up to version */
					echo esc_html( sprintf( __( '%1$s (tested up to %2$s)', 'woocommerce' ), $plugin['Name'], $plugin['WC tested up to'] ) );
				?></li>
			<?php endforeach ?>
		</ul>
	</p>
</div>
