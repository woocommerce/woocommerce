<?php
/**
 * BillingPolicy - an optional parser for a billing cadence and trial payload.
 *
 * An extension may use {@see self::from_array()} to parse its plan billing arrays and
 * compute due moments from them. The engine stores plan policies opaquely and does not
 * read them itself. A policy always has a usable cadence: construction refuses an unknown
 * period or a non-positive interval. The array shape it parses:
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
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\Coercion;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\InvalidInputException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ValidationErrorCode;

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
	 * Build a billing policy. Every invalid value is reported in one exception.
	 *
	 * @param string                                $period         One of 'day' | 'week' | 'month' | 'year'.
	 * @param int                                   $interval       Period count.
	 * @param int|null                              $min_cycles     Minimum cycles before cancellation is allowed.
	 * @param int|null                              $max_cycles     Total cycles before the contract ends.
	 * @param array{length: int, unit: string}|null $trial_duration Native trial; null if none.
	 * @throws InvalidInputException If the period is unknown, the interval is not positive, the cycle bounds or the trial shape are invalid.
	 */
	public function __construct( string $period, int $interval, ?int $min_cycles, ?int $max_cycles, ?array $trial_duration ) {
		self::throw_errors(
			array_merge(
				array(
					self::get_period_error( $period ),
					self::get_interval_error( $interval ),
				),
				self::get_cycle_errors( $min_cycles, $max_cycles ),
				self::get_trial_errors( $trial_duration )
			)
		);

		$this->period         = $period;
		$this->interval       = $interval;
		$this->min_cycles     = $min_cycles;
		$this->max_cycles     = $max_cycles;
		$this->trial_duration = null === $trial_duration ? null : array(
			'length' => (int) $trial_duration['length'],
			'unit'   => (string) $trial_duration['unit'],
		);
	}

	/**
	 * Hydrate from the JSON-decoded `billing_policy` column shape.
	 *
	 * Missing nullable keys default to null. `period` and `interval` are required, and
	 * must form a usable cadence (see the constructor). Every invalid value is reported
	 * in one exception.
	 *
	 * @param array<string, mixed> $data Decoded billing_policy row.
	 * @throws InvalidInputException If the data is not valid.
	 */
	public static function from_array( array $data ): self {
		$min_cycles = Coercion::coerce_nullable_int( $data['min_cycles'] ?? null );
		$max_cycles = Coercion::coerce_nullable_int( $data['max_cycles'] ?? null );
		$trial      = $data['trial_duration'] ?? null;

		self::throw_errors(
			array_merge(
				array(
					array_key_exists( 'period', $data )
						? self::get_period_error( $data['period'] )
						: new InvalidInputException( ValidationErrorCode::REQUIRED, 'period', 'BillingPolicy: period is required, but not supplied.' ),
					array_key_exists( 'interval', $data )
						? self::get_interval_error( $data['interval'] )
						: new InvalidInputException( ValidationErrorCode::REQUIRED, 'interval', 'BillingPolicy: interval is required, but not supplied.' ),
				),
				self::get_cycle_errors( $min_cycles, $max_cycles ),
				array( self::get_trial_duration_error( $trial ) ),
				is_array( $trial ) ? self::get_trial_errors( $trial ) : array()
			)
		);

		return new self(
			Coercion::coerce_string( $data['period'] ),
			Coercion::coerce_int( $data['interval'] ),
			$min_cycles,
			$max_cycles,
			is_array( $trial ) ? array(
				'length' => Coercion::coerce_int( $trial['length'] ?? null ),
				'unit'   => Coercion::coerce_string( $trial['unit'] ?? null ),
			) : null
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
	 */
	public function compute_next_renewal_from( DateTimeImmutable $anchor ): DateTimeImmutable {
		$utc = $anchor->setTimezone( new DateTimeZone( 'UTC' ) );

		return $utc->modify( sprintf( '+%d %s', $this->interval, $this->period ) );
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
	 * @throws InvalidInputException If the trial length is not positive or the trial unit is unknown.
	 */
	public function compute_first_renewal_from( DateTimeImmutable $contract_start ): DateTimeImmutable {
		if ( null === $this->trial_duration ) {
			return $this->compute_next_renewal_from( $contract_start );
		}

		$length = (int) $this->trial_duration['length'];
		$unit   = (string) $this->trial_duration['unit'];

		if ( $length <= 0 ) {
			throw new InvalidInputException(
				ValidationErrorCode::OUT_OF_BOUNDS,
				'trial_duration.length',
				sprintf( 'BillingPolicy::compute_first_renewal_from(): trial length must be positive, got %d.', $length ),
				array( 'min' => 1 )
			);
		}

		$unit_error = self::get_unit_error( $unit, 'trial_duration.unit' );
		if ( null !== $unit_error ) {
			throw $unit_error;
		}

		return $contract_start
			->setTimezone( new DateTimeZone( 'UTC' ) )
			->modify( sprintf( '+%d %s', $length, $unit ) );
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
	 * The error for a unit that is not one of day/week/month/year, or null.
	 *
	 * @param string $unit  The raw unit.
	 * @param string $field Field the unit came from, in dot notation.
	 */
	private static function get_unit_error( string $unit, string $field ): ?InvalidInputException {
		$allowed = array( 'day', 'week', 'month', 'year' );
		if ( ! in_array( $unit, $allowed, true ) ) {
			return new InvalidInputException(
				ValidationErrorCode::NOT_IN_ENUM,
				$field,
				sprintf( 'BillingPolicy: invalid %s "%s".', $field, $unit ),
				array( 'allowed' => $allowed )
			);
		}

		return null;
	}

	/**
	 * Throw the non-null errors at once; do nothing when there are none.
	 *
	 * @param array<int, InvalidInputException|null> $errors Errors, null where a check passed.
	 * @throws InvalidInputException If an error is present.
	 */
	private static function throw_errors( array $errors ): void {
		$errors = array_values( array_filter( $errors ) );
		if ( array() !== $errors ) {
			throw InvalidInputException::from_errors( $errors );
		}
	}

	/**
	 * The error for a period that is not a string or not a known unit, or null.
	 *
	 * @param mixed $period Candidate period.
	 */
	private static function get_period_error( $period ): ?InvalidInputException {
		if ( ! is_string( $period ) ) {
			return new InvalidInputException(
				ValidationErrorCode::INVALID_TYPE,
				'period',
				'BillingPolicy: period must be a string, got ' . gettype( $period ) . '.',
				array( 'type' => 'string' )
			);
		}

		return self::get_unit_error( $period, 'period' );
	}

	/**
	 * The error for an interval that is not a positive integer, or null.
	 *
	 * @param mixed $interval Candidate interval.
	 */
	private static function get_interval_error( $interval ): ?InvalidInputException {
		if ( ! is_int( $interval ) ) {
			return new InvalidInputException(
				ValidationErrorCode::INVALID_TYPE,
				'interval',
				'BillingPolicy: interval must be an integer, got ' . gettype( $interval ) . '.',
				array( 'type' => 'integer' )
			);
		}
		if ( $interval <= 0 ) {
			return new InvalidInputException(
				ValidationErrorCode::OUT_OF_BOUNDS,
				'interval',
				sprintf( 'BillingPolicy: interval must be positive, got %d.', $interval ),
				array( 'min' => 1 )
			);
		}

		return null;
	}

	/**
	 * The cycle bound errors: each bound's own, then `min_cycles` above `max_cycles` only
	 * when both passed theirs.
	 *
	 * @param int|null $min_cycles Minimum cycles before cancellation is allowed.
	 * @param int|null $max_cycles Total cycles before the contract ends.
	 * @return array<int, InvalidInputException|null>
	 */
	private static function get_cycle_errors( ?int $min_cycles, ?int $max_cycles ): array {
		$min_error = self::get_min_cycles_error( $min_cycles );
		$max_error = self::get_max_cycles_error( $max_cycles );
		if ( null !== $min_error || null !== $max_error ) {
			return array( $min_error, $max_error );
		}

		return array( self::get_cycle_bounds_error( $min_cycles, $max_cycles ) );
	}

	/**
	 * The error for a negative `min_cycles`, or null.
	 *
	 * @param int|null $min_cycles Minimum cycles before cancellation is allowed.
	 */
	private static function get_min_cycles_error( ?int $min_cycles ): ?InvalidInputException {
		if ( null !== $min_cycles && $min_cycles < 0 ) {
			return new InvalidInputException(
				ValidationErrorCode::OUT_OF_BOUNDS,
				'min_cycles',
				sprintf( 'BillingPolicy: min_cycles must be 0 or greater, got %d.', $min_cycles ),
				array( 'min' => 0 )
			);
		}

		return null;
	}

	/**
	 * The error for a negative `max_cycles`, or null.
	 *
	 * @param int|null $max_cycles Total cycles before the contract ends.
	 */
	private static function get_max_cycles_error( ?int $max_cycles ): ?InvalidInputException {
		if ( null !== $max_cycles && $max_cycles < 0 ) {
			return new InvalidInputException(
				ValidationErrorCode::OUT_OF_BOUNDS,
				'max_cycles',
				sprintf( 'BillingPolicy: max_cycles must be 0 or greater, got %d.', $max_cycles ),
				array( 'min' => 0 )
			);
		}

		return null;
	}

	/**
	 * The error for `min_cycles` above `max_cycles`, or null.
	 *
	 * @param int|null $min_cycles Minimum cycles before cancellation is allowed.
	 * @param int|null $max_cycles Total cycles before the contract ends.
	 */
	private static function get_cycle_bounds_error( ?int $min_cycles, ?int $max_cycles ): ?InvalidInputException {
		if ( null !== $min_cycles && null !== $max_cycles && $min_cycles > $max_cycles ) {
			return new InvalidInputException(
				ValidationErrorCode::OUT_OF_BOUNDS,
				'min_cycles',
				sprintf( 'BillingPolicy: min_cycles cannot exceed max_cycles, got %d and %d.', $min_cycles, $max_cycles ),
				array( 'max' => $max_cycles )
			);
		}

		return null;
	}

	/**
	 * The error for a trial duration that is neither null nor an array, or null.
	 *
	 * @param mixed $trial_duration Candidate trial duration.
	 */
	private static function get_trial_duration_error( $trial_duration ): ?InvalidInputException {
		if ( null !== $trial_duration && ! is_array( $trial_duration ) ) {
			return new InvalidInputException(
				ValidationErrorCode::INVALID_TYPE,
				'trial_duration',
				sprintf( 'BillingPolicy: trial_duration must be null or an array, got %s.', gettype( $trial_duration ) ),
				array( 'type' => 'object' )
			);
		}

		return null;
	}

	/**
	 * The trial shape errors: the length's, then the unit's. None for no trial.
	 *
	 * @param array<array-key, mixed>|null $trial_duration Trial duration.
	 * @return array<int, InvalidInputException|null>
	 */
	private static function get_trial_errors( ?array $trial_duration ): array {
		if ( null === $trial_duration ) {
			return array();
		}

		return array( self::get_trial_length_error( $trial_duration ), self::get_trial_unit_error( $trial_duration ) );
	}

	/**
	 * The error for a missing or non-integer trial length, or null.
	 *
	 * @param array<array-key, mixed> $trial_duration Trial duration.
	 */
	private static function get_trial_length_error( array $trial_duration ): ?InvalidInputException {
		if ( ! array_key_exists( 'length', $trial_duration ) ) {
			return new InvalidInputException( ValidationErrorCode::REQUIRED, 'trial_duration.length', 'BillingPolicy: trial_duration.length is required.' );
		}
		if ( ! is_int( $trial_duration['length'] ) ) {
			return new InvalidInputException(
				ValidationErrorCode::INVALID_TYPE,
				'trial_duration.length',
				sprintf( 'BillingPolicy: trial_duration.length must be an integer, got %s.', gettype( $trial_duration['length'] ) ),
				array( 'type' => 'integer' )
			);
		}

		return null;
	}

	/**
	 * The error for a missing or non-string trial unit, or null.
	 *
	 * @param array<array-key, mixed> $trial_duration Trial duration.
	 */
	private static function get_trial_unit_error( array $trial_duration ): ?InvalidInputException {
		if ( ! array_key_exists( 'unit', $trial_duration ) ) {
			return new InvalidInputException( ValidationErrorCode::REQUIRED, 'trial_duration.unit', 'BillingPolicy: trial_duration.unit is required.' );
		}
		if ( ! is_string( $trial_duration['unit'] ) ) {
			return new InvalidInputException(
				ValidationErrorCode::INVALID_TYPE,
				'trial_duration.unit',
				sprintf( 'BillingPolicy: trial_duration.unit must be a string, got %s.', gettype( $trial_duration['unit'] ) ),
				array( 'type' => 'string' )
			);
		}

		return null;
	}
}
