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
 * WooCommerce test double.
 */
class WooCommerce_Test_Double {}

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
		delete_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION );
		delete_option( Woocommerce_Analytics::PROXY_TRACKING_EVER_ENABLED_OPTION );
		delete_transient( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT );

		// Remove any filters that might interfere.
		remove_all_filters( 'woocommerce_analytics_auto_install_proxy_speed_module' );
		remove_all_filters( 'woocommerce_analytics_experimental_proxy_tracking_enabled' );
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
		delete_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION );
		delete_option( Woocommerce_Analytics::PROXY_TRACKING_EVER_ENABLED_OPTION );
		delete_transient( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT );
		remove_all_filters( 'woocommerce_analytics_experimental_proxy_tracking_enabled' );

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
	 * An absent version means the module was never installed, so the removal branch
	 * must not run: it would revoke an authorization this site never granted.
	 */
	public function test_maybe_update_proxy_speed_module_skips_when_version_is_false(): void {
		// Ensure version option doesn't exist (simulates first install).
		delete_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION );

		// The only thing removal touches that is observable from here.
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION, 'yes' );

		// Call the method.
		Woocommerce_Analytics::maybe_update_proxy_speed_module();

		$this->assertSame(
			'yes',
			get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION ),
			'Removal ran against a site that never installed the module.'
		);
		$this->assertFalse( get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION ) );

		// Transient should be set (check was performed).
		$this->assertSame( 1, get_transient( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT ) );
	}

	/**
	 * Test that update is skipped when version matches current package version.
	 */
	public function test_maybe_update_proxy_speed_module_skips_when_version_matches(): void {
		// Enable both flags: the module is only installed where the feature it
		// accelerates is also on.
		add_filter( 'woocommerce_analytics_auto_install_proxy_speed_module', '__return_true' );
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );

		// Set version to match current.
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION, Woocommerce_Analytics::PACKAGE_VERSION );

		// Call the method.
		Woocommerce_Analytics::maybe_update_proxy_speed_module();

		// Version should remain the same (no update needed).
		$this->assertSame( Woocommerce_Analytics::PACKAGE_VERSION, get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION ) );

		remove_filter( 'woocommerce_analytics_auto_install_proxy_speed_module', '__return_true' );
		remove_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
	}

	/**
	 * Turning proxy tracking off must uninstall the speed module, not just leave
	 * the REST route unregistered. Otherwise the module keeps intercepting POSTs
	 * at MU-plugin stage on a site whose operator believes the endpoint is gone.
	 */
	public function test_maybe_update_proxy_speed_module_removes_when_proxy_tracking_disabled(): void {
		// The module's own flag stays on; only proxy tracking is off.
		add_filter( 'woocommerce_analytics_auto_install_proxy_speed_module', '__return_true' );

		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION, Woocommerce_Analytics::PACKAGE_VERSION );

		Woocommerce_Analytics::maybe_update_proxy_speed_module();

		$this->assertFalse(
			get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION ),
			'The speed module must be uninstalled when proxy tracking is disabled.'
		);

		remove_filter( 'woocommerce_analytics_auto_install_proxy_speed_module', '__return_true' );
	}

	/**
	 * The install path needs both flags, so that an operator cannot end up with a
	 * module installed for a feature that is switched off.
	 */
	public function test_maybe_add_proxy_speed_module_requires_proxy_tracking_too(): void {
		add_filter( 'woocommerce_analytics_auto_install_proxy_speed_module', '__return_true' );

		Woocommerce_Analytics::maybe_add_proxy_speed_module();

		// Asserted on the options the install path writes, not on a temp directory:
		// WPMU_PLUGIN_DIR is already defined by the test bootstrap, so a path
		// assertion here would pass whether or not the guard exists.
		$this->assertFalse(
			get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION ),
			'No module may be installed while proxy tracking is disabled.'
		);
		$this->assertFalse(
			get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION ),
			'An uninstalled module must not be authorized to serve.'
		);

		remove_filter( 'woocommerce_analytics_auto_install_proxy_speed_module', '__return_true' );
	}

	/**
	 * The module fails closed on the authorization option, so it has to be written
	 * before the file can exist. Asserted on an install that fails after that point,
	 * because the ordering is not observable once both have succeeded.
	 */
	public function test_the_install_path_authorizes_before_it_writes_the_file(): void {
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
		add_filter( 'woocommerce_analytics_auto_install_proxy_speed_module', '__return_true' );
		add_filter( 'filesystem_method', array( $this, 'force_unusable_filesystem' ) );

		Woocommerce_Analytics::maybe_add_proxy_speed_module();

		remove_filter( 'filesystem_method', array( $this, 'force_unusable_filesystem' ) );

		$this->assertSame(
			'yes',
			get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION ),
			'A module written before this option exists refuses every request until the next init.'
		);
		$this->assertFalse(
			get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION ),
			'No version may be recorded for a module that was never written.'
		);
	}

	/**
	 * The sync runs on `init` for every request on every site carrying this package,
	 * and the module already treats an absent option as unauthorized. Writing `no`
	 * anyway puts an autoloaded row on the overwhelming majority of installs that
	 * will never turn proxy tracking on.
	 */
	public function test_a_site_that_never_authorized_the_module_gets_no_row(): void {
		Woocommerce_Analytics::sync_proxy_tracking_state();
		Woocommerce_Analytics::sync_proxy_tracking_state();

		$this->assertFalse(
			get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION ),
			'An absent option already means unauthorized; the row buys nothing.'
		);
	}

	/**
	 * Revoking still has to leave a value behind, since the module reads the option
	 * and an absent one is only safe while no module was ever authorized.
	 */
	public function test_revoking_an_authorized_module_writes_no(): void {
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
		add_filter( 'woocommerce_analytics_auto_install_proxy_speed_module', '__return_true' );
		Woocommerce_Analytics::sync_proxy_tracking_state();

		remove_all_filters( 'woocommerce_analytics_auto_install_proxy_speed_module' );
		Woocommerce_Analytics::sync_proxy_tracking_state();

		$this->assertSame(
			'no',
			get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION ),
			'A module already on disk must be told to stop, not merely left unmentioned.'
		);
	}

	/**
	 * The MU-plugin reads what this writes, and the conditions the early returns in
	 * should_track_store() test say nothing about whether the feature is on. Losing
	 * the registration, or moving it below one of them, leaves the module serving on
	 * a stale value with the whole suite green.
	 */
	public function test_the_state_sync_is_registered_even_when_tracking_bails(): void {
		remove_all_actions( 'init' );

		$this->assertFalse(
			Woocommerce_Analytics::should_track_store(),
			'This test is only meaningful while should_track_store() takes an early return.'
		);
		$this->assertSame(
			20,
			has_action( 'init', array( Woocommerce_Analytics::class, 'sync_proxy_tracking_state' ) ),
			'The sync must be registered before the early returns, at the priority rest_api_init depends on.'
		);
	}

	/**
	 * The registration is only worth anything if `init` actually writes the state.
	 */
	public function test_the_init_action_writes_the_state(): void {
		remove_all_actions( 'init' );
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );

		Woocommerce_Analytics::should_track_store();
		do_action( 'init' );

		$this->assertSame(
			'yes',
			get_option( Woocommerce_Analytics::PROXY_TRACKING_EVER_ENABLED_OPTION ),
			'Without this the REST route is never registered on a site that enabled the feature.'
		);
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
		// Both flags, or the method returns at its eligibility guard and the file
		// assertion below holds whatever the version check does.
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
		add_filter( 'woocommerce_analytics_auto_install_proxy_speed_module', '__return_true' );

		// Set version to match current.
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION, Woocommerce_Analytics::PACKAGE_VERSION );

		// Define WPMU_PLUGIN_DIR if not defined.
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
			define( 'WPMU_PLUGIN_DIR', $this->temp_mu_plugin_dir );
		}

		// Call the method.
		Woocommerce_Analytics::maybe_add_proxy_speed_module();

		$this->assertSame(
			'yes',
			get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION ),
			'The eligibility guard returned first, so the version check was never reached.'
		);

		// No file should be created since version matches.
		$mu_plugin_file = $this->temp_mu_plugin_dir . '/woocommerce-analytics-proxy-speed-module.php';
		$this->assertFileDoesNotExist( $mu_plugin_file );
	}

	/**
	 * MU-plugins load whether or not the plugin carrying this package is active,
	 * so a module file that outlives a deactivation must not be left holding a
	 * stale `yes`. The sticky option survives: cached pages outlive both.
	 */
	public function test_removing_the_module_drops_its_authorization(): void {
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
		add_filter( 'woocommerce_analytics_auto_install_proxy_speed_module', '__return_true' );
		Woocommerce_Analytics::sync_proxy_tracking_state();

		$this->assertSame( 'yes', get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION ) );

		// Both filters stay on, so a later sync cannot be what clears the option: the
		// removal itself has to, or a module file that outlives a deactivation keeps
		// serving on the last value written.
		Woocommerce_Analytics::maybe_remove_proxy_speed_module();

		$this->assertNotSame(
			'yes',
			get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION ),
			'Removal must revoke immediately, before any later init can run.'
		);
		$this->assertSame(
			'yes',
			get_option( Woocommerce_Analytics::PROXY_TRACKING_EVER_ENABLED_OPTION ),
			'The sticky option records that cached pages may exist, which removal does not undo.'
		);
	}

	/**
	 * WP_Filesystem() returns false outright on hosts that ask for credentials, and
	 * that is precisely when the module file survives and keeps loading. Revoking
	 * has to happen anyway, since it needs no filesystem at all.
	 */
	public function test_removal_revokes_even_when_the_filesystem_is_unavailable(): void {
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
		add_filter( 'woocommerce_analytics_auto_install_proxy_speed_module', '__return_true' );
		Woocommerce_Analytics::sync_proxy_tracking_state();
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION, Woocommerce_Analytics::PACKAGE_VERSION );

		$this->assertSame( 'yes', get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION ) );

		add_filter( 'filesystem_method', array( $this, 'force_unusable_filesystem' ) );
		Woocommerce_Analytics::maybe_remove_proxy_speed_module();
		remove_filter( 'filesystem_method', array( $this, 'force_unusable_filesystem' ) );

		$this->assertNotSame(
			'yes',
			get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION ),
			'An undeletable module must not be left holding its authorization.'
		);
		$this->assertNotFalse(
			get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION ),
			'The version option is what schedules the retry, so a failed removal must keep it.'
		);
	}

	/**
	 * Makes WP_Filesystem() fail the way a host requiring credentials does.
	 *
	 * @return string
	 */
	public function force_unusable_filesystem() {
		return 'ftpext';
	}

	/**
	 * The sticky option has to be clearable, or a site that tried the feature once
	 * carries the endpoint for good with no supported way back.
	 */
	public function test_reset_proxy_tracking_state_clears_both_options(): void {
		// Both flags, or the sync leaves the authorization option absent and the
		// second assertion below is true without anything having been cleared.
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
		add_filter( 'woocommerce_analytics_auto_install_proxy_speed_module', '__return_true' );
		Woocommerce_Analytics::sync_proxy_tracking_state();

		$this->assertSame( 'yes', get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION ) );

		Woocommerce_Analytics::reset_proxy_tracking_state();

		$this->assertFalse( get_option( Woocommerce_Analytics::PROXY_TRACKING_EVER_ENABLED_OPTION ) );
		$this->assertFalse( get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION ) );
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
	 * The speed module runs at MU-plugin stage, where no plugin has registered a
	 * callback on the proxy tracking filter yet, so it cannot ask `Features` and
	 * has to read a persisted answer instead.
	 */
	public function test_sync_proxy_tracking_state_records_the_resolved_value(): void {
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
		add_filter( 'woocommerce_analytics_auto_install_proxy_speed_module', '__return_true' );

		Woocommerce_Analytics::sync_proxy_tracking_state();

		$this->assertSame( 'yes', get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION ) );

		remove_all_filters( 'woocommerce_analytics_experimental_proxy_tracking_enabled' );

		Woocommerce_Analytics::sync_proxy_tracking_state();

		$this->assertSame(
			'no',
			get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION ),
			'Turning the feature off must be mirrored too, or the module never stops answering.'
		);
	}

	/**
	 * The mirror runs on every front-end and admin request, so it must not write
	 * to the options table when nothing changed.
	 */
	public function test_sync_proxy_tracking_state_does_not_rewrite_an_unchanged_value(): void {
		Woocommerce_Analytics::sync_proxy_tracking_state();

		$writes = 0;
		$spy    = function ( $value ) use ( &$writes ) {
			++$writes;
			return $value;
		};
		add_filter( 'pre_update_option_' . Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION, $spy );

		Woocommerce_Analytics::sync_proxy_tracking_state();

		remove_filter( 'pre_update_option_' . Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION, $spy );

		$this->assertSame( 0, $writes );
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
