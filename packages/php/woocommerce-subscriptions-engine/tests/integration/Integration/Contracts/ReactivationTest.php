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
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PlanSnapshot;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Hold;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Reactivation;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;

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
		return $this->make_plan();
	}

	/**
	 * Seed a contract with a next-payment date and the given selling plan.
	 *
	 * An on-hold row that still carries `next_payment_gmt` and no hold anchor meta is the
	 * shape of a contract held before hold started disarming the next-due moment; it is the
	 * reactivation fallback these seeds exercise.
	 *
	 * @param string|null           $next_payment_gmt Next-payment GMT string, or null.
	 * @param int                   $selling_plan_id  Selling plan id.
	 * @param string                $status           Contract status. Default ON_HOLD.
	 * @param array<string, string> $meta             Contract meta.
	 */
	private function seed_on_hold( ?string $next_payment_gmt, int $selling_plan_id, string $status = ContractStatus::ON_HOLD, array $meta = array() ): int {
		$contract = Contract::create(
			array(
				'extension_slug'   => 'engine-tests',
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

		$id = $this->contracts->insert( $contract );
		foreach ( $meta as $key => $value ) {
			$this->contracts->add_meta( $id, $key, $value );
		}

		return $id;
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

	public function test_reactivate_rearms_from_the_hold_anchor(): void {
		$id = $this->seed_on_hold( '2099-01-01 00:00:00', $this->make_monthly_plan(), ContractStatus::ACTIVE );
		( new Hold( $this->contracts ) )->hold( $this->reload( $id ) );
		$this->assertNull( $this->reload( $id )->get_next_payment_gmt(), 'Hold disarmed the next-due moment.' );

		$this->sut->reactivate( $this->reload( $id ), $this->utc( '2026-06-01 00:00:00' ) );

		$stored = $this->reload( $id );
		$this->assertSame( ContractStatus::ACTIVE, $stored->get_status() );
		$this->assertSame( '2099-01-01 00:00:00', $stored->get_next_payment_gmt() );
		$this->assertSame( '', $this->contracts->get_meta( $id, Hold::ANCHOR_META_KEY, true ) );
	}

	public function test_reactivate_rolls_a_past_due_anchor_forward(): void {
		// Held with a 2026-02-01 anchor, resumed 2026-04-15: rolled forward by whole
		// cadences exactly like a stored past-due date -> 2026-05-01.
		$id = $this->seed_on_hold( null, $this->make_monthly_plan(), ContractStatus::ON_HOLD, array( Hold::ANCHOR_META_KEY => '2026-02-01 00:00:00' ) );

		$this->sut->reactivate( $this->reload( $id ), $this->utc( '2026-04-15 00:00:00' ) );

		$stored = $this->reload( $id );
		$this->assertSame( '2026-05-01 00:00:00', $stored->get_next_payment_gmt() );
		$this->assertSame( '', $this->contracts->get_meta( $id, Hold::ANCHOR_META_KEY, true ) );
	}

	/**
	 * Hold clears the next payment, so one set while held was re-armed deliberately and wins.
	 */
	public function test_reactivate_prefers_a_stored_next_payment_over_the_anchor(): void {
		$id = $this->seed_on_hold( '2099-06-01 00:00:00', $this->make_monthly_plan(), ContractStatus::ON_HOLD, array( Hold::ANCHOR_META_KEY => '2099-01-01 00:00:00' ) );

		$this->sut->reactivate( $this->reload( $id ), $this->utc( '2026-06-01 00:00:00' ) );

		$stored = $this->reload( $id );
		$this->assertSame( '2099-06-01 00:00:00', $stored->get_next_payment_gmt() );
		$this->assertSame( '', $this->contracts->get_meta( $id, Hold::ANCHOR_META_KEY, true ) );
	}

	public function test_reactivate_ignores_a_malformed_anchor(): void {
		$id = $this->seed_on_hold( null, $this->make_monthly_plan(), ContractStatus::ON_HOLD, array( Hold::ANCHOR_META_KEY => 'not-a-date' ) );

		$this->sut->reactivate( $this->reload( $id ), $this->utc( '2026-06-01 00:00:00' ) );

		$stored = $this->reload( $id );
		$this->assertSame( ContractStatus::ACTIVE, $stored->get_status() );
		$this->assertNull( $stored->get_next_payment_gmt(), 'A malformed anchor counts as absent.' );
		$this->assertSame( '', $this->contracts->get_meta( $id, Hold::ANCHOR_META_KEY, true ) );
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
		$id = $this->seed_on_hold(
			'2020-01-01 00:00:00',
			$this->make_plan(
				array(
					'billing_policy' => array(
						'period'   => 'day',
						'interval' => 1,
					),
				)
			)
		);

		$this->sut->reactivate( $this->reload( $id ), $this->utc( '2026-07-06 00:00:00' ) );

		$this->assertSame( '2026-07-06 00:00:00', $this->reload( $id )->get_next_payment_gmt() );
	}

	public function test_reactivate_floors_past_due_at_now_without_a_policy(): void {
		// Selling plan resolves to no row, so there is no cadence to roll by.
		$id = $this->seed_on_hold( '2026-02-01 00:00:00', self::MISSING_PLAN_ID );

		$this->sut->reactivate( $this->reload( $id ), $this->utc( '2026-04-15 09:30:00' ) );

		$this->assertSame( '2026-04-15 09:30:00', $this->reload( $id )->get_next_payment_gmt() );
	}

	/**
	 * @dataProvider provide_unusable_live_billing_payloads
	 *
	 * @param array<string, mixed>|null $billing The live plan's billing payload.
	 */
	public function test_reactivate_floors_past_due_at_now_when_the_live_billing_is_unusable( ?array $billing ): void {
		$plan_id = $this->make_plan( array( 'billing_policy' => $billing ) );
		$id      = $this->seed_on_hold( '2026-02-01 00:00:00', $plan_id );

		$warnings = $this->capture_engine_log(
			'warning',
			array(
				'contract_id' => $id,
				'plan_id'     => $plan_id,
			),
			function () use ( $id ): void {
				$this->sut->reactivate( $this->reload( $id ), $this->utc( '2026-04-15 09:30:00' ) );
			}
		);

		$this->assertSame( '2026-04-15 09:30:00', $this->reload( $id )->get_next_payment_gmt() );
		$this->assertNotEmpty( $warnings, 'A null or unusable live billing payload is logged with the contract and plan.' );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>|null}>
	 */
	public function provide_unusable_live_billing_payloads(): array {
		return array(
			'null payload'     => array( null ),
			'missing interval' => array( array( 'period' => 'month' ) ),
			'unknown period'   => array(
				array(
					'period'   => 'decade',
					'interval' => 1,
				),
			),
			'zero interval'    => array(
				array(
					'period'   => 'month',
					'interval' => 0,
				),
			),
		);
	}

	/**
	 * A snapshot policy with no usable cadence falls through to the live plan (logged),
	 * the same as renewal, instead of throwing out of the forward roll.
	 *
	 * @dataProvider provide_unusable_snapshot_billing_payloads
	 *
	 * @param array<string, mixed>      $snapshot_billing The snapshot's billing payload.
	 * @param array<string, mixed>|null $live_billing     The live plan's billing payload.
	 * @param string                    $expected_next    The expected next payment.
	 */
	public function test_reactivate_falls_back_to_the_live_plan_when_the_snapshot_billing_is_unusable( array $snapshot_billing, ?array $live_billing, string $expected_next ): void {
		$id = $this->seed_on_hold( '2026-02-01 00:00:00', $this->make_plan( array( 'billing_policy' => $live_billing ) ) );

		$contract = $this->reload( $id );
		$contract->set_plan_snapshot(
			PlanSnapshot::from_array(
				array(
					'selling_plan_id' => $contract->get_selling_plan_id(),
					'billing_policy'  => $snapshot_billing,
				)
			)
		);

		$warnings = $this->capture_engine_log(
			'warning',
			array( 'contract_id' => $id ),
			function () use ( $contract ): void {
				$this->assertTrue( $this->sut->reactivate( $contract, $this->utc( '2026-04-15 09:30:00' ) ) );
			}
		);

		$stored = $this->reload( $id );
		$this->assertSame( ContractStatus::ACTIVE, $stored->get_status() );
		$this->assertSame( $expected_next, $stored->get_next_payment_gmt() );
		$this->assertNotEmpty( $warnings, 'An unusable snapshot billing payload is logged.' );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>, 1: array<string, mixed>|null, 2: string}>
	 */
	public function provide_unusable_snapshot_billing_payloads(): array {
		$monthly = array(
			'period'   => 'month',
			'interval' => 1,
		);
		$decade  = array(
			'period'   => 'decade',
			'interval' => 1,
		);
		$zero    = array(
			'period'   => 'month',
			'interval' => 0,
		);
		$rolled  = '2026-05-01 00:00:00';
		$floored = '2026-04-15 09:30:00';

		return array(
			'unknown period, live monthly'     => array( $decade, $monthly, $rolled ),
			'zero interval, live monthly'      => array( $zero, $monthly, $rolled ),
			'unknown period, live unusable'    => array( $decade, $zero, $floored ),
			'zero interval, live null payload' => array( $zero, null, $floored ),
		);
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

	/**
	 * The anchor is cleared after the status write has committed, so a failed delete must
	 * not abort the reactivation, which could not be retried on an active contract.
	 */
	public function test_a_failed_anchor_clear_does_not_abort_the_reactivation(): void {
		$id = $this->seed_on_hold( null, $this->make_monthly_plan(), ContractStatus::ON_HOLD, array( Hold::ANCHOR_META_KEY => '2099-01-01 00:00:00' ) );

		$fired = 0;
		add_action(
			Reactivation::CONTRACT_REACTIVATED_ACTION,
			static function () use ( &$fired ): void {
				++$fired;
			}
		);
		$meta_table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_META );
		$break      = static function ( string $query ) use ( $meta_table ): string {
			return 0 === strpos( $query, "DELETE FROM `{$meta_table}`" ) ? 'SELECT broken syntax (' : $query;
		};
		add_filter( 'query', $break );

		try {
			$this->assertTrue( $this->sut->reactivate( $this->reload( $id ), $this->utc( '2026-06-01 00:00:00' ) ) );
		} finally {
			remove_filter( 'query', $break );
		}

		$stored = $this->reload( $id );
		$this->assertSame( 1, $fired, 'The reactivated action fires.' );
		$this->assertSame( ContractStatus::ACTIVE, $stored->get_status() );
		$this->assertSame( '2099-01-01 00:00:00', $stored->get_next_payment_gmt() );
	}

	public function test_a_lost_race_keeps_the_hold_anchor(): void {
		$id    = $this->seed_on_hold( null, $this->make_monthly_plan(), ContractStatus::ON_HOLD, array( Hold::ANCHOR_META_KEY => '2099-01-01 00:00:00' ) );
		$stale = $this->reload( $id );

		$concurrent = $this->reload( $id );
		$concurrent->set_status( ContractStatus::CANCELLED );
		$this->contracts->update( $concurrent );

		try {
			$this->sut->reactivate( $stale, $this->utc( '2026-06-01 00:00:00' ) );
			$this->fail( 'Expected a DomainException when the conditional write misses.' );
		} catch ( DomainException $e ) {
			$this->assertSame( '2099-01-01 00:00:00', $this->contracts->get_meta( $id, Hold::ANCHOR_META_KEY, true ) );
		}
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
				'extension_slug'  => 'engine-tests',
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

	public function test_reactivate_rejects_a_pending_cancellation_contract(): void {
		$id = $this->seed_on_hold( '2099-01-01 00:00:00', $this->make_monthly_plan(), ContractStatus::PENDING_CANCELLATION );

		try {
			$this->sut->reactivate( $this->reload( $id ), $this->utc( '2026-06-01 00:00:00' ) );
			$this->fail( 'Expected a DomainException for a pending-cancellation contract.' );
		} catch ( DomainException $e ) {
			$stored = $this->reload( $id );
			$this->assertSame( ContractStatus::PENDING_CANCELLATION, $stored->get_status() );
			$this->assertSame( '2099-01-01 00:00:00', $stored->get_next_payment_gmt(), 'Nothing was written.' );
		}
	}

	public function test_reactivate_rejects_an_unregistered_stored_status(): void {
		global $wpdb;

		$id = $this->seed_on_hold( '2099-01-01 00:00:00', $this->make_monthly_plan() );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS ), array( 'status' => 'legacy-paused' ), array( 'id' => $id ) );

		try {
			$this->sut->reactivate( $this->reload( $id ), $this->utc( '2026-06-01 00:00:00' ) );
			$this->fail( 'Expected a DomainException for an unregistered stored status.' );
		} catch ( DomainException $e ) {
			$stored = $this->reload( $id );
			$this->assertSame( 'legacy-paused', $stored->get_status() );
			$this->assertSame( '2099-01-01 00:00:00', $stored->get_next_payment_gmt(), 'Nothing was written.' );
		}
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
