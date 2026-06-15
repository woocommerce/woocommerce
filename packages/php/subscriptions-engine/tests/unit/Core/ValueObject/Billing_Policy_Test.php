<?php
/**
 * Unit tests for Billing_Policy.
 *
 * @package WooCommerce\Subscriptions\Engine
 */

declare( strict_types=1 );

namespace WooCommerce\Subscriptions\Engine\Tests\Unit\Core\ValueObject;

use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use PHPUnit\Framework\TestCase;
use WooCommerce\Subscriptions\Engine\Core\ValueObject\Billing_Policy;

/**
 * @covers \WooCommerce\Subscriptions\Engine\Core\ValueObject\Billing_Policy
 */
class Billing_Policy_Test extends TestCase {

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

		$policy = Billing_Policy::from_array( $data );

		$this->assertSame( 'month', $policy->get_period() );
		$this->assertSame( 2, $policy->get_interval() );
		$this->assertSame( 1, $policy->get_min_cycles() );
		$this->assertSame( 12, $policy->get_max_cycles() );
		$this->assertSame( $data['trial_duration'], $policy->get_trial_duration() );
		$this->assertSame( $data, $policy->to_array() );
	}

	public function test_missing_nullable_keys_default_to_null(): void {
		$policy = Billing_Policy::from_array(
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
		$policy = Billing_Policy::from_array(
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
		$policy = Billing_Policy::from_array(
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
		$policy = Billing_Policy::from_array(
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
		$policy = Billing_Policy::from_array(
			array(
				'period'   => 'fortnight',
				'interval' => 1,
			)
		);

		$this->expectException( DomainException::class );
		$policy->compute_next_renewal_from( new DateTimeImmutable( '2026-01-01', new DateTimeZone( 'UTC' ) ) );
	}

	public function test_non_positive_interval_throws(): void {
		$policy = Billing_Policy::from_array(
			array(
				'period'   => 'month',
				'interval' => 0,
			)
		);

		$this->expectException( DomainException::class );
		$policy->compute_next_renewal_from( new DateTimeImmutable( '2026-01-01', new DateTimeZone( 'UTC' ) ) );
	}
}
