<?php
/**
 * ContractStatus - the contract lifecycle state machine.
 *
 * Owns the set of valid statuses and the allowed transitions between them.
 * Status transitions are validated here and applied by the {@see Contract} entity.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Entity
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Entity;

use DomainException;

defined( 'ABSPATH' ) || exit;

/**
 * ContractStatus value/helper class.
 */
final class ContractStatus {

	const ACTIVE               = 'active';
	const ON_HOLD              = 'on-hold';
	const PENDING_CANCELLATION = 'pending-cancellation';
	const CANCELLED            = 'cancelled';
	const EXPIRED              = 'expired';

	/**
	 * All known statuses.
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		return array(
			self::ACTIVE,
			self::ON_HOLD,
			self::PENDING_CANCELLATION,
			self::CANCELLED,
			self::EXPIRED,
		);
	}

	/**
	 * Whether `$status` is a known status.
	 *
	 * @param string $status Status to check.
	 */
	public static function is_valid( string $status ): bool {
		return in_array( $status, self::all(), true );
	}

	/**
	 * Whether `$status` is terminal (no transitions out).
	 *
	 * @param string $status Status to check.
	 */
	public static function is_terminal( string $status ): bool {
		return self::is_valid( $status ) && array() === self::transitions()[ $status ];
	}

	/**
	 * Whether a contract may move from `$from` to `$to`.
	 *
	 * Unknown source or target statuses are reported as not allowed, so a row
	 * that has drifted into an unrecognized state cannot be transitioned out of
	 * a value we do not know how to reason about. Same-status calls
	 * (`active` -> `active`) report false here; {@see Contract::set_status()}
	 * short-circuits no-ops before consulting this table so they do not surface
	 * as exceptions to callers.
	 *
	 * @param string $from Current status.
	 * @param string $to   Target status.
	 */
	public static function is_transition_allowed( string $from, string $to ): bool {
		if ( ! self::is_valid( $from ) || ! self::is_valid( $to ) ) {
			return false;
		}

		return in_array( $to, self::transitions()[ $from ], true );
	}

	/**
	 * Whether a contract may move from `$from` to `$to`.
	 *
	 * Alias of {@see self::is_transition_allowed()}.
	 *
	 * @param string $from Current status.
	 * @param string $to   Target status.
	 */
	public static function can_transition( string $from, string $to ): bool {
		return self::is_transition_allowed( $from, $to );
	}

	/**
	 * Throw if `$from` -> `$to` is not an allowed transition.
	 *
	 * The canonical enforcement entry point: every status change flows through
	 * here before the new status is applied, which makes "no nonsense states" a
	 * structural guarantee rather than a code-review aspiration.
	 *
	 * @param string $from Current status.
	 * @param string $to   Target status.
	 * @throws DomainException When the transition is rejected by {@see self::is_transition_allowed()}.
	 */
	public static function assert_transition_allowed( string $from, string $to ): void {
		if ( ! self::is_transition_allowed( $from, $to ) ) {
			throw new DomainException(
				sprintf( 'ContractStatus: illegal status transition from "%s" to "%s".', $from, $to )
			);
		}
	}

	/**
	 * Allowed transitions: current status => list of reachable statuses.
	 *
	 * @return array<string, array<int, string>>
	 */
	private static function transitions(): array {
		return array(
			self::ACTIVE               => array( self::ON_HOLD, self::PENDING_CANCELLATION, self::CANCELLED, self::EXPIRED ),
			self::ON_HOLD              => array( self::ACTIVE, self::PENDING_CANCELLATION, self::CANCELLED ),
			self::PENDING_CANCELLATION => array( self::ACTIVE, self::CANCELLED ),
			self::CANCELLED            => array(),
			self::EXPIRED              => array(),
		);
	}
}
