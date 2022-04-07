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
		$package_json = file_get_contents( '../woocommerce-admin/package.json' );
		$package      = json_decode( $package_json );

		// Get main plugin file header version.
		$plugin = get_file_data( '../woocommerce-admin/woocommerce-admin.php', array( 'Version' => 'Version' ) );

		// Get plugin DB version.
		$db_version = defined( 'WC_ADMIN_VERSION_NUMBER' ) ? constant( 'WC_ADMIN_VERSION_NUMBER' ) : false;

		// Compare all versions to the package.json value.
		$this->assertEquals( $package->version, $plugin['Version'], 'Plugin header version does not match package.json' );
		$this->assertEquals( $package->version, $db_version, 'DB version constant does not match package.json' );
	}
}
