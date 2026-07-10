<?php
/**
 * BillingPolicy - typed value object for a plan's billing cadence and trial.
 *
 * Mirrors the `billing_policy` JSON column shape. Shape:
 *   {
 *     period:         'day' | 'week' | 'month' | 'year',
 *     interval:       int,
 *     min_cycles:     ?int,
 *     max_cycles:     ?int,
 *     trial_duration: { length: int, unit: 'day'|'week'|'month'|'year' } | null
 *   }
 *
 * Trial is a native field: the first cycle's billing date is delayed by the
 * trial at contract creation rather than modelled as a discount.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject;

use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ScalarCoercion;

defined( 'ABSPATH' ) || exit;

/**
 * BillingPolicy value object.
 *
 * Immutable. Construct via {@see self::from_array()} when hydrating from a
 * stored row, or via the constructor when building one in code.
 */
final class BillingPolicy {

	/**
	 * Period unit: 'day' | 'week' | 'month' | 'year'.
	 *
	 * @var string
	 */
	private $period;

	/**
	 * Period count (e.g. 2 with 'week' = every 2 weeks).
	 *
	 * @var int
	 */
	private $interval;

	/**
	 * Minimum cycles before cancellation is allowed; null if unbounded below.
	 *
	 * @var int|null
	 */
	private $min_cycles;

	/**
	 * Total cycles before the contract ends; null if open-ended.
	 *
	 * @var int|null
	 */
	private $max_cycles;

	/**
	 * Native trial; null if no trial.
	 *
	 * @var array{length: int, unit: string}|null
	 */
	private $trial_duration;

	/**
	 * Build a billing policy.
	 *
	 * @param string                                $period         One of 'day' | 'week' | 'month' | 'year'.
	 * @param int                                   $interval       Period count.
	 * @param int|null                              $min_cycles     Minimum cycles before cancellation is allowed.
	 * @param int|null                              $max_cycles     Total cycles before the contract ends.
	 * @param array{length: int, unit: string}|null $trial_duration Native trial; null if none.
	 */
	public function __construct( string $period, int $interval, ?int $min_cycles, ?int $max_cycles, ?array $trial_duration ) {
		$this->validate_min_max_cycles( $min_cycles, $max_cycles );

		$this->period         = $period;
		$this->interval       = $interval;
		$this->min_cycles     = $min_cycles;
		$this->max_cycles     = $max_cycles;
		$this->trial_duration = self::normalize_trial_duration( $trial_duration );
	}

	/**
	 * Hydrate from the JSON-decoded `billing_policy` column shape.
	 *
	 * Missing nullable keys default to null. `period` and `interval` are required.
	 *
	 * @param array<string, mixed> $data Decoded billing_policy row.
	 * @throws DomainException If the data is not valid.
	 */
	public static function from_array( array $data ): self {
		if ( ! array_key_exists( 'period', $data ) ) {
			throw new DomainException( 'BillingPolicy: period is required, but not supplied.' );
		}
		if ( ! array_key_exists( 'interval', $data ) ) {
			throw new DomainException( 'BillingPolicy: interval is required, but not supplied.' );
		}
		if ( ! is_string( $data['period'] ) ) {
			throw new DomainException( 'BillingPolicy: period must be a string, got ' . gettype( $data['period'] ) . '.' );
		}
		if ( ! is_int( $data['interval'] ) ) {
			throw new DomainException( 'BillingPolicy: interval must be an integer, got ' . gettype( $data['interval'] ) . '.' );
		}

		$trial = $data['trial_duration'] ?? null;
		if ( null !== $trial && ! is_array( $trial ) ) {
			throw new DomainException(
				sprintf( 'BillingPolicy: trial_duration must be null or an array, got %s.', gettype( $trial ) )
			);
		}

		$trial = self::normalize_trial_duration( $trial );

		return new self(
			(string) $data['period'],
			(int) $data['interval'],
			ScalarCoercion::coerce_nullable_int( $data['min_cycles'] ?? null ),
			ScalarCoercion::coerce_nullable_int( $data['max_cycles'] ?? null ),
			$trial
		);
	}

	/**
	 * Period unit: 'day' | 'week' | 'month' | 'year'.
	 */
	public function get_period(): string {
		return $this->period;
	}

	/**
	 * Period count. Together with `period` defines cadence.
	 */
	public function get_interval(): int {
		return $this->interval;
	}

	/**
	 * Minimum cycles before cancellation is allowed; null if unbounded below.
	 */
	public function get_min_cycles(): ?int {
		return $this->min_cycles;
	}

	/**
	 * Total cycles before the contract ends; null if open-ended.
	 */
	public function get_max_cycles(): ?int {
		return $this->max_cycles;
	}

	/**
	 * Native trial duration, or null if no trial.
	 *
	 * @return array{length: int, unit: string}|null
	 */
	public function get_trial_duration(): ?array {
		return $this->trial_duration;
	}

