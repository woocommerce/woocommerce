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
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\DueRenewal;

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
	private function due( int $count, string $status, string $ends_at ): DueRenewal {
		return new DueRenewal( 42, $count, $status, $ends_at );
	}

	public function test_advances_to_the_next_cycle_when_a_billed_head_period_has_ended(): void {
		$intent = $this->selector->select( $this->due( 1, CycleStatus::BILLED, '2026-02-01 00:00:00' ), $this->now );

		$this->assertNotNull( $intent );
		$this->assertSame( 42, $intent->get_contract_id() );
		$this->assertSame( 2, $intent->get_cycle_count() );
	}

	public function test_does_not_advance_when_the_billed_head_period_has_not_ended(): void {
		// The charge-ahead guard: a just-billed head whose period runs into the future is not
		// yet due for its successor.
		$intent = $this->selector->select( $this->due( 1, CycleStatus::BILLED, '2026-04-01 00:00:00' ), $this->now );

		$this->assertNull( $intent );
	}

	public function test_advances_on_the_exact_period_boundary(): void {
		// ends_at == now: the period has ended, so the successor is due.
		$intent = $this->selector->select( $this->due( 3, CycleStatus::BILLED, '2026-03-01 00:00:00' ), $this->now );

		$this->assertNotNull( $intent );
		$this->assertSame( 4, $intent->get_cycle_count() );
	}

	public function test_advances_past_a_cancelled_head_that_has_ended(): void {
		$intent = $this->selector->select( $this->due( 5, CycleStatus::CANCELLED, '2026-02-01 00:00:00' ), $this->now );

		$this->assertNotNull( $intent );
		$this->assertSame( 6, $intent->get_cycle_count() );
	}

	public function test_retries_the_same_cycle_when_the_head_is_still_pending(): void {
		// A pending head only reaches the selector via the scan once its lease has expired; the
		// money-path reclaims it. Selection targets the same count, not the next.
		$intent = $this->selector->select( $this->due( 7, CycleStatus::PENDING, '2026-02-01 00:00:00' ), $this->now );

		$this->assertNotNull( $intent );
		$this->assertSame( 7, $intent->get_cycle_count() );
	}

	public function test_skips_a_failed_head(): void {
		$this->assertNull( $this->selector->select( $this->due( 2, CycleStatus::FAILED, '2026-02-01 00:00:00' ), $this->now ) );
	}

	public function test_skips_a_processing_head(): void {
		$this->assertNull( $this->selector->select( $this->due( 2, CycleStatus::PROCESSING, '2026-02-01 00:00:00' ), $this->now ) );
	}

	public function test_skips_a_countless_head(): void {
		$this->assertNull( $this->selector->select( new DueRenewal( 42, null, CycleStatus::BILLED, '2026-02-01 00:00:00' ), $this->now ) );
	}
}
