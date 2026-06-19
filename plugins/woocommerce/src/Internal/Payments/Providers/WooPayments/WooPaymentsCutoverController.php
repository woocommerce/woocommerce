<?php
/**
 * WooPaymentsCutoverController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Subscriptions\WooPaymentsLegacySubscriptionsGuard;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Proxies\LegacyProxy;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the WooPayments plugin-to-native cutover UX.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsCutoverController implements RegisterHooksInterface {

	/**
	 * Query action value used to disable the standalone WooPayments plugin.
	 *
	 * @var string
	 */
	public const ACTION_DISABLE = 'disable_woopayments';

	/**
	 * Filter that controls the soft cutover admin notice.
	 *
	 * @var string
	 */
	public const FILTER_SOFT_CUTOVER_ENABLED = 'woocommerce_woopayments_native_soft_cutover_enabled';

	/**
	 * Filter that controls mandatory WooPayments auto-deactivation and activation blocking.
	 *
	 * @var string
	 */
	public const FILTER_MANDATORY_CUTOVER_ENABLED = 'woocommerce_woopayments_native_mandatory_cutover_enabled';

	/**
	 * Filter that reports whether a core-owned WooPayments transport is ready to process after deactivation.
	 *
	 * @var string
	 */
	public const FILTER_NATIVE_TRANSPORT_READY = 'woocommerce_woopayments_native_transport_ready';

	/**
	 * Filter that reports whether native WooPayments merchant admin surfaces are ready after deactivation.
	 *
	 * @var string
	 */
	public const FILTER_NATIVE_ADMIN_SURFACES_READY = 'woocommerce_woopayments_native_admin_surfaces_ready';

	/**
	 * Filter that reports provider event types still pending native cutover disposition.
	 *
	 * @var string
	 */
	public const FILTER_PROVIDER_EVENT_TYPES_PENDING_CUTOVER = 'woocommerce_woopayments_native_cutover_pending_event_types';

	/**
	 * Filter that reports operational queue hooks still pending native cutover disposition.
	 *
	 * @var string
	 */
	public const FILTER_OPERATIONAL_QUEUE_HOOKS_PENDING_CUTOVER = 'woocommerce_woopayments_native_cutover_pending_operational_queue_hooks';

	/**
	 * Filter for cutover preflight failures.
	 *
	 * @var string
	 */
	public const FILTER_PREFLIGHT_FAILURES = 'woocommerce_woopayments_native_cutover_preflight_failures';

	/**
	 * Nonce action for the one-click disable action.
	 *
	 * @var string
	 */
	public const NONCE_ACTION = 'woocommerce_disable_woopayments';

	/**
	 * Nonce query parameter for the one-click disable action.
	 *
	 * @var string
	 */
	public const NONCE_NAME = '_wc_woopayments_cutover_nonce';

	/**
	 * Query parameter that carries the cutover action.
	 *
	 * @var string
	 */
	public const QUERY_ACTION = 'wc_woopayments_cutover_action';

	/**
	 * Query parameter that carries the cutover status notice.
	 *
	 * @var string
	 */
	public const QUERY_STATUS = 'wc_woopayments_cutover_status';

	/**
	 * Legacy operational queue hooks that still need native cutover disposition.
	 *
	 * @var string[]
	 */
	private const DEFAULT_PENDING_OPERATIONAL_QUEUE_HOOKS = array();

	/**
	 * Status value for a successful plugin disable.
	 *
	 * @var string
	 */
	public const STATUS_DISABLED = 'disabled';

	/**
	 * Status value for a blocked plugin disable.
	 *
	 * @var string
	 */
	public const STATUS_BLOCKED = 'blocked';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private NativePaymentsRuntimeArbiter $arbiter;

	/**
	 * Legacy proxy.
	 *
	 * @var LegacyProxy
	 */
	private LegacyProxy $legacy_proxy;

	/**
	 * Native WooPayments provider.
	 *
	 * @var WooPaymentsProvider
	 */
	private WooPaymentsProvider $provider;

	/**
	 * Legacy subscription data guard.
	 *
	 * @var WooPaymentsLegacySubscriptionsGuard
	 */
	private WooPaymentsLegacySubscriptionsGuard $legacy_subscriptions_guard;

	/**
	 * Canceled-authorization fee remediation queue owner.
	 *
	 * @var WooPaymentsCanceledAuthorizationFeeRemediationService
	 */
	private WooPaymentsCanceledAuthorizationFeeRemediationService $fee_remediation_service;

	/**
	 * Platform connection readiness service.
	 *
	 * @var WooPaymentsPlatformConnectionService
	 */
	private WooPaymentsPlatformConnectionService $platform_connection_service;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter                               $arbiter                    Runtime owner arbiter.
	 * @param LegacyProxy                                                $legacy_proxy               Legacy proxy.
	 * @param WooPaymentsProvider                                        $provider                   Native WooPayments provider.
	 * @param WooPaymentsLegacySubscriptionsGuard|null                   $legacy_subscriptions_guard Legacy subscription data guard.
	 * @param WooPaymentsCanceledAuthorizationFeeRemediationService|null $fee_remediation_service    Canceled-authorization fee remediation queue owner.
	 * @param WooPaymentsPlatformConnectionService|null                  $platform_connection_service Platform connection readiness service.
	 */
	final public function init(
		NativePaymentsRuntimeArbiter $arbiter,
		LegacyProxy $legacy_proxy,
		WooPaymentsProvider $provider,
		?WooPaymentsLegacySubscriptionsGuard $legacy_subscriptions_guard = null,
		?WooPaymentsCanceledAuthorizationFeeRemediationService $fee_remediation_service = null,
		?WooPaymentsPlatformConnectionService $platform_connection_service = null
	): void {
		$this->arbiter                     = $arbiter;
		$this->legacy_proxy                = $legacy_proxy;
		$this->provider                    = $provider;
		$this->legacy_subscriptions_guard  = $legacy_subscriptions_guard ?? wc_get_container()->get( WooPaymentsLegacySubscriptionsGuard::class );
		$this->fee_remediation_service     = $fee_remediation_service ?? wc_get_container()->get( WooPaymentsCanceledAuthorizationFeeRemediationService::class );
		$this->platform_connection_service = $platform_connection_service ?? wc_get_container()->get( WooPaymentsPlatformConnectionService::class );
	}

	/**
	 * Register hooks.
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'handle_admin_init' ) );
		add_action( 'admin_notices', array( $this, 'output_admin_notices' ) );
		add_action( 'activate_plugin', array( $this, 'guard_woopayments_activation' ) );
	}

	/**
	 * Handle admin init cutover actions.
	 *
	 * @internal
	 */
	public function handle_admin_init(): void {
		$this->maybe_auto_deactivate_plugin();

		$action = isset( $_GET[ self::QUERY_ACTION ] ) ? sanitize_key( wp_unslash( $_GET[ self::QUERY_ACTION ] ) ) : '';
		if ( self::ACTION_DISABLE !== $action ) {
			return;
		}

		$nonce = isset( $_GET[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::NONCE_NAME ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );
		}

		$status = $this->disable_woopayments_plugin() ? self::STATUS_DISABLED : self::STATUS_BLOCKED;
		$url    = add_query_arg(
			array(
				self::QUERY_STATUS => $status,
			),
			admin_url( 'plugins.php' )
		);

		wp_safe_redirect( $url );
		$this->legacy_proxy->exit();
	}

	/**
	 * Output WooPayments cutover admin notices.
	 *
	 * @internal
	 */
	public function output_admin_notices(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reads post-redirect status only; no state change is performed here.
		$status = isset( $_GET[ self::QUERY_STATUS ] ) ? sanitize_key( wp_unslash( $_GET[ self::QUERY_STATUS ] ) ) : '';
		if ( self::STATUS_DISABLED === $status ) {
			$this->output_success_notice();
			return;
		}

		if ( self::STATUS_BLOCKED === $status ) {
			$this->output_blocked_notice();
			return;
		}

		if ( $this->should_show_soft_cutover_notice() ) {
			$this->output_soft_cutover_notice();
		}
	}

	/**
	 * Tell whether the soft cutover notice should be shown.
	 *
	 * @return bool
	 */
	public function should_show_soft_cutover_notice(): bool {
		return $this->is_soft_cutover_enabled()
			&& $this->arbiter->is_plugin_runtime_active()
			&& $this->current_user_can_cutover()
			&& $this->is_cutover_ready();
	}

	/**
	 * Disable the standalone WooPayments plugin when all cutover guards pass.
	 *
	 * @return bool True when the plugin no longer owns the runtime.
	 */
	public function disable_woopayments_plugin(): bool {
		if ( ! $this->arbiter->is_plugin_runtime_active() || ! $this->current_user_can_cutover() || ! $this->is_cutover_ready() ) {
			return false;
		}

		return $this->deactivate_woopayments_plugin();
	}

	/**
	 * Deactivate the standalone WooPayments plugin.
	 *
	 * @return bool True when the plugin no longer owns the runtime.
	 */
	private function deactivate_woopayments_plugin(): bool {
		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( 'unavailable' === $this->fee_remediation_service->ensure_scheduled() ) {
			wc_get_logger()->error(
				'WooPayments could not be deactivated because native WooPayments could not schedule canceled-authorization fee remediation.',
				array( 'source' => 'woocommerce-woopayments-cutover' )
			);
			return false;
		}

		$this->legacy_proxy->call_function(
			'deactivate_plugins',
			NativePaymentsRuntimeArbiter::PLUGIN_FILE,
			false,
			$this->is_woopayments_network_active()
		);

		return ! $this->is_woopayments_site_active() && ! $this->is_woopayments_network_active();
	}

	/**
	 * Guard WooPayments activation once mandatory native cutover is enabled.
	 *
	 * @internal
	 *
	 * @param string $plugin Plugin path being activated.
	 */
	public function guard_woopayments_activation( string $plugin ): void {
		if (
			NativePaymentsRuntimeArbiter::PLUGIN_FILE !== $plugin ||
			! $this->is_mandatory_cutover_enabled() ||
			! $this->is_cutover_ready() ||
			Constants::is_true( 'WC_ALLOW_MERGED_FEATURE_PLUGINS' )
		) {
			return;
		}

		wp_die(
			esc_html__( 'WooPayments cannot be activated because its functionality is now included in WooCommerce core.', 'woocommerce' ),
			esc_html__( 'Plugin activation error', 'woocommerce' ),
			array(
				'link_url'  => esc_url( admin_url( 'plugins.php' ) ),
				'link_text' => esc_html__( 'Return to the Plugins page', 'woocommerce' ),
			)
		);
	}

	/**
	 * Get cutover preflight failure codes.
	 *
	 * @return array<int,string> Failure codes.
	 */
	public function get_preflight_failures(): array {
		$failures = array();

		if ( ! $this->arbiter->is_native_runtime_enabled() ) {
			$failures[] = 'native_runtime_disabled';
		}

		/**
		 * Filters whether the native WooPayments transport is ready for cutover.
		 *
		 * @param bool $is_ready Whether the native transport can process WooPayments requests.
		 *
		 * @since 11.0.0
		 */
		if ( ! (bool) apply_filters( self::FILTER_NATIVE_TRANSPORT_READY, $this->is_native_transport_ready() ) ) {
			$failures[] = 'native_transport_unavailable';
		}

		$platform_connection_failures = $this->platform_connection_service->get_cutover_preflight_failures();
		$failures                     = array_merge( $failures, $platform_connection_failures );

		/**
		 * Filters whether native WooPayments merchant admin surfaces are ready after deactivation.
		 *
		 * @param bool $is_ready Whether native merchant admin surfaces are ready.
		 *
		 * @since 11.0.0
		 */
		if ( ! (bool) apply_filters( self::FILTER_NATIVE_ADMIN_SURFACES_READY, true ) ) {
			$failures[] = 'native_admin_surfaces_unavailable';
		}

		if ( array() !== $this->get_pending_provider_event_types() ) {
			$failures[] = 'provider_events_undispositioned';
		}

		if ( array() !== $this->get_pending_operational_queue_hooks() ) {
			$failures[] = 'operational_queue_hooks_undispositioned';
		}

		if ( ! $this->fee_remediation_service->can_schedule_cutover_remediation() ) {
			$failures[] = 'financial_migrations_unavailable';
		}

		/**
		 * Filters WooPayments native cutover preflight failures.
		 *
		 * @param array<int,string> $failures Failure codes.
		 *
		 * @since 11.0.0
		 */
		$failures = apply_filters( self::FILTER_PREFLIGHT_FAILURES, $failures );

		$failures = is_array( $failures ) ? array_values( array_map( 'strval', $failures ) ) : array( 'preflight_filter_invalid' );

		foreach ( $platform_connection_failures as $failure ) {
			$failure = (string) $failure;
			if ( '' !== $failure && ! in_array( $failure, $failures, true ) ) {
				$failures[] = $failure;
			}
		}

		if (
			$this->legacy_subscriptions_guard->has_legacy_stripe_billing_subscription_markers() &&
			! in_array( 'legacy_stripe_billing_subscriptions_present', $failures, true )
		) {
			$failures[] = 'legacy_stripe_billing_subscriptions_present';
		}

		return $failures;
	}

	/**
	 * Auto-deactivate WooPayments when mandatory cutover is enabled and safe.
	 */
	private function maybe_auto_deactivate_plugin(): void {
		if (
			! $this->is_mandatory_cutover_enabled() ||
			! $this->arbiter->is_plugin_runtime_active() ||
			Constants::is_true( 'WC_ALLOW_MERGED_FEATURE_PLUGINS' )
		) {
			return;
		}

		if ( ! $this->is_cutover_ready() ) {
			add_action( 'admin_notices', array( $this, 'output_blocked_notice' ) );
			return;
		}

		if ( $this->deactivate_woopayments_plugin() ) {
			add_action( 'admin_notices', array( $this, 'output_success_notice' ) );
			return;
		}

		add_action( 'admin_notices', array( $this, 'output_blocked_notice' ) );
	}

	/**
	 * Tell whether all deterministic cutover preflight checks pass.
	 *
	 * @return bool
	 */
	private function is_cutover_ready(): bool {
		return array() === $this->get_preflight_failures();
	}

	/**
	 * Tell whether native WooPayments can process after plugin deactivation.
	 *
	 * @return bool
	 */
	private function is_native_transport_ready(): bool {
		try {
			return $this->provider->can_process_payments();
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	/**
	 * Get provider event types that still need native cutover disposition.
	 *
	 * @return array<int,string> Event type identifiers.
	 */
	private function get_pending_provider_event_types(): array {
		/**
		 * Filters provider event types that still need native cutover disposition.
		 *
		 * @param array<int,string> $event_types Event types that still block cutover.
		 *
		 * @since 11.0.0
		 */
		$event_types = apply_filters( self::FILTER_PROVIDER_EVENT_TYPES_PENDING_CUTOVER, WooPaymentsEventIngestor::KNOWN_UNHANDLED_EVENT_TYPES );

		if ( ! is_array( $event_types ) ) {
			return array( 'provider_events_filter_invalid' );
		}

		return array_values(
			array_unique(
				array_filter(
					array_map( 'strval', $event_types ),
					static fn( string $event_type ): bool => '' !== $event_type
				)
			)
		);
	}

	/**
	 * Get operational queue hooks that still need native cutover disposition.
	 *
	 * @return array<int,string> Operational queue hook names.
	 */
	private function get_pending_operational_queue_hooks(): array {
		/**
		 * Filters operational queue hooks that still need native cutover disposition.
		 *
		 * @param array<int,string> $hook_names Operational queue hooks that still block cutover.
		 *
		 * @since 11.0.0
		 */
		$hook_names = apply_filters( self::FILTER_OPERATIONAL_QUEUE_HOOKS_PENDING_CUTOVER, self::DEFAULT_PENDING_OPERATIONAL_QUEUE_HOOKS );

		if ( ! is_array( $hook_names ) ) {
			return array( 'operational_queue_hooks_filter_invalid' );
		}

		return array_values(
			array_unique(
				array_filter(
					array_map( 'strval', $hook_names ),
					static fn( string $hook_name ): bool => '' !== $hook_name
				)
			)
		);
	}

	/**
	 * Tell whether the soft cutover notice is enabled.
	 *
	 * @return bool
	 */
	private function is_soft_cutover_enabled(): bool {
		/**
		 * Filters whether the WooPayments native soft cutover notice is enabled.
		 *
		 * @since 11.0.0
		 *
		 * @param bool $enabled Whether the soft cutover notice is enabled.
		 */
		return (bool) apply_filters( self::FILTER_SOFT_CUTOVER_ENABLED, true );
	}

	/**
	 * Tell whether mandatory native cutover is enabled.
	 *
	 * @return bool
	 */
	private function is_mandatory_cutover_enabled(): bool {
		/**
		 * Filters whether mandatory WooPayments native cutover is enabled.
		 *
		 * @since 11.0.0
		 *
		 * @param bool $enabled Whether mandatory cutover is enabled.
		 */
		return (bool) apply_filters( self::FILTER_MANDATORY_CUTOVER_ENABLED, false );
	}

	/**
	 * Tell whether the current user can perform cutover actions.
	 *
	 * @return bool
	 */
	private function current_user_can_cutover(): bool {
		if ( ! $this->legacy_proxy->call_function( 'current_user_can', 'manage_woocommerce' ) ) {
			return false;
		}

		$plugin_capability = $this->is_woopayments_network_active() ? 'manage_network_plugins' : 'activate_plugins';

		return (bool) $this->legacy_proxy->call_function( 'current_user_can', $plugin_capability );
	}

	/**
	 * Tell whether WooPayments is active network-wide.
	 *
	 * @return bool
	 */
	private function is_woopayments_network_active(): bool {
		$network_active = (array) $this->legacy_proxy->call_function( 'get_site_option', 'active_sitewide_plugins', array() );

		return isset( $network_active[ NativePaymentsRuntimeArbiter::PLUGIN_FILE ] );
	}

	/**
	 * Tell whether WooPayments is active for the current site.
	 *
	 * @return bool
	 */
	private function is_woopayments_site_active(): bool {
		$active_plugins = (array) $this->legacy_proxy->call_function( 'get_option', 'active_plugins', array() );

		return in_array( NativePaymentsRuntimeArbiter::PLUGIN_FILE, $active_plugins, true );
	}

	/**
	 * Get a nonce-protected URL for the soft cutover action.
	 *
	 * @return string
	 */
	private function get_disable_url(): string {
		$url = add_query_arg(
			array(
				self::QUERY_ACTION => self::ACTION_DISABLE,
			),
			admin_url( 'admin.php' )
		);

		return wp_nonce_url( $url, self::NONCE_ACTION, self::NONCE_NAME );
	}

	/**
	 * Output the soft cutover notice.
	 */
	private function output_soft_cutover_notice(): void {
		?>
		<div class="notice notice-info">
			<p>
				<?php esc_html_e( 'WooPayments is now part of WooCommerce core. Disable the WooPayments extension to continue processing payments with WooPayments.', 'woocommerce' ); ?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( $this->get_disable_url() ); ?>">
					<?php esc_html_e( 'Disable WooPayments', 'woocommerce' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Output the successful cutover notice.
	 */
	public function output_success_notice(): void {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'WooPayments is now fully native in WooCommerce. Everything works as before.', 'woocommerce' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Output the blocked cutover notice.
	 */
	public function output_blocked_notice(): void {
		$failures = $this->get_preflight_failures();
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'WooPayments could not be disabled because native WooPayments is not ready to process payments yet.', 'woocommerce' ); ?></p>
			<?php if ( in_array( 'legacy_stripe_billing_subscriptions_present', $failures, true ) ) : ?>
				<p>
					<?php
					printf(
						/* translators: %s: WooCommerce Subscriptions product name. */
						esc_html__( 'This store still has legacy Stripe Billing subscription data. Install %s, run the WooPayments Stripe Billing migration from the WooPayments extension, then try again.', 'woocommerce' ),
						'WooCommerce Subscriptions'
					);
					?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
}
