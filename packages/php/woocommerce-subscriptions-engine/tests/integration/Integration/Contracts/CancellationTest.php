<?php
/**
 * Integration tests for the Cancellation contract operation: the period-end mode (->
 * PENDING_CANCELLATION, the end date stamped, the next-due moment disarmed) and the
 * immediate mode's guards and disarm (its order/cycle effects are covered through the
 * facade suite).
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Contracts;

use DomainException;
use EngineIntegrationTestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Cancellation;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Hold;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Cancellation
 */
class CancellationTest extends EngineIntegrationTestCase {

	/**
	 * @var ContractRepository
	 */
	private $contracts;

	/**
	 * @var Cancellation
	 */
	private $sut;

	public function set_up(): void {
		parent::set_up();
		$this->contracts = new ContractRepository();
		$this->sut       = new Cancellation( $this->contracts );
	}

	/**
	 * Seed an active contract with a future next-payment date.
	 *
	 * @param string|null $end_gmt Optional pre-set end date.
	 */
	private function seed_active( ?string $end_gmt = null ): int {
		return $this->seed( ContractStatus::ACTIVE, $end_gmt );
	}

	/**
	 * Seed a contract at a status with a future next-payment date.
	 *
	 * @param string      $status  Contract status.
	 * @param string|null $end_gmt Optional pre-set end date.
	 */
	private function seed( string $status, ?string $end_gmt = null ): int {
		$contract = Contract::create(
			array(
				'customer_id'      => 1,
				'status'           => $status,
				'currency'         => 'USD',
				'selling_plan_id'  => 1,
				'start_gmt'        => '2026-01-01 00:00:00',
				'next_payment_gmt' => '2099-01-01 00:00:00',
				'end_gmt'          => $end_gmt,
				'billing_total'    => '19.99',
			)
		);

		return $this->contracts->insert( $contract );
	}

	public function test_winds_down_to_pending_cancellation_and_stamps_the_end_date(): void {
		$id = $this->seed_active();

		$result = $this->sut->cancel_at_period_end( $this->reload( $id ) );

		$this->assertTrue( $result );
		$stored = $this->reload( $id );
		$this->assertSame( ContractStatus::PENDING_CANCELLATION, $stored->get_status() );
		// The next-payment moment becomes the contract end (the "cancels on" date).
		$this->assertSame( '2099-01-01 00:00:00', $stored->get_end_gmt() );
	}

	public function test_cancel_at_period_end_stamps_the_end_and_clears_the_next_payment(): void {
		// The flow disarms the next-due moment itself, so the due scan never selects the
		// winding-down contract; its end_gmt is the date it terminates at.
		$id = $this->seed_active();

		$this->sut->cancel_at_period_end( $this->reload( $id ) );

		$stored = $this->reload( $id );
		$this->assertSame( '2099-01-01 00:00:00', $stored->get_end_gmt() );
		$this->assertNull( $stored->get_next_payment_gmt() );
	}

	public function test_preserves_an_existing_end_date(): void {
		$id = $this->seed_active( '2026-09-09 00:00:00' );

		$this->sut->cancel_at_period_end( $this->reload( $id ) );

		$stored = $this->reload( $id );
		$this->assertSame( '2026-09-09 00:00:00', $stored->get_end_gmt() );
		$this->assertNull( $stored->get_next_payment_gmt() );
	}

	public function test_cancel_at_period_end_from_on_hold_stamps_the_end_from_the_hold_anchor(): void {
		$id = $this->seed_active();
		( new Hold( $this->contracts ) )->hold( $this->reload( $id ) );

		$this->assertTrue( $this->sut->cancel_at_period_end( $this->reload( $id ) ) );

		$stored = $this->reload( $id );
		$this->assertSame( ContractStatus::PENDING_CANCELLATION, $stored->get_status() );
		$this->assertSame( '2099-01-01 00:00:00', $stored->get_end_gmt() );
		$this->assertNull( $stored->get_next_payment_gmt() );
		$this->assertArrayNotHasKey( Hold::ANCHOR_META_KEY, $stored->get_meta() );
	}

