<?php
/**
 * Contract_Status - the contract lifecycle state machine.
 *
 * Owns the set of valid statuses and the allowed transitions between them.
 * Status transitions are validated here and applied by the {@see Contract} entity.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Entity
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Entity;

defined( 'ABSPATH' ) || exit;

/**
 * Contract_Status value/helper class.
 */
final class Contract_Status {

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
	 * @param string $from Current status.
	 * @param string $to   Target status.
	 */
	public static function can_transition( string $from, string $to ): bool {
		if ( ! self::is_valid( $from ) || ! self::is_valid( $to ) ) {
			return false;
		}

		return in_array( $to, self::transitions()[ $from ], true );
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
