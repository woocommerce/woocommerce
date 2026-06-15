<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use WC_Unit_Test_Case;

/**
 * Tests for the NativePaymentsRuntimeArbiter class.
 *
 * These are the §4.5 mutual-exclusion invariants: exactly one payments runtime
 * (the WooPayments plugin or core-native) may own a site, and the plugin wins
 * whenever it is active — unless it explicitly yields through the stand-down handshake.
 */
class NativePaymentsRuntimeArbiterTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( NativePaymentsRuntimeArbiter::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( NativePaymentsRuntimeArbiter::FILTER_RUNTIME_OWNER );
		remove_all_filters( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED );
		remove_all_filters( NativePaymentsRuntimeArbiter::FILTER_PLUGIN_YIELDED );
		$this->reset_legacy_proxy_mocks();
		parent::tearDown();
	}

	/**
	 * Control every WooPayments-plugin detection signal in a single mock registration.
	 *
	 * All three are mocked together so an "absent" plugin is absent on every signal — the
	 * real test process may have WC_Payments loaded, which would otherwise trip the fallback.
	 *
	 * @param bool $in_list      Whether the plugin is in the per-site active-plugins list.
	 * @param bool $network      Whether the plugin is in the network active-sitewide-plugins list.
	 * @param bool $class_loaded Whether the WC_Payments bootstrap class is loaded (the fallback signal).
	 */
	private function fake_plugin( bool $in_list = false, bool $network = false, bool $class_loaded = false ): void {
		$entry = NativePaymentsRuntimeArbiter::PLUGIN_FILE;
		$this->register_legacy_proxy_function_mocks(
			array(
				'get_option'      => function ( $name, $default_value = false ) use ( $in_list, $entry ) {
					if ( 'active_plugins' === $name ) {
						return $in_list ? array( $entry ) : array();
					}
					return get_option( $name, $default_value );
				},
				'get_site_option' => function ( $name, $default_value = false ) use ( $network, $entry ) {
					if ( 'active_sitewide_plugins' === $name ) {
						return $network ? array( $entry => 1234567890 ) : array();
					}
					return get_site_option( $name, $default_value );
				},
				'class_exists'    => function ( $class_name, $autoload = true ) use ( $class_loaded ) {
					if ( 'WC_Payments' === ltrim( (string) $class_name, '\\' ) ) {
						return $class_loaded;
					}
					return class_exists( $class_name, $autoload );
				},
			)
		);
	}

	/**
	 * Enable the native runtime feature flag.
	 */
	private function enable_native_runtime(): void {
		add_filter( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED, '__return_true' );
	}

	/**
	 * Make the plugin yield the runtime to native.
	 */
	private function plugin_yields(): void {
		add_filter( NativePaymentsRuntimeArbiter::FILTER_PLUGIN_YIELDED, '__return_true' );
	}

	/**
	 * @testdox Owner is none when the plugin is absent and native is disabled.
	 */
	public function test_owner_is_none_when_plugin_absent_and_native_disabled(): void {
		$this->fake_plugin();

		$this->assertSame( NativePaymentsRuntimeArbiter::OWNER_NONE, $this->sut->get_runtime_owner(), 'With no plugin and native off, nobody owns the runtime.' );
		$this->assertFalse( $this->sut->should_native_register(), 'Native must not register when it does not own the runtime.' );
		$this->assertFalse( $this->sut->is_plugin_runtime_active(), 'The plugin does not own the runtime when it is absent.' );
	}

	/**
	 * @testdox Owner is the plugin when the plugin is active in the per-site list.
	 */
	public function test_owner_is_plugin_when_plugin_active(): void {
		$this->fake_plugin( true );

		$this->assertSame( NativePaymentsRuntimeArbiter::OWNER_PLUGIN, $this->sut->get_runtime_owner(), 'An active plugin owns the runtime.' );
		$this->assertTrue( $this->sut->is_plugin_runtime_active(), 'The plugin owns the runtime when active.' );
		$this->assertFalse( $this->sut->should_native_register(), 'Native must register nothing while the plugin owns the runtime.' );
	}

	/**
	 * @testdox Owner is the plugin when the plugin is network-activated.
	 */
	public function test_owner_is_plugin_when_network_active(): void {
		$this->fake_plugin( false, true );

		$this->assertSame( NativePaymentsRuntimeArbiter::OWNER_PLUGIN, $this->sut->get_runtime_owner(), 'A network-activated plugin owns the runtime.' );
		$this->assertTrue( $this->sut->is_plugin_runtime_active(), 'A network-activated plugin owns the runtime.' );
		$this->assertFalse( $this->sut->should_native_register(), 'Native must not register while a network-activated plugin owns the runtime.' );
	}

	/**
	 * @testdox A network-activated plugin wins even when native is enabled (the network-detection fence).
	 */
	public function test_network_active_plugin_wins_when_native_enabled(): void {
		$this->fake_plugin( false, true );
		$this->enable_native_runtime();

		$this->assertSame( NativePaymentsRuntimeArbiter::OWNER_PLUGIN, $this->sut->get_runtime_owner(), 'Network-active detection must keep plugin-wins even when native is enabled.' );
		$this->assertFalse( $this->sut->should_native_register(), 'Native must not register on a network-activated-plugin site.' );
	}

	/**
	 * @testdox The plugin is detected via the class_exists fallback for non-standard installs.
	 */
	public function test_plugin_detected_via_class_exists_fallback(): void {
		$this->fake_plugin( false, false, true );
		$this->enable_native_runtime();

		$this->assertSame( NativePaymentsRuntimeArbiter::OWNER_PLUGIN, $this->sut->get_runtime_owner(), 'A loaded bootstrap class is detected even without a standard active-plugins entry.' );
		$this->assertFalse( $this->sut->should_native_register(), 'Native must not register when the plugin is detected by the fallback signal.' );
	}

	/**
	 * @testdox Plugin wins even when the native runtime is enabled (the core §4.5 rule).
	 */
	public function test_plugin_wins_even_when_native_enabled(): void {
		$this->fake_plugin( true );
		$this->enable_native_runtime();

		$this->assertSame( NativePaymentsRuntimeArbiter::OWNER_PLUGIN, $this->sut->get_runtime_owner(), 'Plugin-wins is the only allowed state while the plugin is active.' );
		$this->assertFalse( $this->sut->should_native_register(), 'Native must not register even when enabled, as long as the plugin is active.' );
	}

	/**
	 * @testdox Owner is native when the plugin is absent and native is enabled.
	 */
	public function test_owner_is_native_when_plugin_absent_and_native_enabled(): void {
		$this->fake_plugin();
		$this->enable_native_runtime();

		$this->assertSame( NativePaymentsRuntimeArbiter::OWNER_NATIVE, $this->sut->get_runtime_owner(), 'Native owns the runtime when the plugin is gone and native is enabled.' );
		$this->assertTrue( $this->sut->should_native_register(), 'Native must register when it owns the runtime.' );
		$this->assertFalse( $this->sut->is_plugin_runtime_active(), 'The plugin does not own the runtime when absent.' );
	}

	/**
	 * @testdox The yield handshake lets native take over a still-present plugin.
	 */
	public function test_yield_handshake_lets_native_take_over_present_plugin(): void {
		$this->fake_plugin( true );
		$this->plugin_yields();
		$this->enable_native_runtime();

		$this->assertSame( NativePaymentsRuntimeArbiter::OWNER_NATIVE, $this->sut->get_runtime_owner(), 'Once the plugin yields, native may own the runtime even while the plugin is still present.' );
		$this->assertTrue( $this->sut->should_native_register(), 'Native registers after the plugin yields and native is enabled.' );
	}

	/**
	 * @testdox A yielded plugin with native disabled leaves nobody owning the runtime.
	 */
	public function test_yielded_plugin_with_native_disabled_owns_nothing(): void {
		$this->fake_plugin( true );
		$this->plugin_yields();

		$this->assertSame( NativePaymentsRuntimeArbiter::OWNER_NONE, $this->sut->get_runtime_owner(), 'A yielded plugin no longer owns the runtime; with native off nobody does.' );
		$this->assertFalse( $this->sut->is_plugin_runtime_active(), 'A yielded plugin is not running its runtime.' );
		$this->assertFalse( $this->sut->should_native_register(), 'Native does not register while disabled, even after the plugin yields.' );
	}

	/**
	 * @testdox The runtime-owner filter cannot promote native over a present, non-yielded plugin.
	 */
	public function test_runtime_owner_filter_cannot_promote_native_over_present_plugin(): void {
		$this->fake_plugin( true );
		$this->enable_native_runtime();
		add_filter(
			NativePaymentsRuntimeArbiter::FILTER_RUNTIME_OWNER,
			function () {
				return NativePaymentsRuntimeArbiter::OWNER_NATIVE;
			}
		);

		$this->assertSame( NativePaymentsRuntimeArbiter::OWNER_PLUGIN, $this->sut->get_runtime_owner(), 'A stray filter must not be able to force native over an active plugin (the dual-runtime direction is fenced).' );
		$this->assertFalse( $this->sut->should_native_register(), 'Native must not register on a forced-native value while the plugin is active and has not yielded.' );
	}

	/**
	 * @testdox The runtime-owner filter can force plugin-wins when the plugin is present.
	 */
	public function test_runtime_owner_filter_can_force_plugin_when_present(): void {
		$this->fake_plugin( true );
		$this->plugin_yields();
		$this->enable_native_runtime();
		add_filter(
			NativePaymentsRuntimeArbiter::FILTER_RUNTIME_OWNER,
			function () {
				return NativePaymentsRuntimeArbiter::OWNER_PLUGIN;
			}
		);

		$this->assertSame( NativePaymentsRuntimeArbiter::OWNER_PLUGIN, $this->sut->get_runtime_owner(), 'Forcing plugin-wins overrides a yield (the conservative escape hatch).' );
	}

	/**
	 * @testdox A forced plugin-wins value is dropped when the plugin is absent (falls through to native).
	 */
	public function test_forced_plugin_owner_is_dropped_when_plugin_absent(): void {
		$this->fake_plugin();
		$this->enable_native_runtime();
		add_filter(
			NativePaymentsRuntimeArbiter::FILTER_RUNTIME_OWNER,
			function () {
				return NativePaymentsRuntimeArbiter::OWNER_PLUGIN;
			}
		);

		$this->assertSame( NativePaymentsRuntimeArbiter::OWNER_NATIVE, $this->sut->get_runtime_owner(), 'Forcing plugin-wins must be ignored when no plugin is present; native owns.' );
		$this->assertTrue( $this->sut->should_native_register(), 'Native registers when forced plugin-wins is dropped and native is enabled.' );
	}

	/**
	 * @testdox The runtime-owner filter none value is a global kill switch.
	 */
	public function test_runtime_owner_filter_none_is_kill_switch(): void {
		$this->fake_plugin();
		$this->enable_native_runtime();
		add_filter(
			NativePaymentsRuntimeArbiter::FILTER_RUNTIME_OWNER,
			function () {
				return NativePaymentsRuntimeArbiter::OWNER_NONE;
			}
		);

		$this->assertSame( NativePaymentsRuntimeArbiter::OWNER_NONE, $this->sut->get_runtime_owner(), 'Forcing none stands every runtime down regardless of state.' );
		$this->assertFalse( $this->sut->should_native_register(), 'Native must not register when the kill switch is on.' );
	}

	/**
	 * @testdox An invalid runtime-owner filter value is ignored.
	 */
	public function test_invalid_runtime_owner_filter_value_is_ignored(): void {
		$this->fake_plugin();
		$this->enable_native_runtime();
		add_filter(
			NativePaymentsRuntimeArbiter::FILTER_RUNTIME_OWNER,
			function () {
				return 'bogus-value';
			}
		);

		$this->assertSame( NativePaymentsRuntimeArbiter::OWNER_NATIVE, $this->sut->get_runtime_owner(), 'A bogus override is ignored and the computed owner stands.' );
	}

	/**
	 * @testdox is_native_runtime_enabled reflects the feature flag independently of ownership.
	 */
	public function test_is_native_runtime_enabled_reflects_flag(): void {
		$this->fake_plugin( true );

		$this->assertFalse( $this->sut->is_native_runtime_enabled(), 'The native flag is off by default.' );

		$this->enable_native_runtime();

		$this->assertTrue( $this->sut->is_native_runtime_enabled(), 'The native flag reports enabled even while the plugin still owns the runtime.' );
	}
}
