<?php
/**
 * Plugins Helper Tests
 *
 * @package WooCommerce\Admin\Tests\PluginHelper
 */

use Automattic\WooCommerce\Admin\PluginsHelper;

/**
 * WC_Admin_Tests_Plugin_Helper Class
 *
 * @package WooCommerce\Admin\Tests\PluginHelper
 */
class WC_Admin_Tests_Plugins_Helper extends WP_UnitTestCase {

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test get_plugin_path_from_slug()
	 */
	public function test_get_plugin_path_from_slug() {

		// Installed plugin checks.
		$ak_path = PluginsHelper::get_plugin_path_from_slug( 'akismet' );
		$this->assertEquals( 'akismet/akismet.php', $ak_path, 'Path returned is not as expected.' );

		// Plugin that is not installed.
		$invalid_path = PluginsHelper::get_plugin_path_from_slug( 'invalid-plugin' );
		$this->assertEquals( false, $invalid_path, 'False should be returned when no matching plugin is installed.' );

		// Check for when slug already appears to be a path.
		$wc_path_slug = PluginsHelper::get_plugin_path_from_slug( 'woocommerce/woocommerce' );
		$this->assertEquals( 'woocommerce/woocommerce', $wc_path_slug, 'Slug should be returned if it appears to already be path.' );
	}

	/**
	 * Test get_active_plugin_slugs()
	 */
	public function test_get_active_plugin_slugs() {

		// Get active slugs.
		$active_slugs = PluginsHelper::get_active_plugin_slugs();

		// Phpunit test environment active plugins option is empty.
		$this->assertEquals( array(), $active_slugs, 'Should not be any active slugs.' );

		// Get Akismet plugin path.
		$akismet_path = PluginsHelper::get_plugin_path_from_slug( 'akismet' );

		// Activate Akismet plugin.
		activate_plugin( $akismet_path );

		// Get active slugs.
		$active_slugs = PluginsHelper::get_active_plugin_slugs();

		$this->assertEquals( array( 'akismet' ), $active_slugs, 'Akismet should be listed as active.' );
	}

	/**
	 * Test get_active_plugin_slugs()
	 */
	public function test_get_active_plugin_slugs_multisite() {
		$this->skipWithoutMultisite();

		// Get active slugs.
		$active_slugs = PluginsHelper::get_active_plugin_slugs();

		// Phpunit test environment active plugins option is empty.
		$this->assertEquals( array(), $active_slugs, 'Should not be any active slugs.' );

		// Get Akismet plugin path.
		$akismet_path = PluginsHelper::get_plugin_path_from_slug( 'akismet' );

		// Activate Akismet plugin.
		activate_plugin( $akismet_path, '', true );

		// Get active slugs.
		$active_slugs = PluginsHelper::get_active_plugin_slugs();

		$this->assertEquals( array( 'akismet' ), $active_slugs, 'Akismet should be listed as active.' );
	}

	/**
	 * Test is_plugin_installed()
	 */
	public function test_is_plugin_installed() {

		// Akismet is installed in the test environment.
		$installed = PluginsHelper::is_plugin_installed( 'akismet' );
		$this->assertEquals( true, $installed, 'Akismet should be installed.' );

		// Invalid plugin is not.
		$installed = PluginsHelper::is_plugin_installed( 'invalid-plugin' );
		$this->assertEquals( false, $installed, 'Invalid plugins should not be installed.' );
	}

	/**
	 * Test is_plugin_active()
	 */
	public function test_is_plugin_active() {

		// Check if facebook is not active. Phpunit test environment active plugins option is empty.
		$active = PluginsHelper::is_plugin_active( 'akismet' );
		$this->assertEquals( false, $active, 'Should not be any active slugs.' );

		// Get Akismet plugin path.
		$akismet_path = PluginsHelper::get_plugin_path_from_slug( 'akismet' );

		// Activate akismet plugin.
		activate_plugin( $akismet_path );

		// Check if akismet is now active.
		$activated = PluginsHelper::is_plugin_active( 'akismet' );
		$this->assertEquals( true, $activated, 'Akismet for WooCommerce should be installed.' );
	}

	/**
	 * Test get_plugin_data()
	 */
	public function test_get_plugin_data() {

		$actual_data = PluginsHelper::get_plugin_data( 'akismet' );

		$expected_keys = array(
			'WC requires at least',
			'WC tested up to',
			'Woo',
			'Name',
			'PluginURI',
			'Description',
			'Author',
			'Version',
			'AuthorURI',
			'TextDomain',
			'DomainPath',
			'Network',
			'RequiresWP',
			'RequiresPHP',
			'Title',
			'AuthorName',
		);

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $actual_data, 'Plugin data does not match expected data.' );
		}

		// Test not installed plugin response.
		$actual_data = PluginsHelper::get_plugin_data( 'my-plugin' );
		$this->assertEquals( false, $actual_data, 'Should return false if plugin is not found.' );
	}

	/**
	 * Test activate_plugins() by using Akismet.
	 */
	public function test_activate_akismet() {
		// Prepare Akismet plugin in the "installed" state.
		deactivate_plugins( 'akismet/akismet.php' );
		$this->assertTrue( PluginsHelper::is_plugin_installed( 'akismet' ) );

		// Activate the plugin.
		$test = PluginsHelper::activate_plugins( array( 'akismet' ) );

		// Assert plugin activated.
		$this->assertSame( 'akismet', $test['activated'][0] );

		// Assert no errors return.
		$this->assertFalse( $test['errors']->has_errors() );

		// Clean up.
		deactivate_plugins( 'akismet/akismet.php' );
	}

	/**
	 * Test error handling in activate_plugins().
	 */
	public function test_activate_plugins_with_error() {
		// Try to activate a plugin that has not been installed.
		$this->assertFalse( PluginsHelper::is_plugin_installed( 'foo-bar' ) );
		$test = PluginsHelper::activate_plugins( array( 'foo-bar' ) );

		// Assert plugin is NOT activated.
		$this->assertFalse( PluginsHelper::is_plugin_active( 'foo-bar' ) );

		// Assert that errors return.
		$this->assertTrue( $test['errors']->has_errors() );
	}
}
