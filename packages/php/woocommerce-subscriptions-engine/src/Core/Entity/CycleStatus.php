<?php
/**
 * CycleStatus - the cycle lifecycle state machine, as an immutable value object.
 * Owns the valid statuses and allowed transitions so an invalid state cannot be
 * represented. Mirrors {@see ContractStatus}.
 *
 * Lifecycle: a cycle is born `pending`; a charge submitted to a gateway that has not
 * yet returned a terminal outcome (an async method awaiting confirmation) is
 * `processing`; it settles to `billed` (terminal) or `failed`. A `failed` cycle can be
 * retried back to `pending` (an admin-triggered re-attempt), and any non-settled cycle
 * can be `cancelled` (terminal). The state is shared with the shipping chain, so
 * `processing` names "submitted, awaiting a terminal outcome" without payment-specific wording.
 * Instance methods serve the entity; the static string helpers operate on raw strings at
 * the storage boundary.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Entity
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Entity;

use DomainException;

defined( 'ABSPATH' ) || exit;

/**
 * CycleStatus value object.
 *
 * Immutable. Construct via a named factory ({@see self::pending()} etc.) or
 * {@see self::from()}.
 */
final class CycleStatus {

	public const PENDING    = 'pending';
	public const PROCESSING = 'processing';
	public const BILLED     = 'billed';
	public const FAILED     = 'failed';
	public const CANCELLED  = 'cancelled';

	/**
	 * The status string this value wraps.
	 *
	 * @var string
	 */
	private $value;

	/**
	 * Use a named factory ({@see self::pending()} etc.) or {@see self::from()}.
	 *
	 * @param string $value A known status string.
	 */
	private function __construct( string $value ) {
		$this->value = $value;
	}

	/**
	 * Build a status value from a known status string.
	 *
	 * @param string $value Status string.
	 * @throws DomainException If `$value` is not a known status.
	 */
	public static function from( string $value ): self {
		if ( ! self::is_valid( $value ) ) {
			throw new DomainException(
				sprintf( 'CycleStatus: "%s" is not a known status.', $value )
			);
		}

		return new self( $value );
	}

	/**
	 * The `pending` status (charge in flight; values locked at creation).
	 */
	public static function pending(): self {
		return new self( self::PENDING );
	}

	/**
	 * The `processing` status (charge submitted, awaiting a terminal outcome; non-terminal).
	 */
	public static function processing(): self {
		return new self( self::PROCESSING );
	}

	/**
	 * The `billed` status (settled after a successful charge; terminal).
	 */
	public static function billed(): self {
		return new self( self::BILLED );
	}

	/**
	 * The `failed` status (charge declined; non-terminal).
	 */
	public static function failed(): self {
		return new self( self::FAILED );
	}

	/**
	 * The `cancelled` status (closed; terminal).
	 */
	public static function cancelled(): self {
		return new self( self::CANCELLED );
	}

	/**
	 * The wrapped status string (the value stored on the cycle row).
	 */
	public function get_value(): string {
		return $this->value;
	}

	/**
	 * Whether this status is the same as `$other`.
	 *
	 * @param CycleStatus $other Status to compare against.
	 */
	public function equals( CycleStatus $other ): bool {
		return $this->value === $other->value;
	}

	/**
	 * Whether this status may move to `$target`.
	 *
	 * @param CycleStatus $target Target status.
	 */
	public function can_transition_to( CycleStatus $target ): bool {
		return self::is_transition_allowed( $this->value, $target->value );
	}

	/**
	 * Move to `$target`, returning the new status value.
	 *
	 * @param CycleStatus $target Target status.
	 * @throws DomainException If the transition is not allowed.
	 */
	public function transition_to( CycleStatus $target ): self {
		self::assert_transition_allowed( $this->value, $target->value );

		return $target;
	}

	/**
	 * All known statuses, in lifecycle order.
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		return array(
			self::PENDING,
			self::PROCESSING,
			self::BILLED,
			self::FAILED,
			self::CANCELLED,
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
	 * Whether a cycle may move from `$from` to `$to`. Unknown statuses report false.
	 * Same-status calls also report false; {@see Cycle::set_status()} short-circuits
	 * no-ops before consulting this table.
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
	 * Whether a cycle may move from `$from` to `$to`.
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
	 * Throw if `$from` -> `$to` is not an allowed transition. The canonical
	 * enforcement entry point every status change flows through.
	 *
	 * @param string $from Current status.
	 * @param string $to   Target status.
	 * @throws DomainException When the transition is rejected by {@see self::is_transition_allowed()}.
	 */
	public static function assert_transition_allowed( string $from, string $to ): void {
		if ( ! self::is_transition_allowed( $from, $to ) ) {
			throw new DomainException(
				sprintf( 'CycleStatus: illegal status transition from "%s" to "%s".', $from, $to )
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
			self::PENDING    => array( self::PROCESSING, self::BILLED, self::FAILED, self::CANCELLED ),
			self::PROCESSING => array( self::BILLED, self::FAILED, self::CANCELLED ),
			self::BILLED     => array(),
			self::FAILED     => array( self::PENDING, self::CANCELLED ),
			self::CANCELLED  => array(),
		);
	}
}
