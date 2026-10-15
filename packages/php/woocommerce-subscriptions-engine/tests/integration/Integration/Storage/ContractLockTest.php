<?php
/**
 * Integration tests for the per-contract lock primitives in ContractRepository.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Storage;

use EngineIntegrationTestCase;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLock;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLockedException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractLockState;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLock
 */
class ContractLockTest extends EngineIntegrationTestCase {

	/**
	 * The System Under Test.
	 *
	 * @var ContractRepository
	 */
	private $sut;

	public function setUp(): void {
		parent::setUp();
		$this->sut = new ContractRepository();
	}

	private function insert_contract( string $next_payment_gmt = '2026-06-15 00:00:00' ): int {
		return $this->sut->insert(
			Contract::create(
				array(
					'extension_slug'   => 'engine-tests',
					'status'           => ContractStatus::ACTIVE,
					'next_payment_gmt' => $next_payment_gmt,
				)
			)
		);
	}

	/**
	 * The raw lock columns of a contract row.
	 *
	 * @param int $id Contract id.
	 * @return array<mixed>
	 */
	private function lock_columns( int $id ): array {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT lock_token, locked_until_gmt, lock_operation FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		$this->assertIsArray( $row );

		return $row;
	}

	/**
	 * End a lock's trust window and make its local expiry estimate read as near.
	 *
	 * @param ContractLock $lock The lock.
	 */
	private function age_to_near_expiry( ContractLock $lock ): void {
		$estimate = new ReflectionProperty( ContractLockState::class, 'expires_estimate' );
		$estimate->setAccessible( true );
		$estimate->setValue( $this->lock_state( $lock ), 0.0 );
		$this->age_lock_trust( $lock );
	}

	private function acquire( int $id, string $operation = 'hold' ): ContractLock {
		$lock = $this->sut->acquire_lock( $id, $operation );
		$this->assertInstanceOf( ContractLock::class, $lock );

		return $lock;
	}

	/**
	 * @testdox A lock is acquired once; a second acquire returns null while it lives.
	 */
	public function test_acquire_wins_once(): void {
		$id = $this->insert_contract();
		$this->pin_db_clock( '2035-01-01 00:00:00' );

		$lock = $this->acquire( $id );

		$this->assertSame( $id, $lock->get_contract_id() );
		$this->assertSame( 'hold', $lock->get_operation() );
		$this->assertNull( $this->sut->acquire_lock( $id, 'cancel' ) );
		$this->assertSame(
			array(
				'exists'           => true,
				'operation'        => 'hold',
				'locked_until_gmt' => '2035-01-01 00:10:00',
			),
			$this->sut->get_lock_holder( $id )
		);
	}

	/**
	 * @testdox An expired lock is taken over, and the old holder's row writes are then refused.
	 */
	public function test_an_expired_lock_is_taken_over(): void {
		$id = $this->insert_contract();
		$this->pin_db_clock( '2035-01-01 00:00:00' );
		$old = $this->acquire( $id );

		$this->pin_db_clock( '2035-01-01 00:09:59' );
		$this->assertNull( $this->sut->acquire_lock( $id, 'cancel' ), 'Still live one second before expiry.' );

		$this->pin_db_clock( '2035-01-01 00:10:00' );
		$new = $this->acquire( $id, 'cancel' );
		$this->assertSame( 'cancel', $this->sut->get_lock_holder( $id )['operation'] );

		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_status( ContractStatus::ON_HOLD );

		try {
			$this->sut->update_fields( $contract, array( 'status' ), $old );
			$this->fail( 'Expected the taken-over lock to be refused.' );
		} catch ( ContractLockedException $e ) {
			$this->assertSame( 'cancel', $e->get_operation() );
		}

		$this->assertTrue( $this->sut->update_fields( $contract, array( 'status' ), $new ) );
	}

	/**
	 * @testdox An acquire bound to a seen due moment fails once the due moment moved.
	 */
	public function test_acquire_with_a_seen_due_moment(): void {
		$id = $this->insert_contract( '2026-06-20 00:00:00' );

		$this->assertNull( $this->sut->acquire_lock( $id, 'due', '2026-06-15 00:00:00' ) );
		$this->assertNull( $this->sut->get_lock_holder( $id )['operation'] );
		$this->assertInstanceOf( ContractLock::class, $this->sut->acquire_lock( $id, 'due', '2026-06-20 00:00:00' ) );
	}

	/**
	 * @testdox An unknown contract cannot be locked and reads as missing.
	 */
	public function test_unknown_contract(): void {
		$this->assertNull( $this->sut->acquire_lock( 999999, 'hold' ) );
		$this->assertFalse( $this->sut->get_lock_holder( 999999 )['exists'] );
	}

