<?php
/**
 * CycleStatus - a cycle status as an immutable value object, plus read helpers
 * over the {@see StatusRegistry}. Mirrors {@see ContractStatus}.
 *
 * Cycle status is opaque engine data with no enforced transitions. The
 * constants name the engine defaults, whose intended lifecycle is: a cycle is
 * born `pending`; a charge submitted to a gateway that has not yet returned an
 * outcome is `processing`; it settles to `billed` or `failed`; a `failed` cycle
 * may be retried back to `pending`, and an unsettled cycle may be `cancelled`.
 * That lifecycle is driven by the flows, not enforced here. The state is shared
 * with the shipping chain, so `processing` avoids payment-specific wording.
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
 * {@see self::from()}; storage hydration uses {@see self::stored()}.
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
	 * Use a named factory ({@see self::pending()} etc.), {@see self::from()} or
	 * {@see self::stored()}.
	 *
	 * @param string $value Status string.
	 */
	private function __construct( string $value ) {
		$this->value = $value;
	}

	/**
	 * Build a status value from a registered status string.
	 *
	 * @param string $value Status string.
	 * @throws DomainException If `$value` is not a registered cycle status.
	 */
	public static function from( string $value ): self {
		if ( ! self::is_valid( $value ) ) {
			throw new DomainException(
				sprintf( 'CycleStatus: "%s" is not a registered status.', $value )
			);
		}

		return new self( $value );
	}

	/**
	 * Build a status value from a persisted string without validation.
	 *
	 * Storage hydration only: a value written by a since-deactivated extension
	 * must round-trip unchanged rather than fail the read.
	 *
	 * @internal Not part of the consumer API; writes go through {@see self::from()}.
	 *
	 * @param string $value Stored status string.
	 */
	public static function stored( string $value ): self {
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
	 * The engine's default cycle statuses in lifecycle order (the registry seed).
	 *
	 * @return array<int, string>
	 */
	public static function defaults(): array {
		return array(
			self::PENDING,
			self::PROCESSING,
			self::BILLED,
			self::FAILED,
			self::CANCELLED,
		);
	}

	/**
	 * Every registered cycle status: the engine defaults, then extension
	 * registrations.
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		return StatusRegistry::all( StatusRegistry::KIND_CYCLE );
	}

	/**
	 * Whether `$status` is a registered cycle status.
	 *
	 * @param string $status Status to check.
	 */
	public static function is_valid( string $status ): bool {
		return StatusRegistry::is_registered( StatusRegistry::KIND_CYCLE, $status );
	}

	/**
	 * Whether `$status` is `billed` or `cancelled`.
	 *
	 * Interim: only the repository's snapshot skip in `hydrate_cycle()` reads
	 * this, and it goes away when the renewal flow leaves the engine. Unknown
	 * and extension-registered statuses report false.
	 *
	 * @param string $status Status to check.
	 */
	public static function is_terminal( string $status ): bool {
		return in_array( $status, array( self::BILLED, self::CANCELLED ), true );
	}
}
