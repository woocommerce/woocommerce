<?php
/**
 * GatewayCapabilities - the pure store of declared gateway capabilities.
 *
 * Payment gateways tell the engine which subscription features they can handle:
 * "I can process recurring charges," "I tolerate variable amounts," "I run my
 * own renewal schedule." This Core class owns the in-memory declarations and
 * the predicate over them; the WordPress-facing entry point lives in
 * {@see \Automattic\WooCommerce\SubscriptionsEngine\Integration\Gateway\CapabilityRegistry},
 * which delegates registration here and layers the live-gateway and filter
 * resolution steps on top.
 *
 * Core zone: WordPress-free by design. No WP/Woo symbols, no time functions.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Pure storage and predicate for gateway capability declarations.
 */
final class GatewayCapabilities {

	/**
	 * Gateway can process engine-scheduled recurring charges.
	 *
	 * The most fundamental flag: an engine-scheduled contract on a gateway
	 * lacking this capability would create renewals nobody runs.
	 */
	public const RECURRING = 'recurring';

	/**
	 * Customer can change the payment method on an active contract. All modern
	 * gateways support this; the flag exists for the rare manual-only case.
	 */
	public const PAYMENT_METHOD_CHANGE = 'payment_method_change';

	/**
	 * Gateway tolerates variable charge amounts (volume-tier upgrades, tax-rate
	 * changes, prorated mid-cycle adjustments). Flag the difference so
	 * amount-changing flows can refuse on an incapable gateway rather than
	 * silently mis-charging.
	 */
	public const AMOUNT_CHANGES = 'amount_changes';

	/**
	 * One customer can hold N active contracts. Default for modern gateways;
	 * the flag exists for single-mandate gateways.
	 */
	public const MULTIPLE_PER_CUSTOMER = 'multiple_per_customer';

	/**
	 * Gateway schedules and fires renewals itself; the engine only tracks
	 * metadata. Pairs with the gateway `schedule_source` at the contract level -
	 * a gateway can declare this capability without every contract on it being
	 * gateway-scheduled.
	 */
	public const GATEWAY_SCHEDULED_RENEWALS = 'gateway_scheduled_renewals';

	/**
	 * In-memory declarations, keyed by gateway id => list of declared capability
	 * flag strings (de-duplicated and reindexed).
	 *
	 * Static (not instance state) because the public registration API is itself
	 * static - every consumer reaches the registry by class name, not via an
	 * injected instance.
	 *
	 * @var array<string, array<int, string>>
	 */
	private static $declarations = array();

	/**
	 * Every recognised capability flag.
	 *
	 * Public so test code, admin tooling, and future capability-matrix UIs can
	 * iterate the canonical list without hard-coding the constants.
	 *
	 * @return array<int, string>
	 */
	public static function known_capabilities(): array {
		return array(
			self::RECURRING,
			self::PAYMENT_METHOD_CHANGE,
			self::AMOUNT_CHANGES,
			self::MULTIPLE_PER_CUSTOMER,
			self::GATEWAY_SCHEDULED_RENEWALS,
		);
	}

	/**
	 * Register a gateway's declared capabilities.
	 *
	 * Each entry in `$capabilities` must be one of the flag constants on this
	 * class; an unknown flag throws so a typo surfaces at registration rather
	 * than silently defaulting to "unsupported" later.
	 *
	 * Re-declaration replaces: if a gateway declares twice, the second call wins
	 * outright - we do not merge. Merging would make "I removed this capability"
	 * impossible to express, and it mirrors the replace-not-merge semantics of
	 * WooCommerce's own feature-compatibility declarations.
	 *
	 * @param string             $gateway_id   Gateway identifier.
	 * @param array<int, string> $capabilities Capability flag strings; each must be one of {@see self::known_capabilities()}.
	 * @throws InvalidArgumentException When `$capabilities` contains a flag not in {@see self::known_capabilities()}.
	 */
	public static function declare( string $gateway_id, array $capabilities ): void {
		$known = self::known_capabilities();
		foreach ( $capabilities as $capability ) {
			if ( ! in_array( $capability, $known, true ) ) {
				throw new InvalidArgumentException(
					sprintf(
						'GatewayCapabilities: unknown capability flag "%s". Expected one of: %s.',
						is_string( $capability ) ? $capability : gettype( $capability ),
						implode( ', ', $known )
					)
				);
			}
		}

		// De-dupe and reindex before storing: the public API documents a "list
		// of strings" shape, so double-declarations should not leak through
		// lookups indistinguishably.
		self::$declarations[ $gateway_id ] = array_values( array_unique( $capabilities ) );
	}

	/**
	 * Whether `$gateway_id` has declared `$capability`.
	 *
	 * A pure predicate over the static declarations only - it does not consult
	 * any live gateway or filter. Returns false for unknown gateway ids and
	 * unknown capability strings; the strict path is {@see self::declare()},
	 * which throws on typos at registration.
	 *
	 * @param string $gateway_id Gateway identifier.
	 * @param string $capability Capability flag.
	 */
	public static function is_declared( string $gateway_id, string $capability ): bool {
		$declared = self::$declarations[ $gateway_id ] ?? array();

		return in_array( $capability, $declared, true );
	}

	/**
	 * Clear every declaration.
	 *
	 * @internal Public only so test setUp can isolate per-test state. Not part
	 *           of the consumer API.
	 */
	public static function reset(): void {
		self::$declarations = array();
	}
}
