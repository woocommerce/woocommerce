<?php
/**
 * Checkout tests.
 *
 * @package WooCommerce\Tests\Checkout
 */

/**
 * Class WC_Tests_Admin_Settings.
 */
class WC_Tests_Admin_Settings extends WC_Unit_Test_Case {

	/**
	 * Test WC_Admin_Settings::check_download_folder_protection().
	 */
	public function test_check_download_folder_protection() {
		$default    = get_option( 'woocommerce_file_download_method' );
		$upload_dir = wp_get_upload_dir();
		$file_path  = $upload_dir['basedir'] . '/woocommerce_uploads/.htaccess';

		// Test with "force" downloads method.
		update_option( 'woocommerce_file_download_method', 'force' );
		WC_Admin_Settings::check_download_folder_protection();
		$file_content = @file_get_contents( $file_path );
		$this->assertEquals( 'deny from all', $file_content );

		// Test with "redirect" downloads method.
		update_option( 'woocommerce_file_download_method', 'redirect' );
		WC_Admin_Settings::check_download_folder_protection();
		$file_content = @file_get_contents( $file_path );
		$this->assertEquals( 'Options -Indexes', $file_content );

		update_option( 'woocommerce_file_download_method', $default );
	}
}
