<?php
/**
 * Integration tests for the Hold contract operation: ACTIVE -> ON_HOLD, the
 * next-due moment disarmed (kept as the hold anchor), and the held action fired.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Contracts;

use DomainException;
use EngineIntegrationTestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Hold;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Hold
 */
class HoldTest extends EngineIntegrationTestCase {

	/**
	 * @var ContractRepository
	 */
	private $contracts;

	/**
	 * @var Hold
	 */
	private $sut;

	public function set_up(): void {
		parent::set_up();
		$this->contracts = new ContractRepository();
		$this->sut       = new Hold( $this->contracts );
	}

	/**
	 * Seed a contract at a status with a next-payment date (a future one by default).
	 *
	 * @param string      $status       Contract status.
	 * @param string|null $next_payment Next-payment GMT string, or null.
	 */
	private function seed( string $status, ?string $next_payment = '2099-01-01 00:00:00' ): int {
		$contract = Contract::create(
			array(
				'customer_id'      => 1,
				'status'           => $status,
				'currency'         => 'USD',
				'selling_plan_id'  => 1,
				'start_gmt'        => '2026-01-01 00:00:00',
				'next_payment_gmt' => $next_payment,
				'billing_total'    => '19.99',
			)
		);

		return $this->contracts->insert( $contract );
	}

	public function test_hold_suspends_billing_by_moving_to_on_hold(): void {
		$id = $this->seed( ContractStatus::ACTIVE );

		$result = $this->sut->hold( $this->reload( $id ) );

		$this->assertTrue( $result );
		// No charge while held: hold disarms the next-due moment the batch due scan keys on.
		$this->assertSame( ContractStatus::ON_HOLD, $this->reload( $id )->get_status() );
	}

	public function test_hold_clears_the_next_payment_and_keeps_the_anchor(): void {
		$id = $this->seed( ContractStatus::ACTIVE );

		$this->sut->hold( $this->reload( $id ) );

		$held = $this->reload( $id );
		$this->assertNull( $held->get_next_payment_gmt() );
		$this->assertSame( '2099-01-01 00:00:00', $held->get_meta()[ Hold::ANCHOR_META_KEY ] ?? null );
	}

	public function test_hold_without_a_next_payment_stores_no_anchor(): void {
		$id = $this->seed( ContractStatus::ACTIVE, null );

		$this->sut->hold( $this->reload( $id ) );

		$held = $this->reload( $id );
		$this->assertSame( ContractStatus::ON_HOLD, $held->get_status() );
		$this->assertNull( $held->get_next_payment_gmt() );
		$this->assertArrayNotHasKey( Hold::ANCHOR_META_KEY, $held->get_meta() );
	}

	public function test_hold_on_an_on_hold_contract_is_an_idempotent_no_op(): void {
		$id = $this->seed( ContractStatus::ACTIVE );
		$this->sut->hold( $this->reload( $id ) );

		$fired = 0;
		add_action(
			Hold::CONTRACT_HELD_ACTION,
			static function () use ( &$fired ): void {
				++$fired;
			}
		);

		// A second hold must not wipe the anchor stashed by the first.
		$this->assertTrue( $this->sut->hold( $this->reload( $id ) ) );

		$held = $this->reload( $id );
		$this->assertSame( 1, $fired );
		$this->assertSame( ContractStatus::ON_HOLD, $held->get_status() );
		$this->assertNull( $held->get_next_payment_gmt() );
		$this->assertSame( '2099-01-01 00:00:00', $held->get_meta()[ Hold::ANCHOR_META_KEY ] ?? null );
	}

	public function test_hold_fires_the_held_action(): void {
		$id    = $this->seed( ContractStatus::ACTIVE );
		$fired = 0;
		add_action(
			Hold::CONTRACT_HELD_ACTION,
			static function () use ( &$fired ): void {
				++$fired;
			}
		);

		$this->sut->hold( $this->reload( $id ) );

		$this->assertSame( 1, $fired );
	}

	public function test_hold_loses_the_race_to_a_concurrent_transition(): void {
		$id       = $this->seed( ContractStatus::ACTIVE );
		$contract = $this->reload( $id );

		// A concurrent request cancels the contract after our read: the compare-and-set
		// write must miss loudly instead of resurrecting the contract to on-hold.
		$concurrent = $this->reload( $id );
		$concurrent->set_status( ContractStatus::CANCELLED );
		$this->contracts->update( $concurrent );

		try {
			$this->sut->hold( $contract );
			$this->fail( 'Expected a DomainException when the conditional write misses.' );
		} catch ( DomainException $e ) {
			$this->assertSame( ContractStatus::CANCELLED, $this->reload( $id )->get_status(), 'The concurrent cancel is not clobbered.' );
		}
	}

	/**
	 * The anchor is stored and read back before the hold disarms the contract, so a failed
	 * meta write aborts the hold with nothing disarmed instead of leaving a held contract
	 * that reactivation could not re-arm.
	 */
	public function test_hold_aborts_without_disarming_when_the_anchor_cannot_be_stored(): void {
		$id         = $this->seed( ContractStatus::ACTIVE );
		$meta_table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_META );
		$break      = static function ( string $query ) use ( $meta_table ): string {
			return 0 === strpos( $query, "INSERT INTO `{$meta_table}`" ) ? 'SELECT broken syntax (' : $query;
		};
		add_filter( 'query', $break );

		try {
			$this->sut->hold( $this->reload( $id ) );
			$this->fail( 'Expected the hold to abort when the anchor cannot be stored.' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'anchor could not be stored', $e->getMessage() );
		} finally {
			remove_filter( 'query', $break );
		}

		$stored = $this->reload( $id );
		$this->assertSame( ContractStatus::ACTIVE, $stored->get_status() );
		$this->assertSame( '2099-01-01 00:00:00', $stored->get_next_payment_gmt(), 'The next-due moment was not disarmed.' );
	}

	public function test_hold_rejects_a_cancelled_contract(): void {
		$id = $this->seed( ContractStatus::CANCELLED );

		$this->expectException( DomainException::class );
		$this->sut->hold( $this->reload( $id ) );
	}

	public function test_hold_rejects_a_pending_cancellation_contract(): void {
		$id = $this->seed( ContractStatus::PENDING_CANCELLATION );

		$this->expectException( DomainException::class );
		$this->sut->hold( $this->reload( $id ) );
	}

	public function test_hold_rejects_an_unregistered_stored_status(): void {
		global $wpdb;

		$id = $this->seed( ContractStatus::ACTIVE );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS ), array( 'status' => 'legacy-paused' ), array( 'id' => $id ) );

		try {
			$this->sut->hold( $this->reload( $id ) );
			$this->fail( 'Expected a DomainException for an unregistered stored status.' );
		} catch ( DomainException $e ) {
			$this->assertSame( 'legacy-paused', $this->reload( $id )->get_status() );
			$this->assertSame( '2099-01-01 00:00:00', $this->reload( $id )->get_next_payment_gmt() );
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
