<?php
/**
 * Unit tests for RenewalCalculator.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Renewal;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal\RenewalCalculator;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal\RenewalCalculator
 */
class RenewalCalculatorTest extends TestCase {

	private function policy( ?int $max_cycles, string $period = 'month', int $interval = 1 ): BillingPolicy {
		return new BillingPolicy( $period, $interval, null, $max_cycles, null );
	}

	public function test_open_ended_policy_never_reaches_max_cycles(): void {
		$policy = $this->policy( null );

		$this->assertFalse( RenewalCalculator::has_reached_max_cycles( $policy, 0 ) );
		$this->assertFalse( RenewalCalculator::has_reached_max_cycles( $policy, 99 ) );
	}

	public function test_not_terminal_below_max_cycles(): void {
		$policy = $this->policy( 3 );

		$this->assertFalse( RenewalCalculator::has_reached_max_cycles( $policy, 0 ) );
		$this->assertFalse( RenewalCalculator::has_reached_max_cycles( $policy, 2 ) );
	}

	public function test_terminal_at_max_cycles(): void {
		$policy = $this->policy( 3 );

		$this->assertTrue( RenewalCalculator::has_reached_max_cycles( $policy, 3 ) );
	}

	public function test_terminal_when_over_counted(): void {
		$policy = $this->policy( 3 );

		$this->assertTrue( RenewalCalculator::has_reached_max_cycles( $policy, 4 ) );
	}

	public function test_next_bill_date_adds_one_cadence_in_utc(): void {
		$policy = $this->policy( null, 'month', 1 );
		$anchor = new DateTimeImmutable( '2026-01-15 10:00:00', new DateTimeZone( 'UTC' ) );

		$next = RenewalCalculator::next_bill_date( $policy, $anchor );

		$this->assertSame( '2026-02-15 10:00:00', $next->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( 'UTC', $next->getTimezone()->getName() );
	}

	public function test_next_bill_date_honours_interval(): void {
		$policy = $this->policy( null, 'week', 2 );
		$anchor = new DateTimeImmutable( '2026-03-01 00:00:00', new DateTimeZone( 'UTC' ) );

		$next = RenewalCalculator::next_bill_date( $policy, $anchor );

		$this->assertSame( '2026-03-15 00:00:00', $next->format( 'Y-m-d H:i:s' ) );
	}

	public function test_next_bill_date_normalizes_non_utc_anchor_to_utc(): void {
		$policy = $this->policy( null, 'day', 1 );
		// 2026-01-15 23:30 in a +05:00 zone is 2026-01-15 18:30 UTC; adding a
		// day lands on 2026-01-16 18:30 UTC.
		$anchor = new DateTimeImmutable( '2026-01-15 23:30:00', new DateTimeZone( '+05:00' ) );

		$next = RenewalCalculator::next_bill_date( $policy, $anchor );

		$this->assertSame( '2026-01-16 18:30:00', $next->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( 'UTC', $next->getTimezone()->getName() );
	}
}
