<?php
/**
 * StatusRegistry - the set of registered contract and cycle statuses.
 *
 * Statuses are opaque engine data: the engine ships a default set per kind
 * ({@see ContractStatus::get_defaults()}, {@see CycleStatus::get_defaults()}) and
 * extensions may register more. The registry holds slugs only - no labels, no
 * transitions, no meaning - and is global (not per owner). Registration is the
 * write-path allowlist: entity setters and the cycle status write refuse a slug
 * that is not registered. Stored values outside the registry (for example one
 * written by a since-deactivated extension) still hydrate and round-trip
 * unchanged; the registry never gates reads.
 *
 * Core zone: WordPress-free by design. No WP/Woo symbols, no time functions.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Entity
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Entity;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Static registry of status slugs per kind.
 */
final class StatusRegistry {

	/**
	 * Contract status kind.
	 */
	public const KIND_CONTRACT = 'contract';

	/**
	 * Cycle status kind.
	 */
	public const KIND_CYCLE = 'cycle';

	/**
	 * Longest accepted slug (the status columns are `varchar(20)`).
	 */
	private const MAX_LENGTH = 20;

	/**
	 * Slug format: lowercase alphanumeric words joined by single hyphens. Anchored with `\z`
	 * (not `$`, which also matches before a trailing newline).
	 */
	private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*\z/';

	/**
	 * Extension registrations, keyed by kind => list of slugs in registration
	 * order. The engine defaults are intrinsic and never stored here.
	 *
	 * Static (not instance state) because the public registration API is itself
	 * static - every consumer reaches the registry by class name.
	 *
	 * @var array<string, array<int, string>>
	 */
	private static $registered = array();

	/**
	 * Register an extension status slug for `$kind`.
	 *
	 * Idempotent: registering a default or an already-registered slug changes
	 * nothing.
	 *
	 * @param string $kind One of {@see self::KIND_CONTRACT} or {@see self::KIND_CYCLE}.
	 * @param string $slug Status slug; must satisfy {@see self::is_valid_slug()}.
	 * @throws InvalidArgumentException When the kind is unknown or the slug is malformed.
	 */
	public static function register( string $kind, string $slug ): void {
		self::assert_known_kind( $kind );

		if ( ! self::is_valid_slug( $slug ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'StatusRegistry: "%s" is not a valid status slug (lowercase letters, digits and single hyphens, at most %d characters).',
					$slug,
					self::MAX_LENGTH
				)
			);
		}

		if ( in_array( $slug, self::get_all( $kind ), true ) ) {
			return;
		}

		self::$registered[ $kind ][] = $slug;
	}

	/**
	 * Whether `$slug` is a registered status (a default or an extension
	 * registration) for `$kind`.
	 *
	 * @param string $kind One of {@see self::KIND_CONTRACT} or {@see self::KIND_CYCLE}.
	 * @param string $slug Status slug.
	 * @throws InvalidArgumentException When the kind is unknown.
	 */
	public static function is_registered( string $kind, string $slug ): bool {
		return in_array( $slug, self::get_all( $kind ), true );
	}

	/**
	 * Every registered status for `$kind`: the engine defaults first, then
	 * extension registrations in registration order.
	 *
	 * @param string $kind One of {@see self::KIND_CONTRACT} or {@see self::KIND_CYCLE}.
	 * @return array<int, string>
	 * @throws InvalidArgumentException When the kind is unknown.
	 */
	public static function get_all( string $kind ): array {
		self::assert_known_kind( $kind );

		$defaults = self::KIND_CONTRACT === $kind ? ContractStatus::get_defaults() : CycleStatus::get_defaults();

		return array_merge( $defaults, self::$registered[ $kind ] ?? array() );
	}

	/**
	 * Whether `$slug` satisfies the status slug format: lowercase letters and
	 * digits in words joined by single hyphens, at most 20 characters.
	 *
	 * @param string $slug Candidate slug.
	 */
	public static function is_valid_slug( string $slug ): bool {
		return strlen( $slug ) <= self::MAX_LENGTH && 1 === preg_match( self::SLUG_PATTERN, $slug );
	}

	/**
	 * Clear every extension registration. The engine defaults remain.
	 *
	 * @internal Public only so test setUp/tearDown can isolate per-test state.
	 *           Not part of the consumer API.
	 */
	public static function reset(): void {
		self::$registered = array();
	}

	/**
	 * Throw unless `$kind` is a known status kind.
	 *
	 * @param string $kind Kind to check.
	 * @throws InvalidArgumentException When the kind is unknown.
	 */
	private static function assert_known_kind( string $kind ): void {
		if ( self::KIND_CONTRACT !== $kind && self::KIND_CYCLE !== $kind ) {
			throw new InvalidArgumentException(
				sprintf( 'StatusRegistry: unknown status kind "%s".', $kind )
			);
		}
	}
}