	public function test_cancel_at_period_end_on_a_pending_cancellation_contract_is_a_no_op(): void {
		$id = $this->seed_active();
		$this->sut->cancel_at_period_end( $this->reload( $id ) );

		$fired = 0;
		add_action(
			Cancellation::CONTRACT_PENDING_CANCELLATION_ACTION,
			static function () use ( &$fired ): void {
				++$fired;
			}
		);

		$this->assertTrue( $this->sut->cancel_at_period_end( $this->reload( $id ) ) );

		$stored = $this->reload( $id );
		$this->assertSame( 1, $fired );
		$this->assertSame( ContractStatus::PENDING_CANCELLATION, $stored->get_status() );
		$this->assertSame( '2099-01-01 00:00:00', $stored->get_end_gmt() );
		$this->assertNull( $stored->get_next_payment_gmt() );
	}

	public function test_fires_the_pending_cancellation_action(): void {
		$id    = $this->seed_active();
		$fired = 0;
		add_action(
			Cancellation::CONTRACT_PENDING_CANCELLATION_ACTION,
			static function () use ( &$fired ): void {
				++$fired;
			}
		);

		$this->sut->cancel_at_period_end( $this->reload( $id ) );

		$this->assertSame( 1, $fired );
	}

	public function test_rejects_a_terminal_contract(): void {
		$contract = Contract::create(
			array(
				'customer_id'     => 1,
				'status'          => ContractStatus::CANCELLED,
				'currency'        => 'USD',
				'selling_plan_id' => 1,
				'start_gmt'       => '2026-01-01 00:00:00',
				'billing_total'   => '19.99',
			)
		);
		$id       = $this->contracts->insert( $contract );

		$this->expectException( DomainException::class );
		$this->sut->cancel_at_period_end( $this->reload( $id ) );
	}

	public function test_cancel_clears_the_next_payment(): void {
		$id = $this->seed_active();

		$this->assertTrue( $this->sut->cancel( $this->reload( $id ) ) );

		$stored = $this->reload( $id );
		$this->assertSame( ContractStatus::CANCELLED, $stored->get_status() );
		$this->assertNull( $stored->get_next_payment_gmt() );
	}

	public function test_cancel_clears_the_next_payment_and_hold_anchor_of_a_held_contract(): void {
		$id = $this->seed_active();
		( new Hold( $this->contracts ) )->hold( $this->reload( $id ) );

		$this->sut->cancel( $this->reload( $id ) );

		$stored = $this->reload( $id );
		$this->assertSame( ContractStatus::CANCELLED, $stored->get_status() );
		$this->assertNull( $stored->get_next_payment_gmt() );
		$this->assertArrayNotHasKey( Hold::ANCHOR_META_KEY, $stored->get_meta() );
	}

	public function test_cancel_accepts_a_pending_cancellation_contract(): void {
		$id = $this->seed( ContractStatus::PENDING_CANCELLATION );

		$this->assertTrue( $this->sut->cancel( $this->reload( $id ) ) );

		$stored = $this->reload( $id );
		$this->assertSame( ContractStatus::CANCELLED, $stored->get_status() );
		$this->assertNull( $stored->get_next_payment_gmt() );
	}

	public function test_cancel_on_a_cancelled_contract_is_a_no_op_that_refires_the_action(): void {
		$id = $this->seed( ContractStatus::CANCELLED );

		$fired = 0;
		add_action(
			Cancellation::CONTRACT_CANCELLED_ACTION,
			static function () use ( &$fired ): void {
				++$fired;
			}
		);

		$this->assertTrue( $this->sut->cancel( $this->reload( $id ) ) );

		$this->assertSame( 1, $fired );
		$this->assertSame( ContractStatus::CANCELLED, $this->reload( $id )->get_status() );
	}

	public function test_cancel_rejects_an_expired_contract(): void {
		$id     = $this->seed( ContractStatus::EXPIRED );
		$before = did_action( Cancellation::CONTRACT_CANCELLED_ACTION );

		try {
			$this->sut->cancel( $this->reload( $id ) );
			$this->fail( 'Expected a DomainException for an expired contract.' );
		} catch ( DomainException $e ) {
			$this->assertSame( ContractStatus::EXPIRED, $this->reload( $id )->get_status() );
			$this->assertSame( $before, did_action( Cancellation::CONTRACT_CANCELLED_ACTION ), 'The cancelled action does not fire.' );
		}
	}

