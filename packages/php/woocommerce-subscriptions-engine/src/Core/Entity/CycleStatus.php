<?php
/**
 * CycleStatus - the cycle lifecycle state machine, as a value object.
 *
 * Owns the set of valid cycle statuses and the allowed transitions between them.
 * A {@see Cycle} holds its status as a `CycleStatus` instance (not a raw string),
 * so an invalid state cannot be represented and transitions are validated by the
 * value itself. Mirrors {@see ContractStatus}'s transition logic.
 *
 * Two surfaces coexist: instance methods (used by the entity - construct with the
 * named factories {@see self::pending()} etc. or {@see self::from()}, compare
 * with {@see self::equals()}, move with {@see self::transition_to()}) and the
 * string-keyed static helpers ({@see self::all()}, {@see self::is_valid()}, ...)
 * that operate on the raw status strings used at the storage boundary.
 *
 * Charge lifecycle: a cycle is born `pending` when it bills (its values locked
 * at creation), then settles to `billed` on a successful charge or `failed` on a
 * declined one. Any non-settled cycle can be `cancelled`. Retry of a `failed`
 * cycle is a follow-up change, so for now a failed cycle can only be cancelled.
 *
 * Terminal for the cycle row are `billed` and `cancelled`: a billed cycle's
 * charge is done and a new cycle is appended next, while a cancelled cycle is
 * closed. `failed` is intentionally non-terminal so a follow-up change can add a
 * retry edge without reshaping the status set.
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

	const PENDING   = 'pending';
	const BILLED    = 'billed';
	const FAILED    = 'failed';
	const CANCELLED = 'cancelled';

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
	 * Whether a cycle may move from `$from` to `$to`.
	 *
	 * Unknown source or target statuses are reported as not allowed, so a row
	 * that has drifted into an unrecognized state cannot be transitioned out of
	 * a value we do not know how to reason about. Same-status calls
	 * (`pending` -> `pending`) report false here; {@see Cycle::set_status()}
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
			self::PENDING   => array( self::BILLED, self::FAILED, self::CANCELLED ),
			self::BILLED    => array(),
			self::FAILED    => array( self::CANCELLED ),
			self::CANCELLED => array(),
		);
	}
}
