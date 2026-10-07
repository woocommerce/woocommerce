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
use Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Subscriptions;
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
				'extension_slug'   => 'engine-tests',
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
		$this->assertSame( '', $this->contracts->get_meta( $id, Hold::ANCHOR_META_KEY, true ) );
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
				'extension_slug'  => 'engine-tests',
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
		$this->assertSame( '', $this->contracts->get_meta( $id, Hold::ANCHOR_META_KEY, true ) );
	}

	/**
	 * The anchor is cleared after the status write has committed, so a failed delete must
	 * not abort the transition: the lifecycle action still fires.
	 *
	 * @dataProvider provide_cancel_modes
	 *
	 * @param string $method Cancellation method under test.
	 * @param string $action Action the method fires.
	 * @param string $status Status the method writes.
	 */
	public function test_a_failed_anchor_clear_does_not_abort_the_transition( string $method, string $action, string $status ): void {
		$id = $this->seed_active();
		( new Hold( $this->contracts ) )->hold( $this->reload( $id ) );

		$fired = 0;
		add_action(
			$action,
			static function () use ( &$fired ): void {
				++$fired;
			}
		);
		$break = $this->break_meta_deletes();

		try {
			$this->assertTrue( $this->sut->$method( $this->reload( $id ) ) );
		} finally {
			remove_filter( 'query', $break );
		}

		$this->assertSame( 1, $fired, 'The lifecycle action fires.' );
		$this->assertSame( $status, $this->reload( $id )->get_status() );
		$this->assertSame( '2099-01-01 00:00:00', $this->contracts->get_meta( $id, Hold::ANCHOR_META_KEY, true ), 'The anchor is left behind.' );
	}

	/**
	 * A transition that loses its compare-and-set leaves the hold anchor in place, so the
	 * contract that won can still resume from it.
	 *
	 * @dataProvider provide_cancel_modes
	 *
	 * @param string $method Cancellation method under test.
	 */
	public function test_a_lost_race_keeps_the_hold_anchor( string $method ): void {
		$id = $this->seed_active();
		( new Hold( $this->contracts ) )->hold( $this->reload( $id ) );
		$stale = $this->reload( $id );

		$concurrent = $this->reload( $id );
		$concurrent->set_status( ContractStatus::EXPIRED );
		$this->contracts->update( $concurrent );

		try {
			$this->sut->$method( $stale );
			$this->fail( 'Expected a DomainException when the conditional write misses.' );
		} catch ( DomainException $e ) {
			$this->assertSame( '2099-01-01 00:00:00', $this->contracts->get_meta( $id, Hold::ANCHOR_META_KEY, true ) );
		}
	}

	/**
	 * Both cancellation modes: method, action, resulting status.
	 *
	 * @return array<string, array{0: string, 1: string, 2: string}>
	 */
	public function provide_cancel_modes(): array {
		return array(
			'cancel'               => array( 'cancel', Cancellation::CONTRACT_CANCELLED_ACTION, ContractStatus::CANCELLED ),
			'cancel at period end' => array( 'cancel_at_period_end', Cancellation::CONTRACT_PENDING_CANCELLATION_ACTION, ContractStatus::PENDING_CANCELLATION ),
		);
	}

	/**
	 * Make every contract meta DELETE fail until the returned filter is removed.
	 *
	 * @return callable The `query` filter to remove.
	 */
	private function break_meta_deletes(): callable {
		$meta_table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_META );
		$break      = static function ( string $query ) use ( $meta_table ): string {
			return 0 === strpos( $query, "DELETE FROM `{$meta_table}`" ) ? 'SELECT broken syntax (' : $query;
		};
		add_filter( 'query', $break );

		return $break;
	}

	public function test_cancel_accepts_a_pending_cancellation_contract(): void {
		$id = $this->seed( ContractStatus::PENDING_CANCELLATION );

		$this->assertTrue( $this->sut->cancel( $this->reload( $id ) ) );

		$stored = $this->reload( $id );
		$this->assertSame( ContractStatus::CANCELLED, $stored->get_status() );
		$this->assertNull( $stored->get_next_payment_gmt() );
	}

	public function test_cancel_accepts_a_draft_contract(): void {
		// A stuck draft (created, never activated) has no cycles and no due moment.
		$id = Contracts::create(
			array(
				'extension_slug' => 'test-owner',
				'customer_id'    => 1,
				'currency'       => 'USD',
			)
		);
		$this->assertSame( ContractStatus::DRAFT, $this->reload( $id )->get_status() );

		$fired = 0;
		add_action(
			Cancellation::CONTRACT_CANCELLED_ACTION,
			static function () use ( &$fired ): void {
				++$fired;
			}
		);

		$this->assertTrue( Subscriptions::cancel( $id ) );

		$stored = $this->reload( $id );
		$this->assertSame( ContractStatus::CANCELLED, $stored->get_status() );
		$this->assertNull( $stored->get_next_payment_gmt() );
		$this->assertSame( 1, $fired );
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
		$this->contracts->update_meta( $id, Hold::ANCHOR_META_KEY, 'not-a-date' );

		$this->sut->cancel_at_period_end( $this->reload( $id ) );

		$stored = $this->reload( $id );
		$this->assertSame( ContractStatus::PENDING_CANCELLATION, $stored->get_status() );
		$this->assertSame( '2099-01-01 00:00:00', $stored->get_end_gmt(), 'The stored next payment, not the malformed anchor, is the period end.' );
		$this->assertSame( '', $this->contracts->get_meta( $id, Hold::ANCHOR_META_KEY, true ) );
	}

	public function test_cancel_at_period_end_from_on_hold_without_a_date_leaves_no_end(): void {
		$id   = $this->seed( ContractStatus::ON_HOLD );
		$held = $this->reload( $id );
		$held->set_next_payment_gmt( null );
		$this->contracts->update( $held );
		$this->contracts->update_meta( $id, Hold::ANCHOR_META_KEY, 'not-a-date' );

		$this->sut->cancel_at_period_end( $this->reload( $id ) );

		$stored = $this->reload( $id );
		$this->assertSame( ContractStatus::PENDING_CANCELLATION, $stored->get_status() );
		$this->assertNull( $stored->get_end_gmt(), 'A malformed anchor is never written as the end date.' );
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
