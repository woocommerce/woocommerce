<?php
/**
 * Unit tests for BillingPolicy.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\ValueObject;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\InvalidInputException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ValidationErrorCode;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\AssertsInvalidInput;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy
 */
class BillingPolicyTest extends TestCase {

	use AssertsInvalidInput;

	/**
	 * @testdox Each refusal reports its code, field and params.
	 * @dataProvider provide_refusals
	 *
	 * @param callable             $operation Operation expected to be refused.
	 * @param string               $code      Expected error code.
	 * @param string               $field     Expected field.
	 * @param array<string, mixed> $params    Expected params.
	 */
	public function test_each_refusal_reports_its_code_field_and_params( callable $operation, string $code, string $field, array $params ): void {
		$this->assert_invalid_input( $operation, $code, $field, $params );
	}

	/**
	 * @return array<string, array{0: callable, 1: string, 2: string, 3: array<string, mixed>}>
	 */
	public function provide_refusals(): array {
		$units = array( 'allowed' => array( 'day', 'week', 'month', 'year' ) );
		$start = new DateTimeImmutable( '2026-01-01 00:00:00', new DateTimeZone( 'UTC' ) );

		return array(
			'interval not positive'     => array(
				static function (): void {
					new BillingPolicy( 'month', 0, null, null, null );
				},
				ValidationErrorCode::OUT_OF_BOUNDS,
				'interval',
				array( 'min' => 1 ),
			),
			'unknown period'            => array(
				static function (): void {
					new BillingPolicy( 'fortnight', 1, null, null, null );
				},
				ValidationErrorCode::NOT_IN_ENUM,
				'period',
				$units,
			),
			'period missing'            => array(
				static function (): void {
					BillingPolicy::from_array( array( 'interval' => 1 ) );
				},
				ValidationErrorCode::REQUIRED,
				'period',
				array(),
			),
			'interval missing'          => array(
				static function (): void {
					BillingPolicy::from_array( array( 'period' => 'month' ) );
				},
				ValidationErrorCode::REQUIRED,
				'interval',
				array(),
			),
			'period not a string'       => array(
				static function (): void {
					BillingPolicy::from_array(
						array(
							'period'   => 1,
							'interval' => 1,
						)
					);
				},
				ValidationErrorCode::INVALID_TYPE,
				'period',
				array( 'type' => 'string' ),
			),
			'interval not an integer'   => array(
				static function (): void {
					BillingPolicy::from_array(
						array(
							'period'   => 'month',
							'interval' => '2',
						)
					);
				},
				ValidationErrorCode::INVALID_TYPE,
				'interval',
				array( 'type' => 'integer' ),
			),
			'trial not an object'       => array(
				static function (): void {
					BillingPolicy::from_array(
						array(
							'period'         => 'month',
							'interval'       => 1,
							'trial_duration' => 'P7D',
						)
					);
				},
				ValidationErrorCode::INVALID_TYPE,
				'trial_duration',
				array( 'type' => 'object' ),
			),
			'min_cycles negative'       => array(
				static function (): void {
					new BillingPolicy( 'month', 1, -1, null, null );
				},
				ValidationErrorCode::OUT_OF_BOUNDS,
				'min_cycles',
				array( 'min' => 0 ),
			),
			'max_cycles negative'       => array(
				static function (): void {
					new BillingPolicy( 'month', 1, null, -1, null );
				},
				ValidationErrorCode::OUT_OF_BOUNDS,
				'max_cycles',
				array( 'min' => 0 ),
			),
			'min_cycles above max'      => array(
				static function (): void {
					new BillingPolicy( 'month', 1, 5, 2, null );
				},
				ValidationErrorCode::OUT_OF_BOUNDS,
				'min_cycles',
				array( 'max' => 2 ),
			),
			'trial length missing'      => array(
				static function (): void {
					BillingPolicy::from_array(
						array(
							'period'         => 'month',
							'interval'       => 1,
							'trial_duration' => array( 'unit' => 'day' ),
						)
					);
				},
				ValidationErrorCode::REQUIRED,
				'trial_duration.length',
				array(),
			),
			'trial unit missing'        => array(
				static function (): void {
					BillingPolicy::from_array(
						array(
							'period'         => 'month',
							'interval'       => 1,
							'trial_duration' => array( 'length' => 7 ),
						)
					);
				},
				ValidationErrorCode::REQUIRED,
				'trial_duration.unit',
				array(),
			),
			'trial length not integer'  => array(
				static function (): void {
					BillingPolicy::from_array(
						array(
							'period'         => 'month',
							'interval'       => 1,
							'trial_duration' => array(
								'length' => '7',
								'unit'   => 'day',
							),
						)
					);
				},
				ValidationErrorCode::INVALID_TYPE,
				'trial_duration.length',
				array( 'type' => 'integer' ),
			),
			'trial unit not a string'   => array(
				static function (): void {
					BillingPolicy::from_array(
						array(
							'period'         => 'month',
							'interval'       => 1,
							'trial_duration' => array(
								'length' => 7,
								'unit'   => 1,
							),
						)
					);
				},
				ValidationErrorCode::INVALID_TYPE,
				'trial_duration.unit',
				array( 'type' => 'string' ),
			),
			'trial length not positive' => array(
				static function () use ( $start ): void {
					$policy = new BillingPolicy(
						'month',
						1,
						null,
						null,
						array(
							'length' => 0,
							'unit'   => 'day',
						)
					);
					$policy->compute_first_renewal_from( $start );
				},
				ValidationErrorCode::OUT_OF_BOUNDS,
				'trial_duration.length',
				array( 'min' => 1 ),
			),
			'unknown trial unit'        => array(
				static function () use ( $start ): void {
					$policy = new BillingPolicy(
						'month',
						1,
						null,
						null,
						array(
							'length' => 7,
							'unit'   => 'fortnight',
						)
					);
					$policy->compute_first_renewal_from( $start );
				},
				ValidationErrorCode::NOT_IN_ENUM,
				'trial_duration.unit',
				$units,
			),
		);
	}

