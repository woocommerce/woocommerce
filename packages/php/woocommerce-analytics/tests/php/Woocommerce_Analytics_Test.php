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
 * Focuses on the proxy tracking state and the speed module's authorization.
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
		$this->set_speed_module_enabled( false );
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
		$this->set_speed_module_enabled( false );

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
	 * A file an earlier version installed runs stale code, so the daily check removes it.
	 */
	public function test_maybe_update_proxy_speed_module_removes_a_leftover_module(): void {
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
		$this->set_speed_module_enabled( true );
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION, '0.18.1' );

		Woocommerce_Analytics::maybe_update_proxy_speed_module();

		$this->assertFalse( get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION ) );
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
		$this->set_speed_module_enabled( true );
		Woocommerce_Analytics::sync_proxy_tracking_state();

		$this->set_speed_module_enabled( false );
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
	 * MU-plugins load whether or not the plugin carrying this package is active,
	 * so a module file that outlives a deactivation must not be left holding a
	 * stale `yes`. The sticky option survives: cached pages outlive both.
	 */
	public function test_removing_the_module_drops_its_authorization(): void {
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
		$this->set_speed_module_enabled( true );
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
		$this->set_speed_module_enabled( true );
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
		$this->set_speed_module_enabled( true );
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
		$this->set_speed_module_enabled( true );

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
	 * Code that calls Proxy_Speed_Module::enable() needs the module authorized without the file being written.
	 *
	 * @dataProvider provide_proxy_tracking_states
	 *
	 * @param bool        $proxy_tracking Whether proxy tracking is enabled.
	 * @param string|bool $expected       The expected authorization option.
	 */
	#[DataProvider( 'provide_proxy_tracking_states' )]
	public function test_an_enabled_module_is_authorized_but_never_installed( $proxy_tracking, $expected ): void {
		$this->set_speed_module_enabled( true );
		if ( $proxy_tracking ) {
			add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
		}

		Woocommerce_Analytics::sync_proxy_tracking_state();
		Woocommerce_Analytics::maybe_update_proxy_speed_module();

		$this->assertSame( $expected, get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION ) );
		$this->assertFalse(
			get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_VERSION_OPTION ),
			'The package never writes the MU-plugin file.'
		);
	}

	/**
	 * Proxy tracking states for an enabled module.
	 *
	 * @return array
	 */
	public static function provide_proxy_tracking_states(): array {
		return array(
			'proxy tracking on'  => array( true, 'yes' ),
			'proxy tracking off' => array( false, false ),
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

	/**
	 * Set what Proxy_Speed_Module::enable() records, which tests cannot call once plugins have loaded.
	 *
	 * @param bool $enabled Whether the module is enabled.
	 */
	private function set_speed_module_enabled( bool $enabled ): void {
		$property = ( new \ReflectionClass( Proxy_Speed_Module::class ) )->getProperty( 'enabled' );
		$property->setAccessible( true );
		$property->setValue( null, $enabled );
	}
}
