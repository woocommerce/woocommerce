<?php
/**
 * Unit tests for the read-only renewal cycle selector.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Integration\Renewal;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalSelector;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\RenewalCandidate;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalSelector
 */
class RenewalSelectorTest extends TestCase {

	/**
	 * The scan moment the selector decides against.
	 *
	 * @var DateTimeImmutable
	 */
	private $now;

	/**
	 * The selector under test.
	 *
	 * @var RenewalSelector
	 */
	private $selector;

	protected function setUp(): void {
		parent::setUp();
		$this->now      = new DateTimeImmutable( '2026-03-01 00:00:00', new DateTimeZone( 'UTC' ) );
		$this->selector = new RenewalSelector();
	}

	/**
	 * @param int    $count   Head chargeable count.
	 * @param string $status  Head status.
	 * @param string $ends_at Head period end (GMT string).
	 */
	private function candidate( int $count, string $status, string $ends_at ): RenewalCandidate {
		return new RenewalCandidate( 42, $count, $status, $ends_at );
	}

	public function test_advances_to_the_next_cycle_when_a_billed_head_period_has_ended(): void {
		$this->assertSame( 2, $this->selector->select_scheduled_cycle( $this->candidate( 1, CycleStatus::BILLED, '2026-02-01 00:00:00' ), $this->now ) );
	}

	public function test_does_not_advance_when_the_billed_head_period_has_not_ended(): void {
		// The charge-ahead guard: a just-billed head whose period runs into the future is not
		// yet due for its successor.
		$this->assertNull( $this->selector->select_scheduled_cycle( $this->candidate( 1, CycleStatus::BILLED, '2026-04-01 00:00:00' ), $this->now ) );
	}

	public function test_advances_on_the_exact_period_boundary(): void {
		// ends_at == now: the period has ended, so the successor is due.
		$this->assertSame( 4, $this->selector->select_scheduled_cycle( $this->candidate( 3, CycleStatus::BILLED, '2026-03-01 00:00:00' ), $this->now ) );
	}

	public function test_advances_past_a_cancelled_head_that_has_ended(): void {
		$this->assertSame( 6, $this->selector->select_scheduled_cycle( $this->candidate( 5, CycleStatus::CANCELLED, '2026-02-01 00:00:00' ), $this->now ) );
	}

	public function test_retries_the_same_cycle_when_the_head_is_still_pending(): void {
		// A pending head only reaches the selector via the scan once its lease has expired; the
		// money-path reclaims it. Selection targets the same count, not the next.
		$this->assertSame( 7, $this->selector->select_scheduled_cycle( $this->candidate( 7, CycleStatus::PENDING, '2026-02-01 00:00:00' ), $this->now ) );
	}

	public function test_skips_a_failed_head(): void {
		$this->assertNull( $this->selector->select_scheduled_cycle( $this->candidate( 2, CycleStatus::FAILED, '2026-02-01 00:00:00' ), $this->now ) );
	}

	public function test_skips_a_processing_head(): void {
		$this->assertNull( $this->selector->select_scheduled_cycle( $this->candidate( 2, CycleStatus::PROCESSING, '2026-02-01 00:00:00' ), $this->now ) );
	}

	public function test_skips_a_countless_head(): void {
		$this->assertNull( $this->selector->select_scheduled_cycle( new RenewalCandidate( 42, null, CycleStatus::BILLED, '2026-02-01 00:00:00' ), $this->now ) );
	}

	public function test_manual_forces_the_next_cycle_regardless_of_the_due_date(): void {
		// A billed head whose period runs into the future would be skipped by the scheduled path,
		// but the admin path forces the next cycle anyway (no due-guard).
		$this->assertSame( 2, $this->selector->select_manual_cycle( $this->candidate( 1, CycleStatus::BILLED, '2026-04-01 00:00:00' ) ) );
	}

	public function test_manual_forces_the_next_cycle_past_a_cancelled_head(): void {
		$this->assertSame( 6, $this->selector->select_manual_cycle( $this->candidate( 5, CycleStatus::CANCELLED, '2026-04-01 00:00:00' ) ) );
	}

	public function test_manual_retries_a_failed_head(): void {
		// The scheduled path skips a failed head (awaits dunning); the admin path retries it.
		$this->assertSame( 2, $this->selector->select_manual_cycle( $this->candidate( 2, CycleStatus::FAILED, '2026-02-01 00:00:00' ) ) );
	}

	public function test_manual_retries_a_pending_head(): void {
		$this->assertSame( 7, $this->selector->select_manual_cycle( $this->candidate( 7, CycleStatus::PENDING, '2026-02-01 00:00:00' ) ) );
	}

	public function test_manual_skips_a_processing_head(): void {
		// A manual trigger cannot preempt an in-flight async charge.
		$this->assertNull( $this->selector->select_manual_cycle( $this->candidate( 2, CycleStatus::PROCESSING, '2026-02-01 00:00:00' ) ) );
	}

	public function test_manual_skips_a_countless_head(): void {
		$this->assertNull( $this->selector->select_manual_cycle( new RenewalCandidate( 42, null, CycleStatus::BILLED, '2026-02-01 00:00:00' ) ) );
	}
}
