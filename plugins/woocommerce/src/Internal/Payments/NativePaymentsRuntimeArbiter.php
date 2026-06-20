<?php
/**
 * NativePaymentsRuntimeArbiter class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments;

use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Decides which payments runtime owns a site, guaranteeing exactly one is active.
 *
 * While WooPayments is being absorbed into core, a site can momentarily have two payment runtimes:
 * the standalone WooPayments **plugin** and the **core-native** runtime. If both register, the site
 * gets duplicate gateways/webhooks/assets and, worst case, a double charge. This arbiter is the
 * single per-request authority that prevents that.
 *
 * Rule: **the plugin wins whenever it is active.** While the WooPayments plugin is active the native
 * runtime stays dormant (registers nothing) and the plugin processes payments exactly as before;
 * native owns the site only once the plugin is no longer active and the native runtime is enabled.
 * A merchant moves from plugin to native by **deactivating the plugin** — surfaced (and, at the
 * cutover release, performed automatically) by the migration-notice / auto-deactivation component,
 * which is modeled on WooCommerce's merged-package handling (`src/Packages.php`). This arbiter is the
 * per-request safety that keeps the unavoidable one-request deactivation overlap free of a dual
 * runtime: on the overlap request the plugin is still active so native stays dormant, and native
 * takes over from the next request once the plugin is gone.
 *
 * Every core-native registration (gateways, Blocks methods, REST controllers, webhook receiver,
 * assets, checkout hooks, ActionScheduler/WP-Cron handlers, migrations, admin notices, eager service
 * construction) MUST consult {@see self::should_native_register()} before doing anything mutating.
 *
 * Plugin detection uses the active-plugins list (per-site + network), reliable in the early-boot
 * window and correct per-site under multisite; `class_exists( 'WC_Payments' )` is only a fallback for
 * non-standard installs (the plugin defines its bootstrap class late, at `plugins_loaded:11`, so
 * `class_exists` alone is false during early registration). Consult the arbiter at or after
 * `plugins_loaded:11` so the plugin's own boot is resolvable.
 *
 * The arbiter is necessary but not sufficient for money-safety: the binding invariant — only one
 * runtime may submit a payment/refund/capture for a given site+order at a time — is additionally
 * backed by a shared order lock + deterministic idempotency key in the processing path (introduced
 * with native processing, not here).
 *
 * Resolved as a single instance by the runtime DI container (auto-wired; the container caches one
 * instance), so all consumers share the same arbiter.
 *
 * Lifecycle — TRANSITIONAL, not permanent core API. It exists only while the standalone plugin can
 * coexist with native; once the plugin is sunset, the plugin-detection path is dead and this should
 * collapse to a plain "is native enabled" gate or be removed. It ships dormant at the keystone stage
 * (no consumers yet); it becomes live when the boot wires it and native registrations consult it.
 *
 * @since 11.0.0
 * @internal Transitional internal component (not a public API); slated to simplify/remove once the standalone plugin is sunset.
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
	 * Filter that reports whether the core-native payments runtime is enabled for this site.
	 *
	 * Defaults to false: the native runtime ships dormant and is enabled (eventually default-on at
	 * the cutover release) only when it is ready to own the site. Even when enabled, the plugin still
	 * wins while it is active.
	 *
	 * @var string
	 */
	const FILTER_NATIVE_ENABLED = 'woocommerce_native_payments_enabled';

	/**
	 * Default state for the native WooPayments runtime rollout.
	 *
	 * This intentionally remains false until the final A5 stage-boundary gates approve the release/default-on flip.
	 *
	 * @var bool
	 */
	public const DEFAULT_NATIVE_RUNTIME_ENABLED = false;

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
		// Plugin-wins is the only allowed state while the plugin is active; native is dormant.
		if ( $this->is_woopayments_plugin_active() ) {
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
	 * The migration-notice / auto-deactivation component uses this to know when to surface the
	 * "WooPayments is now in core — deactivate the extension" notice and the cutover action.
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
	 * This is the feature-flag state, independent of ownership: native can be enabled while the
	 * plugin still owns the runtime (in which case the plugin still wins).
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
		 * @param bool $enabled Whether the native runtime is enabled.
		 */
		return (bool) apply_filters( self::FILTER_NATIVE_ENABLED, self::DEFAULT_NATIVE_RUNTIME_ENABLED );
	}

	/**
	 * Tell whether the WooPayments plugin is active for the current site.
	 *
	 * Primary signal is the active-plugins list (per-site + network), which is reliable in the
	 * early-boot window and correct per-site under multisite. The class_exists check is only a
	 * fallback for non-standard installs.
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