	/**
	 * @testdox Operation labels must be 1-64 characters.
	 */
	public function test_operation_label_is_validated(): void {
		$id = $this->insert_contract();

		foreach ( array( '', str_repeat( 'o', 65 ) ) as $operation ) {
			try {
				$this->sut->acquire_lock( $id, $operation );
				$this->fail( 'Expected an invalid label to be refused.' );
			} catch ( InvalidArgumentException $e ) {
				$this->assertNull( $this->sut->get_lock_holder( $id )['operation'] );
			}
		}

		$this->assertInstanceOf( ContractLock::class, $this->sut->acquire_lock( $id, str_repeat( 'o', 64 ) ) );
	}

	/**
	 * @testdox Release clears the token and operation and keeps the release moment; it is idempotent.
	 */
	public function test_release_records_the_release_moment(): void {
		$id = $this->insert_contract();
		$this->pin_db_clock( '2035-01-01 00:00:00' );
		$lock = $this->acquire( $id );

		$this->pin_db_clock( '2035-01-01 00:02:00' );
		$lock->release();
		$lock->release();

		$this->assertSame(
			array(
				'lock_token'       => null,
				'locked_until_gmt' => '2035-01-01 00:02:00',
				'lock_operation'   => null,
			),
			$this->lock_columns( $id )
		);
		$this->assertTrue( $this->lock_state( $lock )->is_released() );
		$this->assertInstanceOf( ContractLock::class, $this->sut->acquire_lock( $id, 'cancel' ), 'A released contract can be locked again at once.' );
	}

	/**
	 * @testdox Releasing a taken-over lock does not clear the new holder.
	 */
	public function test_release_is_token_guarded(): void {
		$id = $this->insert_contract();
		$this->pin_db_clock( '2035-01-01 00:00:00' );
		$old = $this->acquire( $id );

		$this->pin_db_clock( '2035-01-01 00:10:00' );
		$this->acquire( $id, 'cancel' );
		$old->release();

		$holder = $this->sut->get_lock_holder( $id );
		$this->assertNotNull( $holder['operation'] );
		$this->assertSame( 'cancel', $holder['operation'] );
	}

	/**
	 * @testdox The fence extends a lock near expiry even in the second it was acquired.
	 */
	public function test_fence_extends_in_the_acquire_second(): void {
		$id = $this->insert_contract();
		$this->pin_db_clock( '2035-01-01 00:00:00' );
		$lock = $this->acquire( $id );
		$this->age_to_near_expiry( $lock );

		$this->sut->fence_children( $lock );

		$this->assertTrue( $this->lock_state( $lock )->has_extended() );
		$this->assertSame( 'hold', $this->sut->get_lock_holder( $id )['operation'] );
	}

	/**
	 * @testdox The fence's extend of a lost lock throws ContractLockedException.
	 */
	public function test_fence_extend_of_a_lost_lock_throws(): void {
		$id = $this->insert_contract();
		$this->pin_db_clock( '2035-01-01 00:00:00' );
		$lock = $this->acquire( $id );
		$this->age_to_near_expiry( $lock );

		$this->pin_db_clock( '2035-01-01 00:10:00' );

		$this->expectException( ContractLockedException::class );
		$this->sut->fence_children( $lock );
	}

	/**
	 * @testdox The child fence trusts a fresh lock without a query, extends once past the window, then verifies.
	 */
	public function test_fence_children_trust_extend_verify(): void {
		global $wpdb;

		$id = $this->insert_contract();
		$this->pin_db_clock( '2035-01-01 00:00:00' );
		$lock = $this->acquire( $id );

		$queries = $wpdb->num_queries;
		$this->sut->fence_children( $lock );
		$this->assertSame( $queries, $wpdb->num_queries, 'A trusted lock costs no query.' );

		$this->pin_db_clock( '2035-01-01 00:09:30' );
		$this->age_lock_trust( $lock );
		$this->sut->fence_children( $lock );
		$this->assertTrue( $this->lock_state( $lock )->has_extended(), 'Past the window the fence extends once.' );
		$this->assertSame( '2035-01-01 00:19:30', $this->sut->get_lock_holder( $id )['locked_until_gmt'] );
		$this->assertTrue( $this->lock_state( $lock )->is_trusted() );

		$this->pin_db_clock( '2035-01-01 00:15:00' );
		$this->age_lock_trust( $lock );
		$this->sut->fence_children( $lock );
		$this->assertTrue( $this->lock_state( $lock )->is_trusted(), 'With 270 s left the verify trusts it again.' );

		$this->pin_db_clock( '2035-01-01 00:18:31' );
		$this->age_lock_trust( $lock );
		$this->expectException( ContractLockedException::class );
		$this->sut->fence_children( $lock );
	}