	/**
	 * @testdox from_array refuses an unusable cadence.
	 * @testWith ["decade", 1, "BillingPolicy: invalid period \"decade\"."]
	 *           ["month", 0, "BillingPolicy: interval must be positive, got 0."]
	 *           ["day", -1, "BillingPolicy: interval must be positive, got -1."]
	 *
	 * @param string $period   Period.
	 * @param int    $interval Interval.
	 * @param string $message  Expected exception message.
	 */
	public function test_from_array_refuses_an_unusable_cadence( string $period, int $interval, string $message ): void {
		$data = array(
			'period'   => $period,
			'interval' => $interval,
		);

		$this->expectException( InvalidInputException::class );
		$this->expectExceptionMessage( $message );

		BillingPolicy::from_array( $data );
	}

	/**
	 * @testdox from_array accepts a usable cadence.
	 */
	public function test_from_array_accepts_a_usable_cadence(): void {
		$policy = BillingPolicy::from_array(
			array(
				'period'   => 'week',
				'interval' => 2,
			)
		);

		$this->assertSame( 'week', $policy->get_period() );
		$this->assertSame( 2, $policy->get_interval() );
	}

	public function test_round_trips_through_array(): void {
		$data = array(
			'period'         => 'month',
			'interval'       => 2,
			'min_cycles'     => 1,
			'max_cycles'     => 12,
			'trial_duration' => array(
				'length' => 14,
				'unit'   => 'day',
			),
		);

		$policy = BillingPolicy::from_array( $data );

		$this->assertSame( 'month', $policy->get_period() );
		$this->assertSame( 2, $policy->get_interval() );
		$this->assertSame( 1, $policy->get_min_cycles() );
		$this->assertSame( 12, $policy->get_max_cycles() );
		$this->assertSame( $data['trial_duration'], $policy->get_trial_duration() );
		$this->assertSame( $data, $policy->to_array() );
	}

