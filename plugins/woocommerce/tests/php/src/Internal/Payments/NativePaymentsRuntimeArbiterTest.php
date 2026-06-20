<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use WC_Unit_Test_Case;

/**
 * Tests for the NativePaymentsRuntimeArbiter class.
 *
 * The mutual-exclusion invariant: exactly one payments runtime (the WooPayments plugin or
 * core-native) owns a site, and the plugin wins whenever it is active.
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
		remove_all_filters( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED );
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
	 * @testdox The plugin is detected via the class_exists fallback for non-standard installs.
	 */
	public function test_plugin_detected_via_class_exists_fallback(): void {
		$this->fake_plugin( false, false, true );
		$this->enable_native_runtime();

		$this->assertSame( NativePaymentsRuntimeArbiter::OWNER_PLUGIN, $this->sut->get_runtime_owner(), 'A loaded bootstrap class is detected even without a standard active-plugins entry.' );
		$this->assertFalse( $this->sut->should_native_register(), 'Native must not register when the plugin is detected by the fallback signal.' );
	}

	/**
	 * @testdox Plugin wins even when the native runtime is enabled.
	 */
	public function test_plugin_wins_even_when_native_enabled(): void {
		$this->fake_plugin( true );
		$this->enable_native_runtime();

		$this->assertSame( NativePaymentsRuntimeArbiter::OWNER_PLUGIN, $this->sut->get_runtime_owner(), 'Plugin-wins is the only allowed state while the plugin is active.' );
		$this->assertFalse( $this->sut->should_native_register(), 'Native must not register even when enabled, as long as the plugin is active.' );
	}

	/**
	 * @testdox A network-activated plugin wins even when native is enabled.
	 */
	public function test_network_active_plugin_wins_when_native_enabled(): void {
		$this->fake_plugin( false, true );
		$this->enable_native_runtime();

		$this->assertSame( NativePaymentsRuntimeArbiter::OWNER_PLUGIN, $this->sut->get_runtime_owner(), 'Network-active detection must keep plugin-wins even when native is enabled.' );
		$this->assertFalse( $this->sut->should_native_register(), 'Native must not register on a network-activated-plugin site.' );
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
	 * @testdox is_native_runtime_enabled reflects the feature flag independently of ownership.
	 */
	public function test_is_native_runtime_enabled_reflects_flag(): void {
		$this->fake_plugin( true );

		$this->assertFalse( $this->sut->is_native_runtime_enabled(), 'The native flag is off by default.' );

		$this->enable_native_runtime();

		$this->assertTrue( $this->sut->is_native_runtime_enabled(), 'The native flag reports enabled even while the plugin still owns the runtime.' );
	}

	/**
	 * @testdox Native runtime rollout default is fail-closed.
	 */
	public function test_native_runtime_rollout_default_is_fail_closed(): void {
		$this->fake_plugin();

		$this->assertFalse( NativePaymentsRuntimeArbiter::DEFAULT_NATIVE_RUNTIME_ENABLED );
		$this->assertFalse( $this->sut->is_native_runtime_enabled(), 'Native runtime must stay default-off until the release rollout default is explicitly flipped.' );
		$this->assertSame( NativePaymentsRuntimeArbiter::OWNER_NONE, $this->sut->get_runtime_owner(), 'With no plugin and default-off native runtime, nobody owns the runtime.' );
	}

	/**
	 * @testdox Native runtime rollout filter receives the fail-closed default and can enable native.
	 */
	public function test_native_runtime_rollout_filter_receives_default_and_can_enable(): void {
		$this->fake_plugin();
		$observed_default = null;
		add_filter(
			NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED,
			static function ( bool $enabled ) use ( &$observed_default ): bool {
				$observed_default = $enabled;
				return true;
			}
		);

		$this->assertTrue( $this->sut->is_native_runtime_enabled(), 'The rollout filter should still be able to enable native runtime for controlled gates.' );
		$this->assertSame( NativePaymentsRuntimeArbiter::DEFAULT_NATIVE_RUNTIME_ENABLED, $observed_default, 'The rollout filter should receive the explicit default value.' );
		$this->assertSame( NativePaymentsRuntimeArbiter::OWNER_NATIVE, $this->sut->get_runtime_owner(), 'Native should own the runtime when the plugin is absent and the rollout filter enables native.' );
	}
}
