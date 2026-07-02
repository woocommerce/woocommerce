<?php
/**
 * ConsumerRegistry - the set of extensions consuming engine functionality.
 *
 * A consumer extension registers its slug here on load, identifying itself as an
 * active owner of the primitives it drives. The engine reads the registry to
 * decide whether, and on whose behalf, to act - the batch renewal dispatcher, for
 * one, treats an empty registry as "no consumer present, charge nothing," so the
 * engine stays inert when bundled but unused.
 *
 * Static (not instance state) because registration is a load-time, by-class-name
 * call - every consumer reaches the registry by class name, mirroring
 * {@see \Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities}.
 *
 * Integration zone: WordPress-native (registered from a consumer's boot).
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership;

defined( 'ABSPATH' ) || exit;

/**
 * Registry of the extensions consuming engine functionality.
 */
final class ConsumerRegistry {

	/**
	 * Registered consumer slugs, de-duplicated (a set keyed by slug).
	 *
	 * @var array<string, true>
	 */
	private static $slugs = array();

	/**
	 * Register a consumer extension by its slug.
	 *
	 * Idempotent: registering the same slug twice is a no-op. The slug is trimmed, and
	 * an empty or whitespace-only slug is ignored so a misconfigured caller cannot flip
	 * the gate open with a blank registration.
	 *
	 * @param string $slug The consumer extension's registered slug.
	 */
	public static function register( string $slug ): void {
		$slug = trim( $slug );
		if ( '' === $slug ) {
			return;
		}

		self::$slugs[ $slug ] = true;
	}

	/**
	 * Whether no consumer is registered. The dispatcher gate: true means charge
	 * nothing this run.
	 */
	public static function is_empty(): bool {
		return array() === self::$slugs;
	}

	/**
	 * The registered consumer slugs (order not significant).
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		return array_keys( self::$slugs );
	}

	/**
	 * Clear every registration.
	 *
	 * @internal Public only so test setUp can isolate per-test state. Not part
	 *           of the consumer API.
	 */
	public static function reset(): void {
		self::$slugs = array();
	}
}
