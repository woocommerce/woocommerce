<?php
/**
 * Class WC_Tests_WC_Helper file.
 *
 * @package WooCommerce|Tests|WC_Helper.
 */

/**
 * Class WC_Tests_WC_Helper.
 */
class WC_Tests_WC_Helper extends WC_Unit_Test_Case {

	/**
	 * Test that woo plugins are loaded correctly even if incorrect cache is intially set.
	 */
	public function test_get_local_woo_plugins_without_woo_header_cache() {
		$woocommerce_key = 'sample-woo-plugin.php';

		remove_filter( 'extra_plugin_headers', 'wc_enable_wc_plugin_headers' );
		wp_clean_plugins_cache( false );
		get_plugins();

		add_filter( 'extra_plugin_headers', 'wc_enable_wc_plugin_headers' );

		$woo_plugins = WC_Helper::get_local_woo_plugins();

		// Restore previous state.
		wp_clean_plugins_cache( false );

		$this->assertArrayHasKey( $woocommerce_key, $woo_plugins );
	}

}