	/**
	 * @testdox The child fence refuses an extended lock whose stored lock expired.
	 */
	public function test_fence_children_refuses_an_expired_lock(): void {
		$id = $this->insert_contract();
		$this->pin_db_clock( '2035-01-01 00:00:00' );
		$lock = $this->acquire( $id );
		$this->age_to_near_expiry( $lock );
		$this->sut->fence_children( $lock );
		$this->assertTrue( $this->lock_state( $lock )->has_extended() );

		$this->pin_db_clock( '2035-01-01 00:10:00' );
		$this->age_lock_trust( $lock );

		$this->expectException( ContractLockedException::class );
		$this->sut->fence_children( $lock );
	}

	/**
	 * @testdox The child fence refuses a released lock.
	 */
	public function test_fence_children_refuses_a_released_lock(): void {
		$lock = $this->acquire( $this->insert_contract() );
		$lock->release();

		$this->expectException( ContractLockedException::class );
		$this->sut->fence_children( $lock );
	}

	/**
	 * @testdox Past the window, a lock near expiry by the local estimate is extended without a verify read.
	 */
	public function test_fence_children_extends_a_lock_near_expiry_by_the_local_estimate(): void {
		global $wpdb;

		$id = $this->insert_contract();
		$this->pin_db_clock( '2035-01-01 00:00:00' );
		$lock = $this->acquire( $id );
		$this->age_to_near_expiry( $lock );

		$this->pin_db_clock( '2035-01-01 00:08:30' );
		$queries = $wpdb->num_queries;
		$this->sut->fence_children( $lock );

		$this->assertSame( 1, $wpdb->num_queries - $queries, 'One extend write, no verify read.' );
		$this->assertTrue( $this->lock_state( $lock )->has_extended() );
		$this->assertSame( '2035-01-01 00:18:30', $this->sut->get_lock_holder( $id )['locked_until_gmt'] );
	}

	/**
	 * @testdox A lock taken over after expiry is refused on its first child write past the trust window.
	 */
	public function test_a_taken_over_lock_is_refused_on_the_first_child_write_past_the_window(): void {
		$id = $this->insert_contract();
		$this->pin_db_clock( '2035-01-01 00:00:00' );
		$old = $this->acquire( $id );

		$this->pin_db_clock( '2035-01-01 00:10:00' );
		$this->acquire( $id, 'cancel' );
		$this->age_lock_trust( $old );

		try {
			$this->sut->add_meta( $id, '_stale', 'x', false, $old );
			$this->fail( 'Expected the taken-over lock to be refused.' );
		} catch ( ContractLockedException $e ) {
			$this->assertSame( 'cancel', $e->get_operation() );
		}

		$this->assertSame( array(), $this->sut->get_meta( $id, '_stale' ) );
	}

	/**
	 * @testdox A lock forged through reflection cannot write child rows once its trust is checked.
	 */
	public function test_a_forged_lock_cannot_write_child_rows_past_a_verify(): void {
		$id = $this->insert_contract();
		$this->pin_db_clock( '2035-01-01 00:00:00' );
		$this->acquire( $id );

		$forged = ( new ReflectionClass( ContractLock::class ) )->newInstanceWithoutConstructor();
		foreach ( array(
			'contract_id' => $id,
			'operation'   => 'forged',
			'state'       => new ContractLockState( str_repeat( 'f', 32 ) ),
		) as $name => $value ) {
			$property = new ReflectionProperty( ContractLock::class, $name );
			$property->setAccessible( true );
			$property->setValue( $forged, $value );
		}
		$this->age_lock_trust( $forged );

		try {
			$this->sut->add_meta( $id, '_forged', 'x', false, $forged );
			$this->fail( 'Expected the forged lock to be refused.' );
		} catch ( ContractLockedException $e ) {
			$this->assertSame( 'hold', $e->get_operation() );
		}

		$this->assertSame( array(), $this->sut->get_meta( $id, '_forged' ) );
	}

	/**
	 * @testdox An unlocked row write refused by the fence throws even when the lock is gone by the re-read.
	 */
	public function test_a_refused_unlocked_update_throws(): void {
		$id       = $this->insert_contract();
		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_status( ContractStatus::ON_HOLD );

		$this->refuse_next_write( 'UPDATE `' . SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS ) . '` SET' );

