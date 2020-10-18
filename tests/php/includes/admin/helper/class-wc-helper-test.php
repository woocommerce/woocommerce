<?php

/**
 * Class WC_Tests_WC_Helper.
 */
class WC_Helper_Test extends \WC_Unit_Test_Case {

	/**
	 * Test that woo plugins are loaded correctly even if incorrect cache is intially set.
	 */
	public function test_get_local_woo_plugins_without_woo_header_cache() {
		$woocommerce_key = 'sample-woo-plugin.php';

		remove_filter( 'extra_plugin_headers', 'wc_enable_wc_plugin_headers' );
		wp_clean_plugins_cache( false );
		get_plugins();

		if ( file_exists( WP_PLUGIN_DIR . '/sample-woo-plugin.php' ) ) {
			unlink( WP_PLUGIN_DIR . '/sample-woo-plugin.php' );
		}
		copy( \WC_Unit_Tests_Bootstrap::instance()->tests_dir . '/data/sample-woo-plugin.php', WP_PLUGIN_DIR . '/sample-woo-plugin.php' );

		add_filter( 'extra_plugin_headers', 'wc_enable_wc_plugin_headers' );

		$woo_plugins = \WC_Helper::get_local_woo_plugins();

		// Restore previous state.
		wp_clean_plugins_cache( false );

		$this->assertArrayHasKey( $woocommerce_key, $woo_plugins );
	}

}
