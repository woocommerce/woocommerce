<?php
/**
 * Tests for the Woocommerce_Analytics class.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use Automattic\Woocommerce_Analytics;
use WorDBless\BaseTestCase;

/**
 * Tests for the Woocommerce_Analytics class.
 *
 * Focuses on testing the MU-plugin auto-update mechanism.
 */
class Woocommerce_Analytics_Test extends BaseTestCase {

	/**
	 * Temporary directory for MU-plugins during tests.
	 *
	 * @var string
	 */
	private $temp_mu_plugin_dir;

	/**
	 * Set up test environment.
	 */
	public function set_up(): void {
		parent::set_up();

		// Create a temporary directory for MU-plugins.
		$this->temp_mu_plugin_dir = sys_get_temp_dir() . '/wc-analytics-test-mu-plugins-' . uniqid();
		mkdir( $this->temp_mu_plugin_dir, 0755, true );

		// Clean up any existing options/transients.
		delete_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION );
		delete_transient( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT );

		// Remove any filters that might interfere.
		remove_all_filters( 'woocommerce_analytics_auto_install_proxy_speed_module' );
	}

	/**
	 * Tear down test environment.
	 */
	public function tear_down(): void {
		// Clean up temporary directory.
		if ( is_dir( $this->temp_mu_plugin_dir ) ) {
			$this->recursive_rmdir( $this->temp_mu_plugin_dir );
		}

		// Clean up options and transients.
		delete_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION );
		delete_transient( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT );

		parent::tear_down();
	}

	/**
	 * Recursively remove a directory.
	 *
	 * @param string $dir Directory path.
	 */
	private function recursive_rmdir( $dir ): void {
		if ( is_dir( $dir ) ) {
			$objects = scandir( $dir );
			foreach ( $objects as $object ) {
				if ( $object !== '.' && $object !== '..' ) {
					$path = $dir . '/' . $object;
					if ( is_dir( $path ) ) {
						$this->recursive_rmdir( $path );
					} else {
						unlink( $path );
					}
				}
			}
			rmdir( $dir );
		}
	}

	/**
	 * Test that transient throttling prevents frequent checks.
	 */
	public function test_maybe_update_proxy_speed_module_skips_when_transient_exists(): void {
		// Set the transient to simulate recent check.
		set_transient( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT, 1, DAY_IN_SECONDS );

		// Set a different version to ensure update would normally trigger.
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION, '0.0.1' );

		// Call the method.
		Woocommerce_Analytics::maybe_update_proxy_speed_module();

		// Version should remain unchanged (update was skipped due to transient).
		$this->assertSame( '0.0.1', get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION ) );
	}

	/**
	 * Test that transient is set after version check.
	 */
	public function test_maybe_update_proxy_speed_module_sets_transient(): void {
		// Ensure no transient exists.
		delete_transient( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT );

		// Set version to match current (no update needed).
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION, Woocommerce_Analytics::PACKAGE_VERSION );

		// Call the method.
		Woocommerce_Analytics::maybe_update_proxy_speed_module();

		// Transient should now be set.
		$this->assertSame( 1, get_transient( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT ) );
	}

	/**
	 * Test that update is skipped when version option is false (first install).
	 */
	public function test_maybe_update_proxy_speed_module_skips_when_version_is_false(): void {
		// Ensure version option doesn't exist (simulates first install).
		delete_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION );

		// Call the method.
		Woocommerce_Analytics::maybe_update_proxy_speed_module();

		// Version should still not exist (maybe_add_proxy_speed_module was not called).
		$this->assertFalse( get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION ) );

		// Transient should be set (check was performed).
		$this->assertSame( 1, get_transient( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT ) );
	}

	/**
	 * Test that update is skipped when version matches current package version.
	 */
	public function test_maybe_update_proxy_speed_module_skips_when_version_matches(): void {
		// Enable the feature flag so the update path is checked.
		add_filter( 'woocommerce_analytics_auto_install_proxy_speed_module', '__return_true' );

		// Set version to match current.
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION, Woocommerce_Analytics::PACKAGE_VERSION );

		// Call the method.
		Woocommerce_Analytics::maybe_update_proxy_speed_module();

		// Version should remain the same (no update needed).
		$this->assertSame( Woocommerce_Analytics::PACKAGE_VERSION, get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION ) );

		remove_filter( 'woocommerce_analytics_auto_install_proxy_speed_module', '__return_true' );
	}

	/**
	 * Test that MU-plugin is removed when feature flag is disabled and version exists.
	 */
	public function test_maybe_update_proxy_speed_module_removes_when_flag_disabled(): void {
		// Set a version to simulate existing installation.
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION, Woocommerce_Analytics::PACKAGE_VERSION );

		// Feature flag is off by default — no filter needed.
		Woocommerce_Analytics::maybe_update_proxy_speed_module();

		// Version option should be deleted (MU-plugin removal cleans it up).
		$this->assertFalse( get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION ) );
	}

	/**
	 * Test that auto-installation is disabled by default.
	 */
	public function test_maybe_add_proxy_speed_module_disabled_by_default(): void {
		// Call the method without any filter (default is false).
		Woocommerce_Analytics::maybe_add_proxy_speed_module();

		// Version option should not be set since auto-install is disabled by default.
		$this->assertFalse( get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION ) );
	}

	/**
	 * Test that filter can enable auto-installation.
	 */
	public function test_maybe_add_proxy_speed_module_respects_filter(): void {
		$filter_called = false;
		$filter_cb     = function () use ( &$filter_called ) {
			$filter_called = true;
			return true;
		};

		add_filter( 'woocommerce_analytics_auto_install_proxy_speed_module', $filter_cb );

		// Call the method - it will proceed past the filter check but may stop at other checks
		// (e.g., filesystem init, WPMU_PLUGIN_DIR). The point is the filter is respected.
		Woocommerce_Analytics::maybe_add_proxy_speed_module();

		$this->assertTrue( $filter_called, 'The auto_install_proxy_speed_module filter should be checked.' );

		remove_filter( 'woocommerce_analytics_auto_install_proxy_speed_module', $filter_cb );
	}

	/**
	 * Test that maybe_add_proxy_speed_module skips when version already matches.
	 */
	public function test_maybe_add_proxy_speed_module_skips_when_version_matches(): void {
		// Set version to match current.
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION, Woocommerce_Analytics::PACKAGE_VERSION );

		// Define WPMU_PLUGIN_DIR if not defined.
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
			define( 'WPMU_PLUGIN_DIR', $this->temp_mu_plugin_dir );
		}

		// Call the method.
		Woocommerce_Analytics::maybe_add_proxy_speed_module();

		// No file should be created since version matches.
		$mu_plugin_file = $this->temp_mu_plugin_dir . '/woocommerce-analytics-proxy-speed-module.php';
		$this->assertFileDoesNotExist( $mu_plugin_file );
	}

	/**
	 * Test that maybe_remove_proxy_speed_module cleans up options and transients.
	 */
	public function test_maybe_remove_proxy_speed_module_cleans_up(): void {
		// Set up initial state.
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION, '1.0.0' );
		set_transient( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT, 1, DAY_IN_SECONDS );

		// Call the method.
		Woocommerce_Analytics::maybe_remove_proxy_speed_module();

		// Options and transients should be deleted.
		$this->assertFalse( get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION ) );
		$this->assertFalse( get_transient( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT ) );
	}

	/**
	 * Test PACKAGE_VERSION constant exists and is valid semver format.
	 */
	public function test_package_version_constant_is_valid(): void {
		$this->assertNotEmpty( Woocommerce_Analytics::PACKAGE_VERSION );
		$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+/', Woocommerce_Analytics::PACKAGE_VERSION );
	}

	/**
	 * Test version option constant is defined.
	 */
	public function test_version_option_constant_is_defined(): void {
		$this->assertSame(
			'woocommerce_analytics_proxy_speed_module_version',
			Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION
		);
	}

	/**
	 * Test version check transient constant is defined.
	 */
	public function test_version_check_transient_constant_is_defined(): void {
		$this->assertSame(
			'woocommerce_analytics_proxy_speed_module_version_check',
			Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT
		);
	}
}
