<?php
/**
 * Tests for the Woocommerce_Analytics class.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use Automattic\Woocommerce_Analytics;
use PHPUnit\Framework\Attributes\DataProvider;
use WorDBless\BaseTestCase;

/**
 * WooCommerce test double.
 */
class WooCommerce_Test_Double {}

/**
 * Tests for the Woocommerce_Analytics class.
 *
 * Focuses on the proxy tracking state the speed module reads, and the leftover MU-plugin cleanup.
 */
class Woocommerce_Analytics_Test extends BaseTestCase {

	/**
	 * Set up test environment.
	 */
	public function set_up(): void {
		parent::set_up();

		// Clean up any existing options/transients.
		delete_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION );
		delete_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION );
		delete_option( Woocommerce_Analytics::PROXY_TRACKING_EVER_ENABLED_OPTION );
		delete_transient( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT );

		// Remove any filters that might interfere.
		delete_option( Woocommerce_Analytics::PROXY_TRACKING_ENABLED_OPTION );
		remove_all_filters( 'woocommerce_analytics_experimental_proxy_tracking_enabled' );
	}

	/**
	 * Tear down test environment.
	 */
	public function tear_down(): void {
		// Clean up options and transients.
		delete_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION );
		delete_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION );
		delete_option( Woocommerce_Analytics::PROXY_TRACKING_EVER_ENABLED_OPTION );
		delete_transient( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT );
		remove_all_filters( 'woocommerce_analytics_experimental_proxy_tracking_enabled' );
		delete_option( Woocommerce_Analytics::PROXY_TRACKING_ENABLED_OPTION );

		parent::tear_down();
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
	 * Jetpack still calls this on activation, so it must stay callable and write nothing.
	 */
	public function test_maybe_add_proxy_speed_module_installs_nothing(): void {
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );

		Woocommerce_Analytics::maybe_add_proxy_speed_module();

		$this->assertFalse( get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION ) );
		$this->assertFalse( get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION ) );
	}

	/**
	 * A file an earlier version installed runs stale code, so the daily check removes it, and retries if it cannot.
	 *
	 * @dataProvider provide_leftover_module_files
	 *
	 * @param bool $deletable Whether the file can be deleted.
	 */
	#[DataProvider( 'provide_leftover_module_files' )]
	public function test_maybe_update_proxy_speed_module_removes_a_leftover_module( $deletable ): void {
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION, '0.18.1' );
		// Not wp_mkdir_p(): it refuses the `..` in the test bootstrap's WPMU_PLUGIN_DIR.
		$created_dir = ! is_dir( WPMU_PLUGIN_DIR ) && mkdir( WPMU_PLUGIN_DIR, 0755, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
		$file        = trailingslashit( WPMU_PLUGIN_DIR ) . 'woocommerce-analytics-proxy-speed-module.php';
		file_put_contents( $file, '<?php' );
		if ( ! $deletable ) {
			chmod( $file, 0444 );
		}

		try {
			Woocommerce_Analytics::maybe_update_proxy_speed_module();

			$this->assertSame( ! $deletable, file_exists( $file ) );
			$this->assertSame(
				$deletable ? false : '0.18.1',
				get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION ),
				'The version option schedules the retry, so only a removed file may clear it.'
			);
		} finally {
			if ( file_exists( $file ) ) {
				chmod( $file, 0644 );
				unlink( $file );
			}
			if ( $created_dir ) {
				rmdir( WPMU_PLUGIN_DIR );
			}
		}
	}

	/**
	 * Leftover module files.
	 *
	 * @return array
	 */
	public static function provide_leftover_module_files(): array {
		return array(
			'deletable'   => array( true ),
			'undeletable' => array( false ),
		);
	}

	/**
	 * A callback on the deprecated filter still runs during its deprecation window, and is told so.
	 */
	public function test_the_daily_check_still_fires_the_deprecated_auto_install_filter(): void {
		$notices = 0;
		add_action(
			'deprecated_hook_run',
			function () use ( &$notices ) {
				++$notices;
			}
		);
		add_filter( 'deprecated_hook_trigger_error', '__return_false' );
		add_filter( 'woocommerce_analytics_auto_install_proxy_speed_module', '__return_true' );

		try {
			Woocommerce_Analytics::maybe_update_proxy_speed_module();

			$this->assertSame( 1, $notices );
			$this->assertFalse( get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION ), 'The deprecated filter must not install anything.' );
		} finally {
			remove_all_actions( 'deprecated_hook_run' );
			remove_all_filters( 'deprecated_hook_trigger_error' );
			remove_all_filters( 'woocommerce_analytics_auto_install_proxy_speed_module' );
		}
	}

	/**
	 * Proxy tracking is off here, the case where a leftover MU-plugin answering would be wrong.
	 */
	public function test_the_sync_revokes_a_leftover_module_authorization(): void {
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION, 'yes' );

		Woocommerce_Analytics::sync_proxy_tracking_state();

		$this->assertFalse(
			get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION ),
			'The leftover MU-plugin serves while this is yes, so the first sync after an update must drop it.'
		);
	}

	/**
	 * Deactivation has to stop the module even if another plugin's copy of the package never runs `init`.
	 */
	public function test_deactivation_clears_the_proxy_tracking_state(): void {
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
		Woocommerce_Analytics::sync_proxy_tracking_state();

		$this->assertSame( 'yes', get_option( Woocommerce_Analytics::PROXY_TRACKING_ENABLED_OPTION ) );

		Woocommerce_Analytics::maybe_remove_proxy_speed_module();

		$this->assertFalse( get_option( Woocommerce_Analytics::PROXY_TRACKING_ENABLED_OPTION ) );
	}

	/**
	 * The sync runs on `init` for every request on every site carrying this package,
	 * and the module already treats an absent option as off. Writing `no`
	 * anyway puts an autoloaded row on the overwhelming majority of installs that
	 * will never turn proxy tracking on.
	 */
	public function test_a_site_that_never_enabled_proxy_tracking_gets_no_row(): void {
		Woocommerce_Analytics::sync_proxy_tracking_state();
		Woocommerce_Analytics::sync_proxy_tracking_state();

		$this->assertFalse(
			get_option( Woocommerce_Analytics::PROXY_TRACKING_ENABLED_OPTION ),
			'An absent option already means off; the row buys nothing.'
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
	 * MU-plugins load whether or not the plugin carrying this package is active,
	 * so a module file that outlives a deactivation must not be left holding a
	 * stale `yes`. The sticky option survives: cached pages outlive both.
	 */
	public function test_removing_the_module_drops_its_authorization(): void {
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
		Woocommerce_Analytics::sync_proxy_tracking_state();
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION, 'yes' );

		$this->assertSame( 'yes', get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION ) );

		// No sync runs once the plugin is deactivated, so the removal itself has to
		// clear it, or a module file that outlives a deactivation keeps serving.
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
		Woocommerce_Analytics::sync_proxy_tracking_state();
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION, 'yes' );
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
		// Proxy tracking on, or the sync leaves the option absent and the second
		// assertion below is true without anything having been cleared.
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
		Woocommerce_Analytics::sync_proxy_tracking_state();

		$this->assertSame( 'yes', get_option( Woocommerce_Analytics::PROXY_TRACKING_ENABLED_OPTION ) );

		Woocommerce_Analytics::reset_proxy_tracking_state();

		$this->assertFalse( get_option( Woocommerce_Analytics::PROXY_TRACKING_EVER_ENABLED_OPTION ) );
		$this->assertFalse( get_option( Woocommerce_Analytics::PROXY_TRACKING_ENABLED_OPTION ) );
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

		Woocommerce_Analytics::sync_proxy_tracking_state();

		$this->assertSame( 'yes', get_option( Woocommerce_Analytics::PROXY_TRACKING_ENABLED_OPTION ) );

		remove_all_filters( 'woocommerce_analytics_experimental_proxy_tracking_enabled' );

		Woocommerce_Analytics::sync_proxy_tracking_state();

		$this->assertSame(
			'no',
			get_option( Woocommerce_Analytics::PROXY_TRACKING_ENABLED_OPTION ),
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
		add_filter( 'pre_update_option_' . Woocommerce_Analytics::PROXY_TRACKING_ENABLED_OPTION, $spy );

		Woocommerce_Analytics::sync_proxy_tracking_state();

		remove_filter( 'pre_update_option_' . Woocommerce_Analytics::PROXY_TRACKING_ENABLED_OPTION, $spy );

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
	 * Every event, page-embedded or server-fired, carries the package version so
	 * malformed events in Tracks can be attributed to the release that sent them.
	 */
	public function test_package_version_is_sent_as_common_property(): void {
		$page_properties   = WC_Analytics_Tracking::get_page_common_properties();
		$server_properties = WC_Analytics_Tracking::get_common_properties();

		$this->assertSame( Woocommerce_Analytics::PACKAGE_VERSION, $page_properties['package_version'] ?? null );
		$this->assertSame( Woocommerce_Analytics::PACKAGE_VERSION, $server_properties['package_version'] ?? null );
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