	public function test_cancel_rejects_an_unregistered_stored_status(): void {
		global $wpdb;

		$id = $this->seed_active();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS ), array( 'status' => 'legacy-paused' ), array( 'id' => $id ) );

		try {
			$this->sut->cancel( $this->reload( $id ) );
			$this->fail( 'Expected a DomainException for an unregistered stored status.' );
		} catch ( DomainException $e ) {
			$this->assertSame( 'legacy-paused', $this->reload( $id )->get_status() );
		}
	}

	public function test_cancel_at_period_end_ignores_a_malformed_hold_anchor(): void {
		$id = $this->seed( ContractStatus::ON_HOLD );
		$this->contracts->update( $this->with_hold_anchor( $this->reload( $id ), 'not-a-date' ) );

		$this->sut->cancel_at_period_end( $this->reload( $id ) );

		$stored = $this->reload( $id );
		$this->assertSame( ContractStatus::PENDING_CANCELLATION, $stored->get_status() );
		$this->assertSame( '2099-01-01 00:00:00', $stored->get_end_gmt(), 'The stored next payment, not the malformed anchor, is the period end.' );
		$this->assertArrayNotHasKey( Hold::ANCHOR_META_KEY, $stored->get_meta() );
	}

	public function test_cancel_at_period_end_from_on_hold_without_a_date_leaves_no_end(): void {
		$id   = $this->seed( ContractStatus::ON_HOLD );
		$held = $this->reload( $id );
		$held->set_next_payment_gmt( null );
		$this->contracts->update( $this->with_hold_anchor( $held, 'not-a-date' ) );

		$this->sut->cancel_at_period_end( $this->reload( $id ) );

		$stored = $this->reload( $id );
		$this->assertSame( ContractStatus::PENDING_CANCELLATION, $stored->get_status() );
		$this->assertNull( $stored->get_end_gmt(), 'A malformed anchor is never written as the end date.' );
	}

	/**
	 * Set the hold anchor meta on a contract.
	 *
	 * @param Contract $contract Contract to change.
	 * @param string   $anchor   Anchor value to store.
	 */
	private function with_hold_anchor( Contract $contract, string $anchor ): Contract {
		$contract->set_meta( Hold::ANCHOR_META_KEY, $anchor );

		return $contract;
	}

	public function test_cancel_at_period_end_rejects_an_expired_contract(): void {
		$id     = $this->seed( ContractStatus::EXPIRED );
		$before = did_action( Cancellation::CONTRACT_PENDING_CANCELLATION_ACTION );

		try {
			$this->sut->cancel_at_period_end( $this->reload( $id ) );
			$this->fail( 'Expected a DomainException for an expired contract.' );
		} catch ( DomainException $e ) {
			$reloaded = $this->reload( $id );
			$this->assertSame( ContractStatus::EXPIRED, $reloaded->get_status() );
			$this->assertSame( '2099-01-01 00:00:00', $reloaded->get_next_payment_gmt(), 'Nothing was written.' );
			$this->assertSame( $before, did_action( Cancellation::CONTRACT_PENDING_CANCELLATION_ACTION ), 'The action does not fire.' );
		}
	}

	public function test_cancel_at_period_end_rejects_an_unregistered_stored_status(): void {
		global $wpdb;

		$id = $this->seed_active();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS ), array( 'status' => 'legacy-paused' ), array( 'id' => $id ) );

		try {
			$this->sut->cancel_at_period_end( $this->reload( $id ) );
			$this->fail( 'Expected a DomainException for an unregistered stored status.' );
		} catch ( DomainException $e ) {
			$reloaded = $this->reload( $id );
			$this->assertSame( 'legacy-paused', $reloaded->get_status() );
			$this->assertSame( '2099-01-01 00:00:00', $reloaded->get_next_payment_gmt(), 'Nothing was written.' );
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