	public function test_missing_nullable_keys_default_to_null(): void {
		$policy = BillingPolicy::from_array(
			array(
				'period'   => 'week',
				'interval' => 1,
			)
		);

		$this->assertNull( $policy->get_min_cycles() );
		$this->assertNull( $policy->get_max_cycles() );
		$this->assertNull( $policy->get_trial_duration() );
	}

	public function test_compute_next_renewal_adds_one_cadence_in_utc(): void {
		$policy = BillingPolicy::from_array(
			array(
				'period'   => 'month',
				'interval' => 1,
			)
		);

		$anchor = new DateTimeImmutable( '2026-01-15 10:00:00', new DateTimeZone( 'UTC' ) );
		$next   = $policy->compute_next_renewal_from( $anchor );

		$this->assertSame( '2026-02-15 10:00:00', $next->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( 'UTC', $next->getTimezone()->getName() );
	}

	public function test_compute_first_renewal_honours_trial(): void {
		$policy = BillingPolicy::from_array(
			array(
				'period'         => 'month',
				'interval'       => 1,
				'trial_duration' => array(
					'length' => 7,
					'unit'   => 'day',
				),
			)
		);

		$start = new DateTimeImmutable( '2026-01-01 00:00:00', new DateTimeZone( 'UTC' ) );
		$first = $policy->compute_first_renewal_from( $start );

		$this->assertSame( '2026-01-08 00:00:00', $first->format( 'Y-m-d H:i:s' ) );
	}

	public function test_compute_first_renewal_without_trial_matches_next(): void {
		$policy = BillingPolicy::from_array(
			array(
				'period'   => 'year',
				'interval' => 1,
			)
		);

		$start = new DateTimeImmutable( '2026-03-10 12:00:00', new DateTimeZone( 'UTC' ) );

		$this->assertEquals(
			$policy->compute_next_renewal_from( $start ),
			$policy->compute_first_renewal_from( $start )
		);
	}

	/**
	 * @testdox construction refuses an unknown period.
	 */
	public function test_construction_refuses_an_unknown_period(): void {
		$this->expectException( InvalidInputException::class );

		new BillingPolicy( 'fortnight', 1, null, null, null );
	}

	/**
	 * @testdox construction refuses a non-positive interval.
	 */
	public function test_construction_refuses_a_non_positive_interval(): void {
		$this->expectException( InvalidInputException::class );

		new BillingPolicy( 'month', 0, null, null, null );
	}

	public function test_non_array_trial_duration_throws(): void {
		$this->expectException( InvalidInputException::class );
		$this->expectExceptionMessage( 'BillingPolicy: trial_duration must be null or an array, got string.' );

		BillingPolicy::from_array(
			array(
				'period'         => 'month',
				'interval'       => 1,
				'trial_duration' => 'P7D',
			)
		);
	}

	/**
	 * @dataProvider provide_min_and_max_cycles_validation_cases
	 * @param string|null $expected_exception_message The expected exception message, or null if no exception is expected.
	 * @param int|null    $min_cycles                 The minimum number of cycles.
	 * @param int|null    $max_cycles                 The maximum number of cycles.
	 */
	public function test_min_and_max_cycles_validation( ?string $expected_exception_message, ?int $min_cycles, ?int $max_cycles ): void {
		if ( null !== $expected_exception_message ) {
			$this->expectException( InvalidInputException::class );
			$this->expectExceptionMessage( $expected_exception_message );
		}

		$policy = BillingPolicy::from_array(
			array(
				'period'     => 'month',
				'interval'   => 1,
				'min_cycles' => $min_cycles,
				'max_cycles' => $max_cycles,
			)
		);

		if ( null === $expected_exception_message ) {
			$this->assertSame( $min_cycles, $policy->get_min_cycles() );
			$this->assertSame( $max_cycles, $policy->get_max_cycles() );
		}
	}

	/**
	 * @return array<string, array{expected_exception_message: string|null, min_cycles: int|null, max_cycles: int|null}>
	 */
	public function provide_min_and_max_cycles_validation_cases(): array {
		return array(
			'min_cycles is 0, max_cycles is null'        => array(
				'expected_exception_message' => null,
				'min_cycles'                 => 0,
				'max_cycles'                 => null,
			),
			'min_cycles is 0, max_cycles is positive'    => array(
				'expected_exception_message' => null,
				'min_cycles'                 => 0,
				'max_cycles'                 => 10,
			),
			'min_cycles is 0, max_cycles is less than 0' => array(
				'expected_exception_message' => 'BillingPolicy: max_cycles must be 0 or greater, got -4.',
				'min_cycles'                 => 0,
				'max_cycles'                 => -4,
			),
			'max_cycles is 0, min_cycles is null'        => array(
				'expected_exception_message' => null,
				'min_cycles'                 => null,
				'max_cycles'                 => 0,
			),
			'max_cycles is 0, min_cycles is positive'    => array(
				'expected_exception_message' => 'BillingPolicy: min_cycles cannot exceed max_cycles, got 5 and 0.',
				'min_cycles'                 => 5,
				'max_cycles'                 => 0,
			),
			'max_cycles is 0, min_cycles is greater than max_cycles' => array(
				'expected_exception_message' => 'BillingPolicy: min_cycles cannot exceed max_cycles, got 5 and 0.',
				'min_cycles'                 => 5,
				'max_cycles'                 => 0,
			),
			'max_cycles is positive, min_cycles is null' => array(
				'expected_exception_message' => null,
				'min_cycles'                 => null,
				'max_cycles'                 => 10,
			),
			'max_cycles is positive, min_cycles is positive' => array(
				'expected_exception_message' => null,
				'min_cycles'                 => 1,
				'max_cycles'                 => 10,
			),
			'max_cycles is positive, min_cycles is the same as max_cycles' => array(
				'expected_exception_message' => null,
				'min_cycles'                 => 10,
				'max_cycles'                 => 10,
			),
			'min_cycles is positive, max_cycles is null' => array(
				'expected_exception_message' => null,
				'min_cycles'                 => 1,
				'max_cycles'                 => null,
			),
			'min_cycles is positive, max_cycles is positive' => array(
				'expected_exception_message' => null,
				'min_cycles'                 => 1,
				'max_cycles'                 => 10,
			),
			'min_cycles is positive, max_cycles is less than min_cycles' => array(
				'expected_exception_message' => 'BillingPolicy: min_cycles cannot exceed max_cycles, got 10 and 9.',
				'min_cycles'                 => 10,
				'max_cycles'                 => 9,
			),
			'min_cycles is negative, max_cycles is null' => array(
				'expected_exception_message' => 'BillingPolicy: min_cycles must be 0 or greater, got -1.',
				'min_cycles'                 => -1,
				'max_cycles'                 => null,
			),
			'min_cycles is negative, max_cycles is positive' => array(
				'expected_exception_message' => 'BillingPolicy: min_cycles must be 0 or greater, got -1.',
				'min_cycles'                 => -1,
				'max_cycles'                 => 10,
			),
			'min_cycles is negative, max_cycles is less than min_cycles' => array(
				'expected_exception_message' => 'BillingPolicy: min_cycles must be 0 or greater, got -1.',
				'min_cycles'                 => -1,
				'max_cycles'                 => -1,
			),
			'min_cycles is positive, max_cycles is negative' => array(
				'expected_exception_message' => 'BillingPolicy: max_cycles must be 0 or greater, got -1.',
				'min_cycles'                 => 1,
				'max_cycles'                 => -1,
			),
		);
	}
}
