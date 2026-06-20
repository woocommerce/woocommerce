<?php
/**
 * WooPaymentsCutoverController tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Subscriptions\WooPaymentsLegacySubscriptionsGuard;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCanceledAuthorizationFeeRemediationService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCutoverController;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsPlatformConnectionService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsProvider;
use Automattic\WooCommerce\Proxies\LegacyProxy;
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
	 * Native WooPayments provider mock.
	 *
	 * @var WooPaymentsProvider&\PHPUnit\Framework\MockObject\MockObject
	 */
	private WooPaymentsProvider $provider;

	/**
	 * Canceled-authorization fee remediation service mock.
	 *
	 * @var WooPaymentsCanceledAuthorizationFeeRemediationService&\PHPUnit\Framework\MockObject\MockObject
	 */
	private WooPaymentsCanceledAuthorizationFeeRemediationService $fee_remediation_service;

	/**
	 * Platform connection readiness service mock.
	 *
	 * @var WooPaymentsPlatformConnectionService&\PHPUnit\Framework\MockObject\MockObject
	 */
	private WooPaymentsPlatformConnectionService $platform_connection_service;

	/**
	 * Whether the native provider can process payments.
	 *
	 * @var bool
	 */
	private bool $native_provider_ready = false;

	/**
	 * Whether canceled-authorization fee remediation can be scheduled during cutover.
	 *
	 * @var bool
	 */
	private bool $fee_remediation_schedulable = true;

	/**
	 * Whether canceled-authorization fee remediation scheduling succeeds after deactivation.
	 *
	 * @var string
	 */
	private string $fee_remediation_schedule_result = 'scheduled';

	/**
	 * Platform connection preflight failures.
	 *
	 * @var string[]
	 */
	private array $platform_connection_failures = array();

	/**
	 * Number of times the cutover asked the remediation service to adopt the queue.
	 *
	 * @var int
	 */
	private int $fee_remediation_schedule_calls = 0;

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
	 * Whether the WooPayments plugin bootstrap class is loaded in this PHP request.
	 *
	 * @var bool
	 */
	private bool $plugin_class_loaded = false;

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
	 * Whether this test registered the subscription order type.
	 *
	 * @var bool
	 */
	private bool $registered_subscription_order_type = false;

	/**
	 * Raw HPOS order rows created by tests.
	 *
	 * @var int[]
	 */
	private array $raw_hpos_order_ids = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->provider = $this->getMockBuilder( WooPaymentsProvider::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'can_process_payments' ) )
			->getMock();
		$this->provider
			->method( 'can_process_payments' )
			->willReturnCallback( fn() => $this->native_provider_ready );

		$this->fee_remediation_service = $this->getMockBuilder( WooPaymentsCanceledAuthorizationFeeRemediationService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'can_schedule_cutover_remediation', 'ensure_scheduled' ) )
			->getMock();
		$this->fee_remediation_service
			->method( 'can_schedule_cutover_remediation' )
			->willReturnCallback( fn() => $this->fee_remediation_schedulable );
		$this->fee_remediation_service
			->method( 'ensure_scheduled' )
			->willReturnCallback(
				function (): string {
					++$this->fee_remediation_schedule_calls;
					return $this->fee_remediation_schedule_result;
				}
			);

		$this->platform_connection_service = $this->getMockBuilder( WooPaymentsPlatformConnectionService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_cutover_preflight_failures' ) )
			->getMock();
		$this->platform_connection_service
			->method( 'get_cutover_preflight_failures' )
			->willReturnCallback( fn() => $this->platform_connection_failures );

		$this->sut = $this->create_cutover_controller();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		unset( $_GET[ WooPaymentsCutoverController::QUERY_ACTION ], $_GET[ WooPaymentsCutoverController::NONCE_NAME ], $_GET[ WooPaymentsCutoverController::QUERY_STATUS ] );
		delete_transient( 'woocommerce_woopayments_native_cutover_status' );

		remove_all_filters( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED );
		remove_all_filters( WooPaymentsCutoverController::FILTER_NATIVE_TRANSPORT_READY );
		remove_all_filters( WooPaymentsCutoverController::FILTER_NATIVE_ADMIN_SURFACES_READY );
		remove_all_filters( WooPaymentsCutoverController::FILTER_PROVIDER_EVENT_TYPES_PENDING_CUTOVER );
		remove_all_filters( WooPaymentsCutoverController::FILTER_OPERATIONAL_QUEUE_HOOKS_PENDING_CUTOVER );
		remove_all_filters( WooPaymentsCutoverController::FILTER_SOFT_CUTOVER_ENABLED );
		remove_all_filters( WooPaymentsCutoverController::FILTER_MANDATORY_CUTOVER_ENABLED );
		remove_all_filters( WooPaymentsCutoverController::FILTER_PREFLIGHT_FAILURES );
		remove_all_filters( 'wp_die_handler' );
		if ( $this->registered_subscription_order_type ) {
			global $wc_order_types;
			unset( $wc_order_types['shop_subscription'] );
			unregister_post_type( 'shop_subscription' );
			$this->registered_subscription_order_type = false;
		}
		$this->delete_raw_hpos_orders();
		Constants::clear_single_constant( 'WC_ALLOW_MERGED_FEATURE_PLUGINS' );
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
	 * @testdox Disable action asks native to adopt canceled-authorization fee remediation before deactivation.
	 */
	public function test_disable_action_schedules_fee_remediation_before_deactivation(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		$this->enable_ready_cutover();

		$this->assertTrue( $this->sut->disable_woopayments_plugin() );

		$this->assertSame( 1, $this->fee_remediation_schedule_calls, 'Cutover should adopt preserved financial remediation jobs before deactivation.' );
	}

	/**
	 * @testdox Disable action blocks deactivation when financial remediation cannot be adopted.
	 */
	public function test_disable_action_blocks_when_fee_remediation_adoption_fails(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		$this->enable_ready_cutover();
		$this->fee_remediation_schedule_result = 'unavailable';

		$result = $this->sut->disable_woopayments_plugin();

		$this->assertFalse( $result, 'Cutover must fail closed when financial remediation cannot be scheduled.' );
		$this->assertSame( 1, $this->fee_remediation_schedule_calls, 'Cutover should attempt to adopt financial remediation before blocking.' );
		$this->assertSame( array(), $this->deactivate_plugin_calls, 'The plugin must stay active when financial remediation cannot be adopted.' );
		$this->assertTrue( $this->plugin_active, 'WooPayments should continue owning runtime until financial remediation is safely queued.' );
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
	 * @testdox Mandatory activation guard remains default-off when native preflight is ready.
	 */
	public function test_mandatory_activation_guard_remains_default_off_when_preflight_is_ready(): void {
		$this->fake_wp_die_handler();
		$this->enable_ready_cutover();

		$this->sut->guard_woopayments_activation( NativePaymentsRuntimeArbiter::PLUGIN_FILE );

		$this->assertTrue( true, 'Mandatory cutover must still require an explicit rollout filter.' );
	}

	/**
	 * @testdox Mandatory cutover rollout default is fail-closed.
	 */
	public function test_mandatory_cutover_rollout_default_is_fail_closed(): void {
		$this->fake_wp_die_handler();
		$this->enable_ready_cutover();

		$this->assertFalse( WooPaymentsCutoverController::DEFAULT_MANDATORY_CUTOVER_ENABLED );
		$this->sut->guard_woopayments_activation( NativePaymentsRuntimeArbiter::PLUGIN_FILE );
		$this->assertTrue( true, 'Mandatory cutover remains fail-closed until the release rollout default is explicitly flipped.' );
	}

	/**
	 * @testdox Mandatory cutover rollout filter receives the fail-closed default and can enable mandatory cutover.
	 */
	public function test_mandatory_cutover_rollout_filter_receives_default_and_can_enable(): void {
		$this->fake_wp_die_handler();
		$this->enable_ready_cutover();
		$observed_default = null;
		add_filter(
			WooPaymentsCutoverController::FILTER_MANDATORY_CUTOVER_ENABLED,
			static function ( bool $enabled ) use ( &$observed_default ): bool {
				$observed_default = $enabled;
				return true;
			}
		);

		$this->expectException( WooPaymentsCutoverBlockedException::class );
		try {
			$this->sut->guard_woopayments_activation( NativePaymentsRuntimeArbiter::PLUGIN_FILE );
		} finally {
			$this->assertSame( WooPaymentsCutoverController::DEFAULT_MANDATORY_CUTOVER_ENABLED, $observed_default, 'The mandatory cutover filter should receive the explicit default value.' );
		}
	}

	/**
	 * @testdox Mandatory auto-deactivation does not require current user cutover capabilities.
	 */
	public function test_mandatory_auto_deactivation_does_not_require_current_user_cutover_capabilities(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( false );
		$this->enable_ready_cutover();
		add_filter( WooPaymentsCutoverController::FILTER_MANDATORY_CUTOVER_ENABLED, '__return_true' );

		$this->sut->handle_admin_init();

		$this->assertCount( 1, $this->deactivate_plugin_calls, 'Mandatory cutover should deactivate the plugin when native preflight is ready.' );
		$this->assertSame(
			array( NativePaymentsRuntimeArbiter::PLUGIN_FILE, false, false ),
			$this->deactivate_plugin_calls[0],
			'Mandatory cutover should deactivate the per-site WooPayments plugin when native preflight is ready.'
		);
		$this->assertFalse( $this->plugin_active, 'Mandatory cutover should remove the plugin active signal.' );
	}

	/**
	 * @testdox Mandatory auto-deactivation renders success notice after deactivation redirects.
	 */
	public function test_mandatory_auto_deactivation_renders_success_notice_after_deactivation_redirect(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( false );
		$this->enable_ready_cutover();
		add_filter( WooPaymentsCutoverController::FILTER_MANDATORY_CUTOVER_ENABLED, '__return_true' );

		$this->sut->handle_admin_init();
		unset( $_GET[ WooPaymentsCutoverController::QUERY_STATUS ] );
		$this->fake_woopayments_class_unloaded();

		$notice = $this->render_admin_notices( $this->create_cutover_controller() );

		$this->assertStringContainsString( 'WooPayments is now fully native in WooCommerce', $notice );
		$this->assertStringContainsString( 'Everything works as before', $notice );

		unset( $_GET[ WooPaymentsCutoverController::QUERY_STATUS ] );
		$this->fake_woopayments_class_unloaded();

		$next_request_notice = $this->render_admin_notices( $this->create_cutover_controller() );

		$this->assertStringNotContainsString( 'WooPayments is now fully native in WooCommerce', $next_request_notice );
	}

	/**
	 * @testdox Mandatory auto-deactivation renders same-request success while the plugin class remains loaded.
	 */
	public function test_mandatory_auto_deactivation_renders_same_request_success_when_plugin_class_remains_loaded(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( false );
		$this->enable_ready_cutover();
		add_filter( WooPaymentsCutoverController::FILTER_MANDATORY_CUTOVER_ENABLED, '__return_true' );

		$this->sut->handle_admin_init();

		$notice = $this->render_admin_notices( $this->sut );

		$this->assertStringContainsString( 'WooPayments is now fully native in WooCommerce', $notice );
		$this->assertStringContainsString( 'Everything works as before', $notice );

		unset( $_GET[ WooPaymentsCutoverController::QUERY_STATUS ] );
		$this->fake_woopayments_class_unloaded();

		$next_request_notice = $this->render_admin_notices( $this->create_cutover_controller() );

		$this->assertStringNotContainsString( 'WooPayments is now fully native in WooCommerce', $next_request_notice );
	}

	/**
	 * @testdox Stale disabled cutover query status renders blocked notice while the plugin runtime remains active.
	 */
	public function test_stale_disabled_query_status_renders_blocked_notice_when_plugin_runtime_remains_active(): void {
		$this->fake_plugin_active();
		$this->enable_ready_cutover();
		$_GET[ WooPaymentsCutoverController::QUERY_STATUS ] = WooPaymentsCutoverController::STATUS_DISABLED;

		$notice = $this->render_admin_notices( $this->sut );

		$this->assertStringContainsString( 'WooPayments could not be disabled because native WooPayments is not ready', $notice );
		$this->assertStringNotContainsString( 'WooPayments is now fully native in WooCommerce', $notice );
	}

	/**
	 * @testdox Stale disabled cutover transient renders blocked notice while the plugin runtime remains active.
	 */
	public function test_stale_disabled_transient_renders_blocked_notice_when_plugin_runtime_remains_active(): void {
		$this->fake_plugin_active();
		$this->enable_ready_cutover();
		set_transient( 'woocommerce_woopayments_native_cutover_status', WooPaymentsCutoverController::STATUS_DISABLED, 10 * MINUTE_IN_SECONDS );

		$notice = $this->render_admin_notices( $this->sut );

		$this->assertStringContainsString( 'WooPayments could not be disabled because native WooPayments is not ready', $notice );
		$this->assertStringNotContainsString( 'WooPayments is now fully native in WooCommerce', $notice );
	}

	/**
	 * @testdox Failed mandatory auto-deactivation clears stored success and renders blocked notice.
	 */
	public function test_failed_mandatory_auto_deactivation_clears_stored_success_and_renders_blocked_notice(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( false );
		$this->enable_ready_cutover();
		$this->fee_remediation_schedule_result = 'unavailable';
		add_filter( WooPaymentsCutoverController::FILTER_MANDATORY_CUTOVER_ENABLED, '__return_true' );

		$this->sut->handle_admin_init();
		unset( $_GET[ WooPaymentsCutoverController::QUERY_STATUS ] );

		$same_request_notice = $this->render_admin_notices( $this->sut );

		$this->fake_woopayments_class_unloaded();
		$next_request_notice = $this->render_admin_notices( $this->create_cutover_controller() );

		$this->assertStringContainsString( 'WooPayments could not be disabled because native WooPayments is not ready', $same_request_notice );
		$this->assertStringNotContainsString( 'WooPayments is now fully native in WooCommerce', $same_request_notice );
		$this->assertStringNotContainsString( 'WooPayments is now fully native in WooCommerce', $next_request_notice );
	}

	/**
	 * @testdox Mandatory auto-deactivation allows merged feature plugins when developer bypass is enabled.
	 */
	public function test_mandatory_auto_deactivation_allows_merged_feature_plugins_when_bypass_enabled(): void {
		$this->fake_plugin_active();
		$this->enable_ready_cutover();
		add_filter( WooPaymentsCutoverController::FILTER_MANDATORY_CUTOVER_ENABLED, '__return_true' );
		Constants::set_constant( 'WC_ALLOW_MERGED_FEATURE_PLUGINS', true );

		$this->sut->handle_admin_init();

		$this->assertSame( array(), $this->deactivate_plugin_calls, 'Developer bypass should preserve an active standalone plugin for parallel testing.' );
		$this->assertTrue( $this->plugin_active, 'Developer bypass should leave WooPayments active.' );
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
	 * @testdox Transport readiness filter can still block cutover when the native provider is ready.
	 */
	public function test_transport_filter_can_block_provider_backed_preflight(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		$this->enable_ready_cutover();
		add_filter( WooPaymentsCutoverController::FILTER_NATIVE_TRANSPORT_READY, '__return_false' );

		$this->assertFalse( $this->sut->should_show_soft_cutover_notice() );
		$this->assertContains( 'native_transport_unavailable', $this->sut->get_preflight_failures() );
	}

	/**
	 * @testdox Cutover preflight blocks while platform connection owner user-token readiness is unavailable.
	 */
	public function test_preflight_blocks_when_platform_connection_user_token_is_unavailable(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		$this->enable_ready_cutover();
		$this->platform_connection_failures = array( 'wpcom_connection_owner_user_token_unavailable' );

		$this->assertContains( 'wpcom_connection_owner_user_token_unavailable', $this->sut->get_preflight_failures() );
		$this->assertFalse( $this->sut->should_show_soft_cutover_notice() );
		$this->assertFalse( $this->sut->disable_woopayments_plugin() );
		$this->assertSame( array(), $this->deactivate_plugin_calls, 'The plugin must stay active when owner user-token readiness is unavailable.' );
	}

	/**
	 * @testdox Cutover preflight filters cannot remove platform connection blockers.
	 */
	public function test_preflight_filter_cannot_remove_platform_connection_blocker(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		$this->enable_ready_cutover();
		$this->platform_connection_failures = array( 'wpcom_blog_id_unavailable' );
		add_filter( WooPaymentsCutoverController::FILTER_PREFLIGHT_FAILURES, '__return_empty_array' );

		$this->assertContains( 'wpcom_blog_id_unavailable', $this->sut->get_preflight_failures() );
		$this->assertFalse( $this->sut->should_show_soft_cutover_notice() );
		$this->assertFalse( $this->sut->disable_woopayments_plugin() );
	}

	/**
	 * @testdox Cutover preflight blocks while native admin surfaces are unavailable.
	 */
	public function test_preflight_blocks_when_native_admin_surfaces_are_unavailable(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		add_filter( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED, '__return_true' );
		add_filter( WooPaymentsCutoverController::FILTER_NATIVE_ADMIN_SURFACES_READY, '__return_false' );
		add_filter( WooPaymentsCutoverController::FILTER_PROVIDER_EVENT_TYPES_PENDING_CUTOVER, '__return_empty_array' );
		$this->native_provider_ready = true;

		$this->assertContains( 'native_admin_surfaces_unavailable', $this->sut->get_preflight_failures() );
		$this->assertFalse( $this->sut->should_show_soft_cutover_notice() );
	}

	/**
	 * @testdox Cutover preflight defaults admin surfaces to ready after the N12 parity gate passes.
	 */
	public function test_preflight_defaults_admin_surfaces_ready_after_n12_parity_gate_passes(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		add_filter( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED, '__return_true' );
		add_filter( WooPaymentsCutoverController::FILTER_PROVIDER_EVENT_TYPES_PENDING_CUTOVER, '__return_empty_array' );
		add_filter( WooPaymentsCutoverController::FILTER_OPERATIONAL_QUEUE_HOOKS_PENDING_CUTOVER, '__return_empty_array' );
		$this->native_provider_ready = true;

		$failures = $this->sut->get_preflight_failures();

		$this->assertNotContains( 'native_admin_surfaces_unavailable', $failures );
		$this->assertTrue( $this->sut->should_show_soft_cutover_notice() );
	}

	/**
	 * @testdox Cutover preflight blocks while provider event types remain undispositioned.
	 */
	public function test_preflight_blocks_when_provider_events_are_undispositioned(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		add_filter( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED, '__return_true' );
		add_filter( WooPaymentsCutoverController::FILTER_NATIVE_ADMIN_SURFACES_READY, '__return_true' );
		add_filter( WooPaymentsCutoverController::FILTER_PROVIDER_EVENT_TYPES_PENDING_CUTOVER, static fn() => array( 'example.event' ) );
		add_filter( WooPaymentsCutoverController::FILTER_OPERATIONAL_QUEUE_HOOKS_PENDING_CUTOVER, '__return_empty_array' );
		$this->native_provider_ready = true;

		$this->assertContains( 'provider_events_undispositioned', $this->sut->get_preflight_failures() );
		$this->assertFalse( $this->sut->should_show_soft_cutover_notice() );
	}

	/**
	 * @testdox Cutover preflight blocks while operational queue hooks remain undispositioned.
	 */
	public function test_preflight_blocks_when_operational_queue_hooks_are_undispositioned(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		add_filter( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED, '__return_true' );
		add_filter( WooPaymentsCutoverController::FILTER_NATIVE_ADMIN_SURFACES_READY, '__return_true' );
		add_filter( WooPaymentsCutoverController::FILTER_PROVIDER_EVENT_TYPES_PENDING_CUTOVER, '__return_empty_array' );
		add_filter( WooPaymentsCutoverController::FILTER_OPERATIONAL_QUEUE_HOOKS_PENDING_CUTOVER, static fn() => array( 'example_hook' ) );
		$this->native_provider_ready = true;

		$this->assertContains( 'operational_queue_hooks_undispositioned', $this->sut->get_preflight_failures() );
		$this->assertFalse( $this->sut->should_show_soft_cutover_notice() );
	}

	/**
	 * @testdox Cutover preflight blocks while required financial migrations cannot be scheduled.
	 */
	public function test_preflight_blocks_when_financial_migrations_cannot_be_scheduled(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		$this->enable_ready_cutover();
		$this->fee_remediation_schedulable = false;

		$this->assertContains( 'financial_migrations_unavailable', $this->sut->get_preflight_failures() );
		$this->assertFalse( $this->sut->should_show_soft_cutover_notice() );
	}

	/**
	 * @testdox Cutover preflight no longer reports event or queue blockers after A5a disposition.
	 */
	public function test_preflight_closes_event_and_queue_blockers_by_default(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		add_filter( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED, '__return_true' );
		add_filter( WooPaymentsCutoverController::FILTER_NATIVE_ADMIN_SURFACES_READY, '__return_true' );
		$this->native_provider_ready = true;

		$failures = $this->sut->get_preflight_failures();

		$this->assertNotContains( 'native_admin_surfaces_unavailable', $failures );
		$this->assertNotContains( 'provider_events_undispositioned', $failures );
		$this->assertNotContains( 'operational_queue_hooks_undispositioned', $failures );
		$this->assertTrue( $this->sut->should_show_soft_cutover_notice() );
	}

	/**
	 * @testdox Cutover preflight blocks while legacy Stripe Billing subscription markers exist.
	 */
	public function test_preflight_blocks_when_legacy_stripe_billing_subscription_marker_exists(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		$this->enable_ready_cutover();
		$this->create_legacy_stripe_billing_subscription( 'pending' );

		$this->assertContains( 'legacy_stripe_billing_subscriptions_present', $this->sut->get_preflight_failures() );
		$this->assertFalse( $this->sut->should_show_soft_cutover_notice() );
	}

	/**
	 * @testdox Cutover preflight blocks cancelled legacy Stripe Billing subscription markers.
	 */
	public function test_preflight_blocks_cancelled_legacy_stripe_billing_subscription_marker(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		$this->enable_ready_cutover();
		$this->create_legacy_stripe_billing_subscription( 'cancelled' );

		$this->assertContains( 'legacy_stripe_billing_subscriptions_present', $this->sut->get_preflight_failures() );
		$this->assertFalse( $this->sut->should_show_soft_cutover_notice() );
	}

	/**
	 * @testdox Cutover preflight blocks migrated legacy Stripe Billing subscription marker variants.
	 */
	public function test_preflight_blocks_migrated_legacy_stripe_billing_subscription_marker(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		$this->enable_ready_cutover();
		$this->create_legacy_stripe_billing_subscription( 'cancelled', '_migrated_wcpay_subscription_id' );

		$this->assertContains( 'legacy_stripe_billing_subscriptions_present', $this->sut->get_preflight_failures() );
		$this->assertFalse( $this->sut->should_show_soft_cutover_notice() );
	}

	/**
	 * @testdox Cutover preflight filters cannot remove the legacy Stripe Billing marker blocker.
	 */
	public function test_preflight_filter_cannot_remove_legacy_stripe_billing_marker_blocker(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		$this->enable_ready_cutover();
		$this->create_legacy_stripe_billing_subscription( 'active' );
		add_filter( WooPaymentsCutoverController::FILTER_PREFLIGHT_FAILURES, '__return_empty_array' );

		$this->assertContains( 'legacy_stripe_billing_subscriptions_present', $this->sut->get_preflight_failures() );
		$this->assertFalse( $this->sut->should_show_soft_cutover_notice() );
	}

	/**
	 * @testdox Cutover preflight blocks legacy Stripe Billing invoice order markers.
	 */
	public function test_preflight_blocks_legacy_stripe_billing_invoice_order_marker(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		$this->enable_ready_cutover();
		$order = wc_create_order();
		$order->update_meta_data( '_migrated_wcpay_billing_invoice_id', 'in_migrated_123' );
		$order->save();

		$this->assertContains( 'legacy_stripe_billing_subscriptions_present', $this->sut->get_preflight_failures() );
		$this->assertFalse( $this->sut->should_show_soft_cutover_notice() );
	}

	/**
	 * @testdox Cutover preflight blocks legacy Stripe Billing markers in HPOS order meta.
	 */
	public function test_preflight_blocks_legacy_stripe_billing_hpos_marker(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		$this->enable_ready_cutover();
		$this->create_legacy_stripe_billing_hpos_marker( '_wcpay_pending_invoice_id' );

		$this->assertContains( 'legacy_stripe_billing_subscriptions_present', $this->sut->get_preflight_failures() );
		$this->assertFalse( $this->sut->should_show_soft_cutover_notice() );
	}

	/**
	 * @testdox Cutover preflight allows clean stores without legacy Stripe Billing markers.
	 */
	public function test_preflight_allows_clean_store_without_legacy_stripe_billing_markers(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		$this->enable_ready_cutover();

		$this->assertNotContains( 'legacy_stripe_billing_subscriptions_present', $this->sut->get_preflight_failures() );
		$this->assertTrue( $this->sut->should_show_soft_cutover_notice() );
	}

	/**
	 * @testdox Blocked cutover notice signposts the Stripe Billing migration path.
	 */
	public function test_blocked_notice_signposts_stripe_billing_migration_path(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		$this->enable_ready_cutover();
		$this->create_legacy_stripe_billing_subscription( 'active' );

		ob_start();
		$this->sut->output_blocked_notice();
		$notice = (string) ob_get_clean();

		$this->assertStringContainsString( 'WooCommerce Subscriptions', $notice );
		$this->assertStringContainsString( 'run the WooPayments Stripe Billing migration from the WooPayments extension', $notice );
	}

	/**
	 * @testdox Mandatory auto-deactivation is blocked while legacy Stripe Billing subscription data exists.
	 */
	public function test_mandatory_auto_deactivation_blocks_when_legacy_stripe_billing_subscription_marker_exists(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( false );
		$this->enable_ready_cutover();
		$this->create_legacy_stripe_billing_subscription( 'on-hold' );
		add_filter( WooPaymentsCutoverController::FILTER_MANDATORY_CUTOVER_ENABLED, '__return_true' );

		$this->sut->handle_admin_init();

		$this->assertSame( array(), $this->deactivate_plugin_calls, 'Mandatory cutover must not deactivate the plugin while legacy Stripe Billing subscription data exists.' );
		$this->assertTrue( $this->plugin_active, 'The plugin must keep owning legacy Stripe Billing subscription data.' );
	}

	/**
	 * @testdox Transport readiness filter can still force cutover readiness for controlled rollouts.
	 */
	public function test_transport_filter_can_force_preflight_when_provider_is_not_ready(): void {
		$this->fake_plugin_active();
		$this->fake_current_user_caps( true );
		add_filter( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED, '__return_true' );
		add_filter( WooPaymentsCutoverController::FILTER_NATIVE_ADMIN_SURFACES_READY, '__return_true' );
		add_filter( WooPaymentsCutoverController::FILTER_PROVIDER_EVENT_TYPES_PENDING_CUTOVER, '__return_empty_array' );
		add_filter( WooPaymentsCutoverController::FILTER_OPERATIONAL_QUEUE_HOOKS_PENDING_CUTOVER, '__return_empty_array' );
		add_filter( WooPaymentsCutoverController::FILTER_NATIVE_TRANSPORT_READY, '__return_true' );

		$this->assertTrue( $this->sut->should_show_soft_cutover_notice() );
	}

	/**
	 * Create a cutover controller wired to this test's dependencies.
	 *
	 * @return WooPaymentsCutoverController
	 */
	private function create_cutover_controller(): WooPaymentsCutoverController {
		$controller = new WooPaymentsCutoverController();
		$controller->init(
			wc_get_container()->get( NativePaymentsRuntimeArbiter::class ),
			wc_get_container()->get( LegacyProxy::class ),
			$this->provider,
			new WooPaymentsLegacySubscriptionsGuard(),
			$this->fee_remediation_service,
			$this->platform_connection_service
		);

		return $controller;
	}

	/**
	 * Render admin notices for a cutover controller.
	 *
	 * @param WooPaymentsCutoverController $controller Cutover controller.
	 * @return string Rendered notice markup.
	 */
	private function render_admin_notices( WooPaymentsCutoverController $controller ): string {
		ob_start();
		$controller->output_admin_notices();
		return (string) ob_get_clean();
	}

	/**
	 * Make the native cutover preflight ready.
	 */
	private function enable_ready_cutover(): void {
		add_filter( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED, '__return_true' );
		add_filter( WooPaymentsCutoverController::FILTER_NATIVE_ADMIN_SURFACES_READY, '__return_true' );
		add_filter( WooPaymentsCutoverController::FILTER_PROVIDER_EVENT_TYPES_PENDING_CUTOVER, '__return_empty_array' );
		add_filter( WooPaymentsCutoverController::FILTER_OPERATIONAL_QUEUE_HOOKS_PENDING_CUTOVER, '__return_empty_array' );
		$this->native_provider_ready = true;
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
		$this->plugin_class_loaded   = $site_active || $network_active;
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
						return $this->plugin_class_loaded;
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
	 * Simulate the next request after the WooPayments plugin was deactivated.
	 */
	private function fake_woopayments_class_unloaded(): void {
		$this->plugin_class_loaded = false;
	}

	/**
	 * Create a legacy WCPay/Stripe Billing subscription fixture.
	 *
	 * @param string $status Subscription status without the wc- prefix.
	 * @param string $meta_key Legacy marker meta key.
	 * @return int Subscription post ID.
	 */
	private function create_legacy_stripe_billing_subscription( string $status, string $meta_key = '_wcpay_subscription_id' ): int {
		$this->register_subscription_order_type();

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'shop_subscription',
				'post_status' => 'wc-' . $status,
				'post_title'  => 'Legacy Stripe Billing subscription',
			)
		);

		$this->assertIsInt( $post_id );
		$this->assertGreaterThan( 0, $post_id );
		update_post_meta( $post_id, $meta_key, 'sub_legacy_' . $status );

		return $post_id;
	}

	/**
	 * Create a raw HPOS legacy marker fixture without hydrating an order object.
	 *
	 * @param string $meta_key Legacy marker meta key.
	 * @return int Raw HPOS order ID.
	 */
	private function create_legacy_stripe_billing_hpos_marker( string $meta_key ): int {
		global $wpdb;

		$orders_table = OrdersTableDataStore::get_orders_table_name();
		$meta_table   = OrdersTableDataStore::get_meta_table_name();
		$order_id     = (int) $wpdb->get_var( "SELECT COALESCE(MAX(id), 0) + 1 FROM {$orders_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$wpdb->insert(
			$orders_table,
			array(
				'id'               => $order_id,
				'status'           => 'wc-active',
				'currency'         => 'USD',
				'type'             => 'shop_subscription',
				'date_created_gmt' => gmdate( 'Y-m-d H:i:s' ),
				'date_updated_gmt' => gmdate( 'Y-m-d H:i:s' ),
			)
		);
		$wpdb->insert(
			$meta_table,
			array(
				'order_id'   => $order_id,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Raw HPOS test fixture row.
				'meta_key'   => $meta_key,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Raw HPOS test fixture row.
				'meta_value' => 'in_legacy_hpos',
			)
		);

		$this->raw_hpos_order_ids[] = $order_id;

		return $order_id;
	}

	/**
	 * Delete raw HPOS order rows created by this test.
	 */
	private function delete_raw_hpos_orders(): void {
		global $wpdb;

		if ( array() === $this->raw_hpos_order_ids ) {
			return;
		}

		$order_ids    = implode( ',', array_map( 'absint', $this->raw_hpos_order_ids ) );
		$orders_table = OrdersTableDataStore::get_orders_table_name();
		$meta_table   = OrdersTableDataStore::get_meta_table_name();

		$wpdb->query( "DELETE FROM {$meta_table} WHERE order_id IN ({$order_ids})" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DELETE FROM {$orders_table} WHERE id IN ({$order_ids})" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$this->raw_hpos_order_ids = array();
	}

	/**
	 * Register a lightweight subscription order type for cutover guard tests.
	 */
	private function register_subscription_order_type(): void {
		if ( post_type_exists( 'shop_subscription' ) ) {
			return;
		}

		wc_register_order_type(
			'shop_subscription',
			array(
				'label'                      => 'Subscriptions',
				'public'                     => false,
				'exclude_from_order_views'   => false,
				'exclude_from_order_count'   => true,
				'exclude_from_order_reports' => true,
				'class_name'                 => 'WC_Order',
			)
		);
		$this->registered_subscription_order_type = true;
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
