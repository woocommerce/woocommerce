<?php
/**
 * WooPaymentsCutoverController tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCutoverController;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPayments native cutover controller.
 */
class WooPaymentsCutoverControllerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsCutoverController
	 */
	private WooPaymentsCutoverController $sut;

	/**
	 * Whether the WooPayments plugin should appear active.
	 *
	 * @var bool
	 */
	private bool $plugin_active = false;

	/**
	 * Whether the WooPayments plugin should appear network active.
	 *
	 * @var bool
	 */
	private bool $plugin_network_active = false;

	/**
	 * Whether the current user can perform cutover actions.
	 *
	 * @var bool
	 */
	private bool $current_user_can_cutover = false;

	/**
	 * Deactivate plugin calls recorded by the legacy proxy mock.
	 *
	 * @var array<int,array{0:string,1:bool,2:bool}>
	 */
	private array $deactivate_plugin_calls = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( WooPaymentsCutoverController::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		unset( $_GET[ WooPaymentsCutoverController::QUERY_ACTION ], $_GET[ WooPaymentsCutoverController::NONCE_NAME ] );

		remove_all_filters( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED );
		remove_all_filters( WooPaymentsCutoverController::FILTER_NATIVE_TRANSPORT_READY );
		remove_all_filters( WooPaymentsCutoverController::FILTER_SOFT_CUTOVER_ENABLED );
		remove_all_filters( WooPaymentsCutoverController::FILTER_MANDATORY_CUTOVER_ENABLED );
		remove_all_filters( WooPaymentsCutoverController::FILTER_PREFLIGHT_FAILURES );
		remove_all_filters( 'wp_die_handler' );
		$this->reset_legacy_proxy_mocks();

		parent::tearDown();
	}

	/**
	 * @testdox Soft cutover notice is hidden until native cutover preflight is ready.
	 */
	public function test_soft_notice_is_hidden_until_preflight_is_ready(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		add_filter( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED, '__return_true' );

		$this->assertFalse(
			$this->sut->should_show_soft_cutover_notice(),
			'The notice must not invite disabling WooPayments while native transport readiness is false.'
		);
	}

	/**
	 * @testdox Soft cutover notice is shown when plugin runtime owns the site and preflight is ready.
	 */
	public function test_soft_notice_is_shown_when_preflight_is_ready(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		$this->enable_ready_cutover();

		$this->assertTrue(
			$this->sut->should_show_soft_cutover_notice(),
			'The notice should show only when the merchant can safely disable the plugin.'
		);
	}

	/**
	 * @testdox Disable action deactivates the WooPayments plugin after capability and preflight pass.
	 */
	public function test_disable_action_deactivates_plugin_after_guards_pass(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		$this->enable_ready_cutover();

		$result = $this->sut->disable_woopayments_plugin();

		$this->assertTrue( $result, 'The disable action should report success after the plugin is deactivated.' );
		$this->assertSame(
			array( NativePaymentsRuntimeArbiter::PLUGIN_FILE, false, false ),
			$this->deactivate_plugin_calls[0],
			'The soft cutover should deactivate the per-site WooPayments plugin.'
		);
		$this->assertFalse( $this->plugin_active, 'The plugin active signal should be removed after deactivation.' );
	}

	/**
	 * @testdox Disable action refuses to deactivate WooPayments when cutover preflight fails.
	 */
	public function test_disable_action_refuses_when_preflight_fails(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		add_filter( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED, '__return_true' );

		$result = $this->sut->disable_woopayments_plugin();

		$this->assertFalse( $result, 'Cutover must fail closed while native transport readiness is false.' );
		$this->assertSame( array(), $this->deactivate_plugin_calls, 'The plugin must not be deactivated on failed preflight.' );
	}

	/**
	 * @testdox Mandatory activation guard blocks WooPayments reactivation when mandatory cutover is enabled.
	 */
	public function test_mandatory_activation_guard_blocks_reactivation_when_enabled(): void {
		$this->fake_wp_die_handler();
		$this->enable_ready_cutover();
		add_filter( WooPaymentsCutoverController::FILTER_MANDATORY_CUTOVER_ENABLED, '__return_true' );

		$this->expectException( WooPaymentsCutoverBlockedException::class );

		$this->sut->guard_woopayments_activation( NativePaymentsRuntimeArbiter::PLUGIN_FILE );
	}

	/**
	 * @testdox Mandatory activation guard stays open when cutover preflight is not ready.
	 */
	public function test_mandatory_activation_guard_stays_open_when_preflight_is_not_ready(): void {
		$this->fake_wp_die_handler();
		add_filter( WooPaymentsCutoverController::FILTER_MANDATORY_CUTOVER_ENABLED, '__return_true' );

		$this->sut->guard_woopayments_activation( NativePaymentsRuntimeArbiter::PLUGIN_FILE );

		$this->assertTrue( true, 'WooPayments reactivation must not be blocked while native transport readiness is false.' );
	}

	/**
	 * @testdox Mandatory activation guard ignores other plugins.
	 */
	public function test_mandatory_activation_guard_ignores_other_plugins(): void {
		$this->fake_wp_die_handler();
		add_filter( WooPaymentsCutoverController::FILTER_MANDATORY_CUTOVER_ENABLED, '__return_true' );

		$this->sut->guard_woopayments_activation( 'other-plugin/other-plugin.php' );

		$this->assertTrue( true, 'Other plugins should not be blocked by the WooPayments cutover guard.' );
	}

	/**
	 * @testdox Network-active WooPayments is deactivated network-wide.
	 */
	public function test_network_active_woopayments_deactivates_network_wide(): void {
		$this->fake_plugin_active( false, true );
		$this->fake_current_user_caps( true );
		$this->enable_ready_cutover();

		$result = $this->sut->disable_woopayments_plugin();

		$this->assertTrue( $result, 'Network-active WooPayments should be disabled when the user can manage network plugins.' );
		$this->assertSame(
			array( NativePaymentsRuntimeArbiter::PLUGIN_FILE, false, true ),
			$this->deactivate_plugin_calls[0],
			'Network-active WooPayments must be deactivated with the network-wide flag.'
		);
	}

	/**
	 * Make the native cutover preflight ready.
	 */
	private function enable_ready_cutover(): void {
		add_filter( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED, '__return_true' );
		add_filter( WooPaymentsCutoverController::FILTER_NATIVE_TRANSPORT_READY, '__return_true' );
	}

	/**
	 * Control the WooPayments plugin active signals.
	 *
	 * @param bool $site_active    Whether the plugin is active for this site.
	 * @param bool $network_active Whether the plugin is active network-wide.
	 */
	private function fake_plugin_active( bool $site_active = true, bool $network_active = false ): void {
		$this->plugin_active         = $site_active;
		$this->plugin_network_active = $network_active;
		$entry                       = NativePaymentsRuntimeArbiter::PLUGIN_FILE;

		$this->register_legacy_proxy_function_mocks(
			array(
				'get_option'         => function ( $name, $default_value = false ) use ( $entry ) {
					if ( 'active_plugins' === $name ) {
						return $this->plugin_active ? array( $entry ) : array();
					}
					return get_option( $name, $default_value );
				},
				'get_site_option'    => function ( $name, $default_value = false ) use ( $entry ) {
					if ( 'active_sitewide_plugins' === $name ) {
						return $this->plugin_network_active ? array( $entry => 1234567890 ) : array();
					}
					return get_site_option( $name, $default_value );
				},
				'class_exists'       => function ( $class_name, $autoload = true ) {
					if ( 'WC_Payments' === ltrim( (string) $class_name, '\\' ) ) {
						return $this->plugin_active || $this->plugin_network_active;
					}
					return class_exists( $class_name, $autoload );
				},
				'deactivate_plugins' => function ( $plugin, $silent = false, $network_wide = null ) {
					$network_wide                    = (bool) $network_wide;
					$this->deactivate_plugin_calls[] = array( (string) $plugin, (bool) $silent, $network_wide );

					if ( $network_wide ) {
						$this->plugin_network_active = false;
					} else {
						$this->plugin_active = false;
					}
				},
				'current_user_can'   => fn( $capability ) => in_array( $capability, array( 'manage_woocommerce', 'activate_plugins', 'manage_network_plugins' ), true )
					? $this->current_user_can_cutover
					: current_user_can( $capability ),
			)
		);
	}

	/**
	 * Control current-user cutover capabilities.
	 *
	 * @param bool $can_cutover Whether the user can perform cutover actions.
	 */
	private function fake_current_user_caps( bool $can_cutover ): void {
		$this->current_user_can_cutover = $can_cutover;
	}

	/**
	 * Replace wp_die with a test exception.
	 */
	private function fake_wp_die_handler(): void {
		add_filter(
			'wp_die_handler',
			static function () {
				return static function ( $message = '' ): void {
					throw new WooPaymentsCutoverBlockedException( esc_html( wp_strip_all_tags( (string) $message ) ) );
				};
			}
		);
	}
}
