<?php
/**
 * Unit tests for the WC_Admin_Functions_Test class
 *
 * @package WooCommerce\Tests\Admin
 */

/**
 * Class WC_Admin_Functions_Test_Test
 */
class WC_Admin_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * Load up the importer classes since they aren't loaded by default.
	 */
	public function setUp() {
		parent::setUp();

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/admin/wc-admin-functions.php';
	}

	/**
	 * Test wc_get_current_admin_url() function.
	 */
	public function test_wc_get_current_admin_url() {
		// Since REQUEST_URI is empty on unit tests it should return an empty string.
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			$this->assertEquals( '', wc_get_current_admin_url() );
		}

		// Test with REQUEST_URI.
		$default_uri            = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=wc-admin&foo=bar';
		$this->assertEquals( admin_url( 'admin.php?page=wc-admin&foo=bar' ), wc_get_current_admin_url() );

		// Test if nonce gets removed.
		$_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=wc-admin&_wpnonce=xxxxxxxxxxxx';
		$this->assertEquals( admin_url( 'admin.php?page=wc-admin' ), wc_get_current_admin_url() );

		// Restore REQUEST_URI.
		$_SERVER['REQUEST_URI'] = $default_uri;
	}
}
