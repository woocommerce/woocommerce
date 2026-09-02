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
use DomainException;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy
 */
class BillingPolicyTest extends TestCase {

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

	public function test_invalid_period_throws(): void {
		$policy = BillingPolicy::from_array(
			array(
				'period'   => 'fortnight',
				'interval' => 1,
			)
		);

		$this->expectException( DomainException::class );
		$policy->compute_next_renewal_from( new DateTimeImmutable( '2026-01-01', new DateTimeZone( 'UTC' ) ) );
	}

	public function test_non_positive_interval_throws(): void {
		$policy = BillingPolicy::from_array(
			array(
				'period'   => 'month',
				'interval' => 0,
			)
		);

		$this->expectException( DomainException::class );
		$policy->compute_next_renewal_from( new DateTimeImmutable( '2026-01-01', new DateTimeZone( 'UTC' ) ) );
	}

	public function test_non_array_trial_duration_throws(): void {
		$this->expectException( DomainException::class );
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
			$this->expectException( DomainException::class );
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
