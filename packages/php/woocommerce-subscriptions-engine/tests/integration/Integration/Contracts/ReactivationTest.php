<?php
/**
 * Integration tests for the Reactivation contract operation: ON_HOLD -> ACTIVE and the
 * next-payment date recomputed forward (the Model-1 seam), which re-arms the batch due
 * scan (an active contract carrying a next-payment date).
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Contracts;

use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use EngineIntegrationTestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\PlanGroup;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PlanSnapshot;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Reactivation;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanGroupRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Reactivation
 */
class ReactivationTest extends EngineIntegrationTestCase {

	private const GATEWAY = 'engine_test_gateway';

	/**
	 * Selling-plan id that resolves to no plan row, to exercise the no-policy floor.
	 */
	private const MISSING_PLAN_ID = 999999;

	/**
	 * @var ContractRepository
	 */
	private $contracts;

	/**
	 * @var Reactivation
	 */
	private $sut;

	public function set_up(): void {
		parent::set_up();

		$this->contracts = new ContractRepository();
		$this->sut       = new Reactivation( $this->contracts );
	}

	/**
	 * Create a monthly plan and return its id.
	 */
	private function make_monthly_plan(): int {
		return $this->make_plan( 'month' );
	}

	/**
	 * Create a plan on the given cadence period and return its id.
	 *
	 * @param string $period Billing period slug: day/week/month/year.
	 */
	private function make_plan( string $period ): int {
		$group_id = ( new PlanGroupRepository() )->insert( PlanGroup::create( array( 'name' => 'Club' ) ) );
		$plan     = Plan::create(
			$group_id,
			array(
				'name'           => ucfirst( $period ) . 'ly',
				'billing_policy' => new BillingPolicy( $period, 1, null, null, null ),
				'category'       => Plan::DEFAULT_CATEGORY,
				'extension_slug' => 'engine-tests',
			)
		);
		( new PlanRepository() )->insert( $plan );

		return (int) $plan->get_id();
	}

	/**
	 * Seed a contract with a next-payment date and the given selling plan.
	 *
	 * @param string|null $next_payment_gmt Next-payment GMT string, or null.
	 * @param int         $selling_plan_id  Selling plan id.
	 * @param string      $status           Contract status. Default ON_HOLD.
	 */
	private function seed_on_hold( ?string $next_payment_gmt, int $selling_plan_id, string $status = ContractStatus::ON_HOLD ): int {
		$contract = Contract::create(
			array(
				'customer_id'      => 1,
				'status'           => $status,
				'currency'         => 'USD',
				'selling_plan_id'  => $selling_plan_id,
				'payment_method'   => self::GATEWAY,
				'start_gmt'        => '2026-01-01 00:00:00',
				'next_payment_gmt' => $next_payment_gmt,
				'billing_total'    => '19.99',
			)
		);

		return $this->contracts->insert( $contract );
	}

	private function utc( string $datetime ): DateTimeImmutable {
		return new DateTimeImmutable( $datetime, new DateTimeZone( 'UTC' ) );
	}

	public function test_reactivate_resumes_and_rearms_the_renewal(): void {
		$id = $this->seed_on_hold( '2099-01-01 00:00:00', $this->make_monthly_plan() );

		$result = $this->sut->reactivate( $this->reload( $id ), $this->utc( '2026-06-01 00:00:00' ) );

		$this->assertTrue( $result );
		$stored = $this->reload( $id );
		// Re-armed: an active contract carrying a next-payment date is what the batch due scan picks up.
		$this->assertSame( ContractStatus::ACTIVE, $stored->get_status() );
		$this->assertNotNull( $stored->get_next_payment_gmt(), 'Reactivate re-arms: the contract is active with a next-payment date.' );
	}

	public function test_reactivate_keeps_a_future_next_payment_unchanged(): void {
		// Held, then resumed before the date arrives: nothing to recompute.
		$id = $this->seed_on_hold( '2026-07-01 00:00:00', $this->make_monthly_plan() );

		$this->sut->reactivate( $this->reload( $id ), $this->utc( '2026-06-15 00:00:00' ) );

		$this->assertSame( '2026-07-01 00:00:00', $this->reload( $id )->get_next_payment_gmt() );
	}

	public function test_reactivate_rolls_a_past_due_date_forward_by_whole_cadences(): void {
		// Due 2026-02-01, held until 2026-04-15: roll +1 month until future ->
		// 2026-02-01 -> 2026-03-01 -> 2026-04-01 -> 2026-05-01 (first > now).
		$id = $this->seed_on_hold( '2026-02-01 00:00:00', $this->make_monthly_plan() );

		$this->sut->reactivate( $this->reload( $id ), $this->utc( '2026-04-15 00:00:00' ) );

		$this->assertSame( '2026-05-01 00:00:00', $this->reload( $id )->get_next_payment_gmt() );
	}

