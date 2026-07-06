<?php
/**
 * CapabilityRegistry - the WordPress-facing gateway capability entry point.
 *
 * Payment gateways declare their subscription capabilities here (the engine's
 * counterpart to WooCommerce's feature-compatibility declarations), and the
 * engine reads the declarations to gate behavior such as renewal scheduling and
 * payment-method change. This class is the public, gateway-author-facing
 * surface; it delegates the actual storage to the WordPress-free Core class
 * {@see \Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities}
 * and adds the live-gateway lookup, the override filter, and the ready-hook
 * wiring on top.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Gateway
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Gateway;

use WC_Order;
use WC_Payment_Gateway;
use WC_Payment_Gateways;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Public capability registry - gateway-author-facing.
 */
final class CapabilityRegistry {

	/**
	 * Filter that always has the final word on a capability check.
	 *
	 * Receives the result of the static-declaration and live-gateway steps plus
	 * the optional order context, and returns the final value. Runtime-injection
	 * gateways (where capability resolution depends on the merchant account
	 * routing the order) flip a capability on or off here per order.
	 */
	public const CAPABILITY_CHECK_FILTER = 'woocommerce_subscriptions_engine_gateway_capability_check';

	/**
	 * Action fired once capability resolution is stable for the request.
	 *
	 * Gateways declare in the canonical declaration window
	 * (`before_woocommerce_init`); WooCommerce finishes loading afterwards.
	 * Consumers that resolve capabilities should wait for this action so the
	 * live-gateway step has a populated registry to read.
	 */
	public const CAPABILITIES_READY_ACTION = 'woocommerce_subscriptions_engine_capabilities_ready';

	/**
	 * `woocommerce_loaded` priority for the ready dispatch.
	 *
	 * Set after the priority used by gateway integration frameworks that inject
	 * capability flags onto the live gateway instance, so the live-gateway step
	 * sees those flags by the time the ready action fires.
	 */
	public const READY_HOOK_PRIORITY = 20;

	/**
	 * Whether hooks have already been registered, to keep registration
	 * idempotent when more than one consumer boots the engine in one request.
	 *
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Wire the ready dispatch onto `woocommerce_loaded`.
	 *
	 * Idempotent: safe to call from more than one consumer's boot.
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		self::$initialized = true;

		add_action(
			'woocommerce_loaded',
			static function (): void {
				do_action( self::CAPABILITIES_READY_ACTION );
			},
			self::READY_HOOK_PRIORITY
		);
	}

	/**
	 * Register a gateway's declared capabilities.
	 *
	 * Call from a gateway's `before_woocommerce_init` hook. Each entry must be
	 * one of the flag constants on {@see GatewayCapabilities}; an unknown flag
	 * throws there so a typo surfaces at registration. Re-declaration replaces.
	 *
	 * @param string             $gateway_id   Gateway identifier (matches `WC_Payment_Gateway::$id`).
	 * @param array<int, string> $capabilities Capability flag strings.
	 */
	public static function declare_compatibility( string $gateway_id, array $capabilities ): void {
		GatewayCapabilities::declare( $gateway_id, $capabilities );
	}

	/**
	 * Whether `$gateway_id` supports `$capability`.
	 *
	 * Resolution chain:
	 *
	 *  1. Static declarations via {@see GatewayCapabilities::is_declared()} - the
	 *     most explicit signal: the gateway author told us up front.
	 *  2. The live gateway instance's `$supports` array, for frameworks that
	 *     inject capability flags onto the instance during their own init rather
	 *     than at class-load time. Skipped silently when WooCommerce is not
	 *     loaded yet or the gateway is not in the registry.
	 *  3. The {@see self::CAPABILITY_CHECK_FILTER} filter, which is always the
	 *     final word: it receives the steps-1-2 result and the `$context` order,
	 *     and a filter returning false overrides an earlier true, and vice versa.
	 *  4. Cast to bool.
	 *
	 * Returns false (does not throw) for unknown gateway ids and unknown
	 * capability strings - this is a predicate, not a validation path.
	 *
	 * @param string        $gateway_id Gateway identifier.
	 * @param string        $capability Capability flag.
	 * @param WC_Order|null $context    Optional order for per-order resolution.
	 */
	public static function supports( string $gateway_id, string $capability, ?WC_Order $context = null ): bool {
		// Step 1: static declarations.
		$current = GatewayCapabilities::is_declared( $gateway_id, $capability );

		// Step 2: live gateway instance `$supports` array. Only consult it when
		// step 1 has not already resolved true - the filter still gets a chance
		// to flip the answer regardless.
		if ( ! $current ) {
			$current = self::gateway_instance_supports( $gateway_id, $capability );
		}

		/**
		 * Filters the resolved capability check before `supports()` returns.
		 *
		 * The final word: a filter returning false overrides a true from the
		 * static-declaration or live-gateway steps, and vice versa.
		 *
		 * @param bool          $current    Result of the static-declaration and live-gateway steps.
		 * @param string        $gateway_id Gateway being checked.
		 * @param string        $capability Capability being checked.
		 * @param WC_Order|null $context    Per-order context, or null for gateway-global checks.
		 */
		$resolved = apply_filters( self::CAPABILITY_CHECK_FILTER, $current, $gateway_id, $capability, $context );

		return (bool) $resolved;
	}

	/**
	 * Read the live gateway instance's `$supports` array (resolution step 2).
	 *
	 * WooCommerce initializes its gateway registry on `woocommerce_loaded`, so
	 * the lookup is empty for callers running before that fires. That is not an
	 * error: "WooCommerce is not loaded yet" reduces cleanly to "the gateway
	 * does not claim that capability via the instance path."
	 *
	 * @param string $gateway_id Gateway identifier.
	 * @param string $capability Capability flag.
	 */
	private static function gateway_instance_supports( string $gateway_id, string $capability ): bool {
		if ( ! function_exists( 'WC' ) ) {
			return false;
		}

		$wc = WC();
		if ( ! is_object( $wc ) || ! method_exists( $wc, 'payment_gateways' ) ) {
			return false;
		}

		$registry = $wc->payment_gateways();
		if ( ! $registry instanceof WC_Payment_Gateways ) {
			return false;
		}

		// `payment_gateways()` returns an associative array keyed by gateway id;
		// it is empty until the gateway registry initializes.
		$gateways = $registry->payment_gateways();
		$gateway  = $gateways[ $gateway_id ] ?? null;

		if ( ! $gateway instanceof WC_Payment_Gateway ) {
			return false;
		}

		// `$supports` is documented as an array in core; defensively narrow
		// non-array values so a misconfigured gateway does not throw out of
		// `in_array`.
		$supports = is_array( $gateway->supports ) ? $gateway->supports : array();

		return in_array( $capability, $supports, true );
	}
}
