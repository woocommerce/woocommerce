<?php
/**
 * Plugin Version Tests
 *
 * @package WooCommerce\Admin\Tests\Reports
 * @since 3.6.4
 */

/**
 * Plugin Version Tests Class
 *
 * @package WooCommerce\Admin\Tests\Reports
 * @since 3.6.4
 */
class WC_Admin_Tests_Plugin_Version extends WP_UnitTestCase {
	/**
	 * Ensure that all version numbers match.
	 */
	public function test_version_numbers() {
		// Get package.json version.
		$package_json = file_get_contents( 'package.json' );
		$package      = json_decode( $package_json );

		// Get main plugin file header version.
		$plugin = get_file_data( 'woocommerce-admin.php', array( 'Version' => 'Version' ) );

		// Get plugin DB version.
		$db_version = defined( 'WC_ADMIN_VERSION_NUMBER' ) ? constant( 'WC_ADMIN_VERSION_NUMBER' ) : false;

		// Compare all versions to the package.json value.
		$this->assertEquals( $package->version, $plugin['Version'], 'Plugin header version does not match package.json' );
		$this->assertEquals( $package->version, $db_version, 'DB version constant does not match package.json' );
	}

	/**
	 * Ensure that a DB version callback is defined when there are updates.
	 */
	public function test_db_update_callbacks() {
		$all_callbacks = \Automattic\WooCommerce\Internal\Admin\Install::get_db_update_callbacks();

		foreach ( $all_callbacks as $version => $version_callbacks ) {
			// Verify all callbacks have been defined.
			foreach ( $version_callbacks as $version_callback ) {
				$this->assertTrue( function_exists( $version_callback ), "Callback {$version_callback}() is not defined." );
			}

			// Verify there is a version update callback for each version.
			$version_string = str_replace( '.', '', $version );
			$expected_callback = "wc_admin_update_{$version_string}_db_version";

			$this->assertContains( $expected_callback, $version_callbacks, "Expected DB update callback {$expected_callback}() was not found." );
		}
	}
}
