<?php
/**
 * ConsumerRegistry - the set of extensions consuming engine functionality.
 *
 * A consumer extension registers its slug here on load, identifying itself as an
 * active owner of the primitives it drives. Engine components read the registry to
 * decide whether, and on whose behalf, to act: an empty registry means "no consumer
 * present," so the engine stays inert when bundled but unused - the renewal
 * dispatcher charges nothing, and future components gate the same way.
 *
 * Static (not instance state) because registration is a load-time, by-class-name
 * call - every consumer reaches the registry by class name, mirroring
 * {@see \Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities}.
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
	 * Remove a consumer registration. A deactivating consumer deregisters itself so the
	 * engine's gates re-evaluate while its code is still loaded - the engine only loads
	 * through its consumers, so the deactivation request is the last chance for components
	 * to clean up (e.g. the renewal dispatcher removes its recurring scan when the last
	 * consumer leaves). An unknown slug is a no-op.
	 *
	 * @param string $slug The consumer extension's registered slug.
	 */
	public static function unregister( string $slug ): void {
		unset( self::$slugs[ trim( $slug ) ] );
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
