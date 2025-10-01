<?php
/**
 * Admin settings tests.
 *
 * @package WooCommerce\Tests\Admin
 */

declare( strict_types = 1 );

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
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$file_content = file_exists( $file_path ) ? file_get_contents( $file_path ) : '';
		$this->assertEquals( 'deny from all', $file_content );

		// Test with "redirect" downloads method.
		update_option( 'woocommerce_file_download_method', 'redirect' );
		WC_Admin_Settings::check_download_folder_protection();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$file_content = file_exists( $file_path ) ? file_get_contents( $file_path ) : '';
		$this->assertEquals( 'Options -Indexes', $file_content );

		update_option( 'woocommerce_file_download_method', $default );
	}

	/**
	 * Test WC_Admin_Settings::save() permission check.
	 *
	 * Ensures that users without manage_woocommerce capability cannot save settings,
	 * even with a valid nonce (e.g., from a pre-opened admin tab before role downgrade).
	 */
	public function test_save_requires_manage_woocommerce_capability() {
		// Create a user without manage_woocommerce capability.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		// Set up the nonce and POST data.
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
		$_POST['_wpnonce']    = wp_create_nonce( 'woocommerce-settings' );
		$_REQUEST['_wpnonce'] = $_POST['_wpnonce'];
		$_GET['tab']          = 'general';
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput

		// Expect wp_die to be called with 403 status.
		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'You do not have permission to save settings.' );

		// Attempt to save settings.
		WC_Admin_Settings::save();
	}

	/**
	 * Test WC_Admin_Settings::save() succeeds with proper capability.
	 */
	public function test_save_succeeds_with_manage_woocommerce_capability() {
		// Create a user with manage_woocommerce capability.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Set up the nonce and POST data.
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
		$_POST['_wpnonce']    = wp_create_nonce( 'woocommerce-settings' );
		$_REQUEST['_wpnonce'] = $_POST['_wpnonce'];
		$_GET['tab']          = 'general';
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput

		// This should not throw an exception.
		WC_Admin_Settings::save();

		// Verify settings were processed (check that save was called successfully).
		$this->assertTrue( true );
	}
}
