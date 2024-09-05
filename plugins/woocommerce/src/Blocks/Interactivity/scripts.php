<?php
use Automattic\Jetpack\Constants;

/**
 * Move interactive scripts to the footer. This is a temporary measure to make
 * it work with `wc_store` and it should be replaced with deferred scripts or
 * modules.
 */
function woocommerce_interactivity_move_interactive_scripts_to_the_footer() {
	// Move the @woocommerce/interactivity package to the footer.
	wp_script_add_data( 'wc-interactivity', 'group', 1 );

	// Move all the view scripts of the interactive blocks to the footer.
	$registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
	foreach ( array_values( $registered_blocks ) as $block ) {
		if ( isset( $block->supports['interactivity'] ) && $block->supports['interactivity'] ) {
			foreach ( $block->view_script_handles as $handle ) {
				wp_script_add_data( $handle, 'group', 1 );
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'woocommerce_interactivity_move_interactive_scripts_to_the_footer', 11 );

/**
 * Register the Interactivity API runtime and make it available to be enqueued
 * as a dependency in interactive blocks.
 */
function woocommerce_interactivity_register_runtime() {
	$plugin_path = \Automattic\WooCommerce\Blocks\Package::get_path();
	$plugin_url  = plugin_dir_url( $plugin_path . '/index.php' );

	$file = 'assets/client/blocks/wc-interactivity.js';

	$file_path = $plugin_path . $file;
	$file_url  = $plugin_url . $file;

	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file_path ) ) {
		$version = filemtime( $file_path );
	} else {
		// Use wc- prefix here to prevent collisions when WC Core version catches up to a version previously used by the WC Blocks feature plugin.
		$version = 'wc-' . Constants::get_constant( 'WC_VERSION' );
	}

	wp_register_script(
		'wc-interactivity',
		$file_url,
		array(),
		$version,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'woocommerce_interactivity_register_runtime' );