	/**
	 * Compute the next renewal moment by adding the policy's cadence to `$anchor`.
	 *
	 * Trial duration is not applied here. The result is normalized to UTC and
	 * period semantics are calendar-aware (matching DateTimeImmutable::modify()):
	 * adding 1 month to 2026-01-31 yields 2026-03-03, not 2026-02-31.
	 *
	 * @param DateTimeImmutable $anchor The moment the next cycle is computed from.
	 * @return DateTimeImmutable The next renewal moment in UTC.
	 * @throws DomainException If `period` is unknown or `interval` is not positive.
	 */
	public function compute_next_renewal_from( DateTimeImmutable $anchor ): DateTimeImmutable {
		if ( $this->interval <= 0 ) {
			throw new DomainException(
				sprintf( 'BillingPolicy::compute_next_renewal_from(): interval must be positive, got %d.', $this->interval )
			);
		}

		$unit = $this->normalize_unit( $this->period, 'period' );
		$utc  = $anchor->setTimezone( new DateTimeZone( 'UTC' ) );

		return $utc->modify( sprintf( '+%d %s', $this->interval, $unit ) );
	}

	/**
	 * Compute the first renewal moment for a freshly-created contract.
	 *
	 * Honours the policy's native trial: when set, the first cycle's billing
	 * date is the end of the trial. With no trial this delegates to
	 * {@see self::compute_next_renewal_from()} so there is one cadence-math path.
	 *
	 * @param DateTimeImmutable $contract_start Moment the contract was created.
	 * @return DateTimeImmutable The first renewal moment in UTC.
	 * @throws DomainException If trial length is not positive or trial unit is unknown.
	 */
	public function compute_first_renewal_from( DateTimeImmutable $contract_start ): DateTimeImmutable {
		if ( null === $this->trial_duration ) {
			return $this->compute_next_renewal_from( $contract_start );
		}

		$length = (int) $this->trial_duration['length'];
		$unit   = (string) $this->trial_duration['unit'];

		if ( $length <= 0 ) {
			throw new DomainException(
				sprintf( 'BillingPolicy::compute_first_renewal_from(): trial length must be positive, got %d.', $length )
			);
		}

		$normalized_unit = $this->normalize_unit( $unit, 'trial unit' );

		return $contract_start
			->setTimezone( new DateTimeZone( 'UTC' ) )
			->modify( sprintf( '+%d %s', $length, $normalized_unit ) );
	}

	/**
	 * Serialize back to the JSON column shape. Lossless round-trip with from_array().
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'period'         => $this->period,
			'interval'       => $this->interval,
			'min_cycles'     => $this->min_cycles,
			'max_cycles'     => $this->max_cycles,
			'trial_duration' => $this->trial_duration,
		);
	}

	/**
	 * Validate and pass through a period/trial unit.
	 *
	 * @param string $unit  The raw unit.
	 * @param string $label Where the unit came from, for the error message.
	 * @throws DomainException If the unit is not one of day/week/month/year.
	 */
	private function normalize_unit( string $unit, string $label ): string {
		if ( ! in_array( $unit, array( 'day', 'week', 'month', 'year' ), true ) ) {
			throw new DomainException(
				sprintf( 'BillingPolicy: invalid %s "%s".', $label, $unit )
			);
		}

		return $unit;
	}

	/**
	 * Validate the min_cycles and max_cycles fields.
	 *
	 * @param int|null $min_cycles Minimum cycles before cancellation is allowed.
	 * @param int|null $max_cycles Total cycles before the contract ends.
	 * @throws DomainException If min_cycles or max_cycles are not valid.
	 */
	private function validate_min_max_cycles( ?int $min_cycles, ?int $max_cycles ): void {
		if ( null !== $min_cycles && $min_cycles < 0 ) {
			throw new DomainException(
				sprintf( 'BillingPolicy: min_cycles must be 0 or greater, got %d.', $min_cycles )
			);
		}

		if ( null !== $max_cycles && $max_cycles < 0 ) {
			throw new DomainException(
				sprintf( 'BillingPolicy: max_cycles must be 0 or greater, got %d.', $max_cycles )
			);
		}

		if ( null !== $min_cycles && null !== $max_cycles && $min_cycles > $max_cycles ) {
			throw new DomainException(
				sprintf( 'BillingPolicy: min_cycles cannot exceed max_cycles, got %d and %d.', $min_cycles, $max_cycles )
			);
		}
	}

	/**
	 * Normalize the trial duration.
	 *
	 * @param array<array-key, mixed>|null $trial_duration The trial duration.
	 * @return array{length: int, unit: string}|null The normalized trial duration.
	 * @throws DomainException If the trial duration is not valid.
	 */
	private static function normalize_trial_duration( ?array $trial_duration ): ?array {
		if ( null === $trial_duration ) {
			return null;
		}

		if ( ! array_key_exists( 'length', $trial_duration ) ) {
			throw new DomainException( "BillingPolicy: trial_duration['length'] is required." );
		}
		if ( ! array_key_exists( 'unit', $trial_duration ) ) {
			throw new DomainException( "BillingPolicy: trial_duration['unit'] is required." );
		}
		if ( ! is_int( $trial_duration['length'] ) ) {
			throw new DomainException(
				sprintf( "BillingPolicy: trial_duration['length'] must be an integer, got %s.", gettype( $trial_duration['length'] ) )
			);
		}
		if ( ! is_string( $trial_duration['unit'] ) ) {
			throw new DomainException(
				sprintf( "BillingPolicy: trial_duration['unit'] must be a string, got %s.", gettype( $trial_duration['unit'] ) )
			);
		}

		return array(
			'length' => (int) $trial_duration['length'],
			'unit'   => (string) $trial_duration['unit'],
		);
	}
}
