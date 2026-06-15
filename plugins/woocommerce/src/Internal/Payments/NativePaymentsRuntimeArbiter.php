<?php
/**
 * NativePaymentsRuntimeArbiter class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments;

use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Decides which payments runtime owns a site, guaranteeing mutual exclusion.
 *
 * As WooPayments is merged into core, two runtimes can momentarily exist for the
 * same site: the standalone WooPayments **plugin** and the **core-native** payments
 * runtime. If both partially boot, the site gets double gateway/webhook/asset
 * registration and, worst case, double payment submission. This arbiter is the
 * single source of truth that prevents that — the architectural enforcement of the
 * "exactly one payments runtime" invariant (see design-spec §4.5).
 *
 * Every core-native registration (gateways, Blocks methods, REST controllers,
 * webhook receiver, assets, checkout hooks, ActionScheduler/WP-Cron handlers,
 * migrations, admin notices, eager service construction) MUST consult
 * {@see self::should_native_register()} before doing anything mutating. While the
 * WooPayments plugin owns the runtime, native registers nothing.
 *
 * Ownership rule (until the reverse-gated plugin release has saturated the install
 * base): **plugin-wins is the only allowed state whenever the plugin is active.**
 * Native owns a site only when the plugin is not active and native is explicitly
 * enabled — or when the plugin, while still present, explicitly **yields** the
 * runtime through {@see self::FILTER_PLUGIN_YIELDED} (the handshake the reverse-gated
 * plugin release uses to stand its runtime down in lockstep with not booting it).
 *
 * Plugin detection: this consults the **active-plugins list** ({@see self::PLUGIN_FILE}
 * in the per-site `active_plugins` option and the network `active_sitewide_plugins`
 * option), not just `class_exists( 'WC_Payments' )`. The bootstrap class is defined
 * only when `wcpay_init()` runs at `plugins_loaded` priority 11 (an explicit require,
 * not the autoloader), so `class_exists` is false in the early-boot window where eager
 * registration happens — the active-plugins option is set before plugins load, so it is
 * reliable there, and being per-blog it is correct under multisite / `switch_to_blog()`.
 * `class_exists` is kept only as a secondary fallback for non-standard installs
 * (mu-plugin / symlinked directory whose basename differs).
 *
 * The arbiter is necessary but not sufficient for money-safety. The binding
 * invariant — only one runtime may submit a payment/refund/capture for a given
 * site+order at a time — is additionally backed by a shared order-scoped lock and a
 * deterministic idempotency key in the processing path (design-spec §4.5,
 * introduced with native processing, not here). This class owns *registration*
 * ownership; it does not itself move money.
 *
 * Consumption contract for later stages:
 * - A1+ must consult the arbiter only at or after `plugins_loaded` priority 11 so the
 *   plugin's own boot decision is resolvable; detection here is early-safe, but the
 *   plugin-wins guarantee assumes the plugin has had its chance to claim ownership.
 * - The decision inputs (active-plugins options + the two filters) are request-stable,
 *   so per-call recomputation is consistent across the several subsystems that consult
 *   it within one request. If profiling later warrants it, A1 may pin the decision once
 *   per request; it is intentionally not memoized here while there are no callers.
 * - The active→inactive transition (a merchant disabling the plugin pre-saturation) is
 *   the dangerous window (§4.5 #4). Webhook/AS drain-or-rebind continuity is the cutover
 *   stage's (A5) responsibility; this class provides the ownership signal that gate guards.
 *
 * Resolved as a single instance by the runtime DI container (auto-wired; the container
 * caches one instance), so all consumers share the same arbiter.
 *
 * @since 11.0.0
 * @internal This is part of the internal native-payments platform and is not a public API.
 */
class NativePaymentsRuntimeArbiter {

	/**
	 * Owner value: the standalone WooPayments plugin owns the runtime.
	 *
	 * @var string
	 */
	const OWNER_PLUGIN = 'plugin';

	/**
	 * Owner value: the core-native payments runtime owns the runtime.
	 *
	 * @var string
	 */
	const OWNER_NATIVE = 'native';

	/**
	 * Owner value: no payments runtime is active for this site.
	 *
	 * @var string
	 */
	const OWNER_NONE = 'none';

	/**
	 * The WooPayments plugin's main file, as it appears in the active-plugins option.
	 *
	 * @var string
	 */
	const PLUGIN_FILE = 'woocommerce-payments/woocommerce-payments.php';

	/**
	 * Filter that can force the runtime owner in the *conservative* direction only.
	 *
	 * Honored values:
	 * - {@see self::OWNER_NONE}: global kill switch — stand every runtime down.
	 * - {@see self::OWNER_PLUGIN}: force plugin-wins, but only when the plugin is present.
	 *
	 * It deliberately CANNOT promote native over a present, non-yielded plugin (the
	 * dangerous direction that risks dual-runtime): that requires the plugin's own
	 * {@see self::FILTER_PLUGIN_YIELDED} signal. This is the per-capability escape hatch
	 * referenced in design-spec §4.6, constrained so a stray/third-party filter cannot
	 * induce a double-runtime state. Any other value is ignored.
	 *
	 * @var string
	 */
	const FILTER_RUNTIME_OWNER = 'woocommerce_native_payments_runtime_owner';

	/**
	 * Filter that reports whether the core-native payments runtime is enabled for this site.
	 *
	 * Defaults to false: A0 ships the native runtime dormant. This is wired to a real
	 * per-site feature flag at cutover; until then native never owns a site unless this
	 * is filtered on (and the plugin is not active, or has yielded).
	 *
	 * @var string
	 */
	const FILTER_NATIVE_ENABLED = 'woocommerce_native_payments_enabled';

