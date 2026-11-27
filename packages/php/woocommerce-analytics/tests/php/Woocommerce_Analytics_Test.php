<?php
/**
 * Tests for the Woocommerce_Analytics class.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use Automattic\Woocommerce_Analytics;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
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
		delete_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_EVENT_PROPS_OPTION );
		remove_all_filters( 'woocommerce_analytics_experimental_proxy_tracking_enabled' );
		remove_all_filters( 'jetpack_woocommerce_analytics_event_props' );
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
		delete_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_EVENT_PROPS_OPTION );
		remove_all_filters( 'jetpack_woocommerce_analytics_event_props' );
		wp_clear_scheduled_hook( Woocommerce_Analytics::PROXY_SPEED_MODULE_EVENT_PROPS_HOOK );
		remove_all_filters( 'wp_doing_cron' );
		wp_set_current_user( 0 );

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
	 * The speed module reads what this writes, and the conditions the early returns in
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
	 * Deactivation is the only cleanup a host runs, so it also stops what the speed module would replay on reactivation.
	 */
	public function test_maybe_remove_proxy_speed_module_stops_the_event_props_record(): void {
		wp_schedule_event( time(), 'hourly', Woocommerce_Analytics::PROXY_SPEED_MODULE_EVENT_PROPS_HOOK );
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_EVENT_PROPS_OPTION, array( 'host' => 'ecommplan' ) );

		Woocommerce_Analytics::maybe_remove_proxy_speed_module();

		$this->assertFalse( wp_next_scheduled( Woocommerce_Analytics::PROXY_SPEED_MODULE_EVENT_PROPS_HOOK ) );
		$this->assertFalse( get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_EVENT_PROPS_OPTION ) );
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
	 * The speed module answers before callbacks on the event props filter register, so cron records what they add.
	 *
	 * @dataProvider provide_event_props_callbacks
	 *
	 * @param string     $proxy_tracking The stored proxy tracking state.
	 * @param array      $added          Properties the callback adds or changes.
	 * @param array|bool $expected       The expected recorded properties, or false for no row.
	 */
	#[DataProvider( 'provide_event_props_callbacks' )]
	public function test_cron_records_what_the_event_props_filter_adds( $proxy_tracking, $added, $expected ): void {
		add_filter( 'wp_doing_cron', '__return_true' );
		update_option( Woocommerce_Analytics::PROXY_TRACKING_ENABLED_OPTION, $proxy_tracking );
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_EVENT_PROPS_OPTION, array( 'stale' => 1 ) );
		add_filter(
			'jetpack_woocommerce_analytics_event_props',
			function ( $props ) use ( $added ) {
				return array_merge( $props, $added );
			}
		);

		Woocommerce_Analytics::record_speed_module_event_props();

		$this->assertSame( $expected, get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_EVENT_PROPS_OPTION ) );
	}

	/**
	 * Event props callbacks and what cron records for them.
	 *
	 * @return array
	 */
	public static function provide_event_props_callbacks(): array {
		return array(
			'a scalar addition'           => array( 'yes', array( 'host' => 'ecommplan' ), array( 'host' => 'ecommplan' ) ),
			'a non-scalar value'          => array( 'yes', array( 'host' => 'ecommplan', 'list' => array( 1 ) ), array( 'host' => 'ecommplan' ) ),
			'a changed common property'   => array( 'yes', array( 'store_currency' => 'XXX' ), false ),
			'nothing added'               => array( 'yes', array(), false ),
			'proxy tracking off'          => array( 'no', array( 'host' => 'ecommplan' ), false ),
		);
	}

	/**
	 * A visitor's request or session must not be what gets replayed onto every other visitor's events.
	 *
	 * @dataProvider provide_record_contexts
	 *
	 * @param bool       $in_cron  Whether the record runs as cron.
	 * @param array|bool $expected The expected recorded properties, or false for no row.
	 */
	#[DataProvider( 'provide_record_contexts' )]
	public function test_the_record_holds_no_visitor( $in_cron, $expected ): void {
		if ( $in_cron ) {
			add_filter( 'wp_doing_cron', '__return_true' );
		}
		update_option( Woocommerce_Analytics::PROXY_TRACKING_ENABLED_OPTION, 'yes' );
		$user_id = wp_insert_user(
			array(
				'user_login' => 'wca-record-visitor',
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( $user_id );
		add_filter(
			'jetpack_woocommerce_analytics_event_props',
			function ( $props ) {
				return array_merge( $props, array( 'recorded_user' => get_current_user_id() ) );
			}
		);

		Woocommerce_Analytics::record_speed_module_event_props();

		$this->assertSame( $expected, get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_EVENT_PROPS_OPTION ) );
		$this->assertSame( $user_id, get_current_user_id(), 'The current user must be restored.' );
	}

	/**
	 * Contexts the record can run in.
	 *
	 * @return array
	 */
	public static function provide_record_contexts(): array {
		return array(
			'cron'        => array( true, array( 'recorded_user' => 0 ) ),
			'a page view' => array( false, false ),
		);
	}

	/**
	 * A callback that fails in cron, where there is no cart or session, keeps the last record and the current user.
	 *
	 * @dataProvider provide_failing_event_props_callbacks
	 *
	 * @param callable $callback The failing callback.
	 */
	#[DataProvider( 'provide_failing_event_props_callbacks' )]
	public function test_a_failing_event_props_callback_keeps_the_last_record( $callback ): void {
		add_filter( 'wp_doing_cron', '__return_true' );
		update_option( Woocommerce_Analytics::PROXY_TRACKING_ENABLED_OPTION, 'yes' );
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_EVENT_PROPS_OPTION, array( 'host' => 'ecommplan' ) );
		$user_id = wp_insert_user(
			array(
				'user_login' => 'wca-record-failure',
				'user_pass'  => 'password',
			)
		);
		wp_set_current_user( $user_id );
		add_filter( 'jetpack_woocommerce_analytics_event_props', $callback );
		$error_log = ini_set( 'error_log', '/dev/null' ); // phpcs:ignore WordPress.PHP.IniSet.Risky

		try {
			Woocommerce_Analytics::record_speed_module_event_props();
		} finally {
			ini_set( 'error_log', (string) $error_log ); // phpcs:ignore WordPress.PHP.IniSet.Risky
		}

		$this->assertSame( array( 'host' => 'ecommplan' ), get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_EVENT_PROPS_OPTION ) );
		$this->assertSame( $user_id, get_current_user_id() );
	}

	/**
	 * Ways an event props callback fails.
	 *
	 * @return array
	 */
	public static function provide_failing_event_props_callbacks(): array {
		return array(
			'a throw'           => array(
				function () {
					throw new \RuntimeException( 'No session in cron.' );
				},
			),
			'a non-array value' => array( '__return_null' ),
		);
	}

	/**
	 * With ALTERNATE_WP_CRON, cron runs inside a visitor's request, so that visitor's cookie must not be recorded.
	 *
	 * The constant cannot be undefined, so this runs in its own process.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_alternate_wp_cron_does_not_record(): void {
		define( 'ALTERNATE_WP_CRON', true );
		add_filter( 'wp_doing_cron', '__return_true' );
		update_option( Woocommerce_Analytics::PROXY_TRACKING_ENABLED_OPTION, 'yes' );
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_EVENT_PROPS_OPTION, array( 'host' => 'ecommplan' ) );
		$_COOKIE['visitor'] = 'shopper-a';
		add_filter(
			'jetpack_woocommerce_analytics_event_props',
			function ( $props ) {
				return array_merge( $props, array( 'visitor' => $_COOKIE['visitor'] ?? '' ) );
			}
		);

		Woocommerce_Analytics::record_speed_module_event_props();

		$this->assertSame( array( 'host' => 'ecommplan' ), get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_EVENT_PROPS_OPTION ) );
	}

	/**
	 * Test that the hourly record is scheduled while proxy tracking is on, and cleared with its row once it is off.
	 */
	public function test_sync_schedules_the_event_props_record_while_proxy_tracking_is_on(): void {
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
		Woocommerce_Analytics::sync_proxy_tracking_state();

		$this->assertSame( 'hourly', wp_get_schedule( Woocommerce_Analytics::PROXY_SPEED_MODULE_EVENT_PROPS_HOOK ) );

		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_EVENT_PROPS_OPTION, array( 'host' => 'ecommplan' ) );
		remove_all_filters( 'woocommerce_analytics_experimental_proxy_tracking_enabled' );
		Woocommerce_Analytics::sync_proxy_tracking_state();

		$this->assertFalse( wp_next_scheduled( Woocommerce_Analytics::PROXY_SPEED_MODULE_EVENT_PROPS_HOOK ) );
		$this->assertFalse( get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_EVENT_PROPS_OPTION ) );
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
