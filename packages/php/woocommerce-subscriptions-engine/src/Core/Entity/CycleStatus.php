<?php
/**
 * CycleStatus - a cycle status as an immutable value object, plus read helpers
 * over the {@see StatusRegistry}. Mirrors {@see ContractStatus}.
 *
 * Cycle status is opaque engine data. The constants name the engine defaults:
 * the defaults are shared slugs and carry no engine meaning; the engine enforces
 * no transitions. Extensions may register more through
 * {@see StatusRegistry::register()}. The slugs are shared with the shipping chain,
 * so `processing` avoids payment-specific wording.
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
 * Immutable: `new CycleStatus( CycleStatus::PENDING )`. The constructor checks the slug
 * format only, so a stored status a since-deactivated extension wrote still loads;
 * whether a status is registered is checked where a cycle status is written
 * ({@see Cycle::create()}, {@see Cycle::set_status()}, the repository's status
 * compare-and-set).
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
	 * Wrap a status slug.
	 *
	 * @param string $value Status slug.
	 * @throws DomainException If `$value` is not a well-formed status slug.
	 */
	public function __construct( string $value ) {
		if ( ! self::is_valid( $value ) ) {
			throw new DomainException(
				sprintf( 'CycleStatus: "%s" is not a valid status slug.', $value )
			);
		}

		$this->value = $value;
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
	 * The engine's default cycle statuses (the registry seed).
	 *
	 * @return array<int, string>
	 */
	public static function get_defaults(): array {
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
	public static function get_all(): array {
		return StatusRegistry::get_all( StatusRegistry::KIND_CYCLE );
	}

	/**
	 * Whether `$status` is a registered cycle status (an engine default or an extension
	 * registration). Write paths accept only registered statuses.
	 *
	 * @param string $status Status to check.
	 */
	public static function is_registered( string $status ): bool {
		return StatusRegistry::is_registered( StatusRegistry::KIND_CYCLE, $status );
	}

	/**
	 * Whether `$status` is a well-formed status slug (lowercase letters and digits in
	 * words joined by single hyphens, at most 20 characters), registered or not.
	 *
	 * @param string $status Status to check.
	 */
	public static function is_valid( string $status ): bool {
		return StatusRegistry::is_valid_slug( $status );
	}

	/**
	 * Whether `$status` is `billed` or `cancelled`.
	 *
	 * Interim: moves out of the engine with the renewal flow (only the repository's
	 * snapshot skip in `hydrate_cycle()` reads this). Unknown and
	 * extension-registered statuses report false.
	 *
	 * @param string $status Status to check.
	 */
	public static function is_terminal( string $status ): bool {
		return in_array( $status, array( self::BILLED, self::CANCELLED ), true );
	}
}