	/**
	 * Filter through which the WooPayments plugin yields the runtime to native.
	 *
	 * This is the stand-down handshake: the reverse-gated plugin release, when it decides
	 * native should own, returns true here AND skips booting its own runtime — in lockstep.
	 * Only then does a still-present plugin let native take over. Defaults to false (the
	 * plugin has not yielded). This signal is plugin-owned; A5 hardens it into a verified,
	 * option-backed handshake written by the plugin's stand-down routine.
	 *
	 * @var string
	 */
	const FILTER_PLUGIN_YIELDED = 'woocommerce_native_payments_plugin_yielded';

	/**
	 * The legacy proxy, used for mockable calls to global functions.
	 *
	 * @var LegacyProxy
	 */
	private LegacyProxy $legacy_proxy;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param LegacyProxy $legacy_proxy The legacy proxy.
	 */
	final public function init( LegacyProxy $legacy_proxy ): void {
		$this->legacy_proxy = $legacy_proxy;
	}

	/**
	 * Get the payments runtime owner for the current site.
	 *
	 * @since 11.0.0
	 *
	 * @return string One of self::OWNER_PLUGIN, self::OWNER_NATIVE, self::OWNER_NONE.
	 */
	public function get_runtime_owner(): string {
		/**
		 * Filters the payments runtime owner in the conservative direction.
		 *
		 * @since 11.0.0
		 *
		 * @param string|null $owner self::OWNER_NONE to stand everything down, self::OWNER_PLUGIN to
		 *                           force plugin-wins (only honored when the plugin is present), or null.
		 */
		$forced = apply_filters( self::FILTER_RUNTIME_OWNER, null );

		// Conservative kill switch: force everything down regardless of state.
		if ( self::OWNER_NONE === $forced ) {
			return self::OWNER_NONE;
		}

		$plugin_active = $this->is_woopayments_plugin_active();

		// Explicit plugin-wins override is honored only when the plugin is actually present.
		if ( self::OWNER_PLUGIN === $forced && $plugin_active ) {
			return self::OWNER_PLUGIN;
		}

		// Plugin-wins is the only allowed state while the plugin is active (design-spec §4.5),
		// unless the plugin has explicitly yielded the runtime to native (the stand-down handshake).
		if ( $plugin_active && ! $this->plugin_has_yielded() ) {
			return self::OWNER_PLUGIN;
		}

		return $this->is_native_runtime_enabled() ? self::OWNER_NATIVE : self::OWNER_NONE;
	}

	/**
	 * Tell whether core-native code may perform mutating registration for this site.
	 *
	 * This is the guard every native registration must consult before acting.
	 *
	 * @since 11.0.0
	 *
	 * @return bool True only when the native runtime owns this site.
	 */
	public function should_native_register(): bool {
		return self::OWNER_NATIVE === $this->get_runtime_owner();
	}

	/**
	 * Tell whether the WooPayments plugin owns the runtime for this site.
	 *
	 * @since 11.0.0
	 *
	 * @return bool True when the plugin owns this site's payments runtime.
	 */
	public function is_plugin_runtime_active(): bool {
		return self::OWNER_PLUGIN === $this->get_runtime_owner();
	}

	/**
	 * Tell whether the core-native payments runtime is enabled for this site.
	 *
	 * This is the feature-flag state, independent of ownership: native can be enabled
	 * while the plugin still owns the runtime (in which case the plugin still wins).
	 *
	 * @since 11.0.0
	 *
	 * @return bool True when the native runtime is enabled.
	 */
	public function is_native_runtime_enabled(): bool {
		/**
		 * Filters whether the core-native payments runtime is enabled for this site.
		 *
		 * @since 11.0.0
		 *
		 * @param bool $enabled Whether the native runtime is enabled. Default false.
		 */
		return (bool) apply_filters( self::FILTER_NATIVE_ENABLED, false );
	}

	/**
	 * Tell whether the WooPayments plugin has yielded the runtime to native.
	 *
	 * Internal to the ownership decision; the plugin signals a yield through the
	 * self::FILTER_PLUGIN_YIELDED filter, not by calling this method.
	 *
	 * @return bool True when the plugin has stood its runtime down in favor of native.
	 */
	private function plugin_has_yielded(): bool {
		/**
		 * Filters whether the WooPayments plugin has yielded the runtime to native.
		 *
		 * @since 11.0.0
		 *
		 * @param bool $yielded Whether the plugin has yielded. Default false.
		 */
		return (bool) apply_filters( self::FILTER_PLUGIN_YIELDED, false );
	}

	/**
	 * Tell whether the WooPayments plugin is active for the current site.
	 *
	 * Primary signal is the active-plugins list (per-site + network), which is reliable
	 * in the early-boot window and correct per-site under multisite. The class_exists
	 * check is only a fallback for non-standard installs.
	 *
	 * @return bool True when the WooPayments plugin is active.
	 */
	private function is_woopayments_plugin_active(): bool {
		if ( $this->plugin_in_active_list() ) {
			return true;
		}

		return (bool) $this->legacy_proxy->call_function( 'class_exists', 'WC_Payments' );
	}

	/**
	 * Tell whether the WooPayments plugin appears in the active-plugins lists.
	 *
	 * @return bool True when the plugin is in the per-site or network active-plugins list.
	 */
	private function plugin_in_active_list(): bool {
		$site_active = (array) $this->legacy_proxy->call_function( 'get_option', 'active_plugins', array() );
		if ( in_array( self::PLUGIN_FILE, $site_active, true ) ) {
			return true;
		}

		$network_active = (array) $this->legacy_proxy->call_function( 'get_site_option', 'active_sitewide_plugins', array() );
		return isset( $network_active[ self::PLUGIN_FILE ] );
	}
}