		$this->expectException( ContractLockedException::class );
		$this->sut->update_fields( $contract, array( 'status' ) );
	}

	/**
	 * @testdox An unlocked meta update refused by the fence throws; an update to the stored value is a no-op.
	 */
	public function test_a_refused_unlocked_update_meta_throws(): void {
		$id = $this->insert_contract();
		$this->sut->add_meta( $id, '_anchor', 'a' );

		$this->assertFalse( $this->sut->update_meta( $id, '_anchor', 'a' ), 'The stored value: nothing to change.' );

		// The update runs again after its insert finds the key, so both attempts are refused.
		$this->refuse_next_write( 'UPDATE ' . SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_META ) . ' m', 2 );
		try {
			$this->sut->update_meta( $id, '_anchor', 'b' );
			$this->fail( 'Expected the refused update to throw.' );
		} catch ( ContractLockedException $e ) {
			$this->assertNull( $e->get_operation() );
		}

		$this->assertSame( array( 'a' ), $this->sut->get_meta( $id, '_anchor' ) );
	}

	/**
	 * @testdox An unlocked meta delete refused by the fence throws; deleting an absent key is a no-op.
	 */
	public function test_a_refused_unlocked_delete_meta_throws(): void {
		$id = $this->insert_contract();
		$this->sut->add_meta( $id, '_anchor', 'a' );

		$this->assertFalse( $this->sut->delete_meta( $id, '_absent' ) );
		$this->assertFalse( $this->sut->delete_meta( $id, '_anchor', 'other' ) );

		$this->refuse_next_write( 'DELETE m FROM ' . SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_META ) );

		$this->expectException( ContractLockedException::class );
		$this->sut->delete_meta( $id, '_anchor' );
	}

	/**
	 * @testdox An unlocked cycle transition refused by the fence throws; a lost compare-and-set returns false.
	 */
	public function test_a_refused_unlocked_transition_throws(): void {
		$id    = $this->insert_contract();
		$cycle = Cycle::create(
			array(
				'contract_id'   => $id,
				'starts_at_gmt' => '2026-07-15 00:00:00',
				'ends_at_gmt'   => '2026-08-15 00:00:00',
				'currency'      => 'USD',
			)
		);
		$this->sut->append_cycle( $cycle );
		$cycle_id = (int) $cycle->get_id();

		$this->assertTrue( $this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, CycleStatus::PENDING, array( 'order_id' => 5 ) ) );
		$this->assertFalse( $this->sut->transition_cycle( $cycle_id, CycleStatus::FAILED, CycleStatus::PENDING, array() ), 'A lost compare-and-set.' );

		$this->refuse_next_write( 'UPDATE ' . SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES ) . ' cy' );
		try {
			$this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, CycleStatus::BILLED, array() );
			$this->fail( 'Expected the refused transition to throw.' );
		} catch ( ContractLockedException $e ) {
			$this->assertSame( $id, $e->get_contract_id() );
		}

		$this->refuse_next_write( 'UPDATE ' . SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES ) . ' cy' );
		$this->expectException( ContractLockedException::class );
		$this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, CycleStatus::PENDING, array( 'order_id' => 6 ) );
	}

	/**
	 * @testdox Reads expose the lock state only while the lock lives, and never the token.
	 */
	public function test_reads_expose_lock_state_not_token(): void {
		$id = $this->insert_contract();
		$this->pin_db_clock( '2035-01-01 00:00:00' );

		$unlocked = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $unlocked );
		$this->assertNull( $unlocked->get_lock_state() );

		$this->acquire( $id );
		$token = $this->lock_columns( $id )['lock_token'];
		$this->assertIsString( $token );

		$reads = array(
			$this->sut->find( $id ),
			$this->sut->find_summary( $id ),
			$this->sut->query( array( 'search' => (string) $id ) )[0] ?? null,
		);
		foreach ( $reads as $contract ) {
			$this->assertInstanceOf( Contract::class, $contract );
			$this->assertSame(
				array(
					'locked_until_gmt' => '2035-01-01 00:10:00',
					'operation'        => 'hold',
				),
				$contract->get_lock_state()
			);
			$this->assertStringNotContainsString( $token, print_r( $contract, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			$this->assertStringNotContainsString( $token, (string) wp_json_encode( $contract->to_storage() ) );
		}

		$this->pin_db_clock( '2035-01-01 00:10:00' );
		$expired = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $expired );
		$this->assertNull( $expired->get_lock_state(), 'An expired lock reads as unlocked.' );
	}
}