	public function test_reactivate_rolls_by_the_frozen_snapshot_cadence_over_the_live_plan(): void {
		// The live selling plan is monthly, but the contract's frozen terms are
		// yearly: the snapshot is what the contract bills under, so the forward
		// roll steps by years - 2026-01-15 -> 2027-01-15 (first > now).
		$id = $this->seed_on_hold( '2026-01-15 00:00:00', $this->make_monthly_plan() );

		$contract = $this->reload( $id );
		$contract->set_plan_snapshot(
			PlanSnapshot::from_array(
				array(
					'selling_plan_id' => $contract->get_selling_plan_id(),
					'billing_policy'  => array(
						'period'   => 'year',
						'interval' => 1,
					),
				)
			)
		);

		$this->sut->reactivate( $contract, $this->utc( '2026-03-01 00:00:00' ) );

		$this->assertSame( '2027-01-15 00:00:00', $this->reload( $id )->get_next_payment_gmt() );
	}

	public function test_reactivate_floors_past_due_at_now_when_the_roll_cap_exhausts(): void {
		// Daily cadence, held ~6.5 years past due: more rolls than the cap allows, so
		// the date is floored at `$now` - never returned still in the past.
		$id = $this->seed_on_hold( '2020-01-01 00:00:00', $this->make_plan( 'day' ) );

		$this->sut->reactivate( $this->reload( $id ), $this->utc( '2026-07-06 00:00:00' ) );

		$this->assertSame( '2026-07-06 00:00:00', $this->reload( $id )->get_next_payment_gmt() );
	}

	public function test_reactivate_floors_past_due_at_now_without_a_policy(): void {
		// Selling plan resolves to no row, so there is no cadence to roll by.
		$id = $this->seed_on_hold( '2026-02-01 00:00:00', self::MISSING_PLAN_ID );

		$this->sut->reactivate( $this->reload( $id ), $this->utc( '2026-04-15 09:30:00' ) );

		$this->assertSame( '2026-04-15 09:30:00', $this->reload( $id )->get_next_payment_gmt() );
	}

	public function test_reactivate_leaves_a_null_next_payment_null(): void {
		$id = $this->seed_on_hold( null, $this->make_monthly_plan() );

		$this->sut->reactivate( $this->reload( $id ), $this->utc( '2026-04-15 00:00:00' ) );

		$stored = $this->reload( $id );
		$this->assertSame( ContractStatus::ACTIVE, $stored->get_status() );
		// No date to arm: the due scan never selects a contract without a next-payment date.
		$this->assertNull( $stored->get_next_payment_gmt() );
	}

	public function test_reactivate_fires_the_reactivated_action(): void {
		$id    = $this->seed_on_hold( '2099-01-01 00:00:00', $this->make_monthly_plan() );
		$fired = 0;
		add_action(
			Reactivation::CONTRACT_REACTIVATED_ACTION,
			static function () use ( &$fired ): void {
				++$fired;
			}
		);

		$this->sut->reactivate( $this->reload( $id ), $this->utc( '2026-06-01 00:00:00' ) );

		$this->assertSame( 1, $fired );
	}

	public function test_reactivate_rejects_an_already_active_contract(): void {
		// An active contract past its due date must NOT reach the recompute: rolling its
		// date forward would skip the charge the due scan owes it.
		$id       = $this->seed_on_hold( '2026-02-01 00:00:00', $this->make_monthly_plan(), ContractStatus::ACTIVE );
		$contract = $this->reload( $id );

		try {
			$this->sut->reactivate( $contract, $this->utc( '2026-04-15 00:00:00' ) );
			$this->fail( 'Expected a DomainException for an already-active contract.' );
		} catch ( DomainException $e ) {
			$row = $this->reload( $id );
			$this->assertSame( ContractStatus::ACTIVE, $row->get_status() );
			$this->assertSame( '2026-02-01 00:00:00', $row->get_next_payment_gmt(), 'The past-due date is untouched, so the due scan still bills it.' );
		}
	}

	public function test_reactivate_loses_the_race_to_a_concurrent_transition(): void {
		$id       = $this->seed_on_hold( '2099-01-01 00:00:00', self::MISSING_PLAN_ID );
		$contract = $this->reload( $id );

		// A concurrent request cancels the contract after our read: the compare-and-set
		// write must miss loudly instead of resurrecting the contract to active.
		$concurrent = $this->reload( $id );
		$concurrent->set_status( ContractStatus::CANCELLED );
		$this->contracts->update( $concurrent );

		try {
			$this->sut->reactivate( $contract, $this->utc( '2026-01-01 00:00:00' ) );
			$this->fail( 'Expected a DomainException when the conditional write misses.' );
		} catch ( DomainException $e ) {
			$this->assertSame( ContractStatus::CANCELLED, $this->reload( $id )->get_status(), 'The concurrent cancel is not clobbered.' );
		}
	}

	public function test_reactivate_rejects_a_terminal_contract(): void {
		$contract = Contract::create(
			array(
				'customer_id'     => 1,
				'status'          => ContractStatus::CANCELLED,
				'currency'        => 'USD',
				'selling_plan_id' => $this->make_monthly_plan(),
				'start_gmt'       => '2026-01-01 00:00:00',
				'billing_total'   => '19.99',
			)
		);
		$id       = $this->contracts->insert( $contract );

		$this->expectException( DomainException::class );
		$this->sut->reactivate( $this->reload( $id ), $this->utc( '2026-06-01 00:00:00' ) );
	}

	/**
	 * Reload a contract, asserting it still exists (narrows the nullable read).
	 *
	 * @param int $id Contract id.
	 */
	private function reload( int $id ): Contract {
		$contract = $this->contracts->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );

		return $contract;
	}
}
