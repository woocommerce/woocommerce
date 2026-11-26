<?php
/**
 * Integration tests for the contract history writes and reads on ContractRepository.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Storage;

use EngineIntegrationTestCase;
use InvalidArgumentException;
use RuntimeException;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLock;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLockedException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\HistoryEntry;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository
 */
class ContractHistoryTest extends EngineIntegrationTestCase {

	/**
	 * Owning extension of the fixture contracts.
	 */
	private const OWNER = 'acme-subs';

	/**
	 * The System Under Test.
	 *
	 * @var ContractRepository
	 */
	private $sut;

	/**
	 * A stored contract id of {@see self::OWNER}.
	 *
	 * @var int
	 */
	private $id;

	public function setUp(): void {
		parent::setUp();
		$this->sut = new ContractRepository();
		$this->id  = $this->insert_released( Contract::create( array( 'extension_slug' => self::OWNER ) ) );
	}

	/**
	 * @testdox With the caller's lock, the entry is written and returned with its id and UTC creation time.
	 */
	public function test_a_locked_write_stores_the_entry(): void {
		$cycle_id = $this->add_cycle( $this->id );
		$lock     = $this->sut->acquire_lock( $this->id, self::OWNER, 'renew' );
		$this->assertInstanceOf( ContractLock::class, $lock );

		$before = gmdate( 'Y-m-d H:i:s' );
		$stored = $this->sut->add_history_entry(
			$this->entry(
				array(
					'cycle_id' => $cycle_id,
					'type'     => 'renewal-paid',
					'payload'  => array( 'actor' => array( 'role' => 'system' ) ),
				)
			),
			self::OWNER,
			$lock
		);
		$after  = gmdate( 'Y-m-d H:i:s' );

		$this->assertInstanceOf( HistoryEntry::class, $stored );
		$this->assertIsInt( $stored->get_id() );
		$this->assertSame( $this->id, $stored->get_contract_id() );
		$this->assertSame( $cycle_id, $stored->get_cycle_id() );
		$this->assertSame( 'renewal-paid', $stored->get_type() );
		$this->assertSame( array( 'actor' => array( 'role' => 'system' ) ), $stored->get_payload() );
		$created = (string) $stored->get_date_created_gmt();
		$this->assertGreaterThanOrEqual( $before, $created );
		$this->assertLessThanOrEqual( $after, $created );

		$this->assertEquals( array( $stored ), $this->sut->find_history( $this->id ), 'The returned entry matches a fresh read.' );
	}

	/**
	 * @testdox With a lost lock, the write throws ContractLockedException and writes nothing.
	 */
	public function test_a_lost_lock_refuses_the_write(): void {
		$this->pin_db_clock( '2035-01-01 00:00:00' );
		$old = $this->sut->acquire_lock( $this->id, self::OWNER, 'renew' );
		$this->assertInstanceOf( ContractLock::class, $old );

		$this->pin_db_clock( '2035-01-01 00:10:00' );
		$this->assertInstanceOf( ContractLock::class, $this->sut->acquire_lock( $this->id, self::OWNER, 'cancel' ) );
		$this->age_lock_trust( $old );

		try {
			$this->sut->add_history_entry( $this->entry(), self::OWNER, $old );
			$this->fail( 'Expected the lost lock to be refused.' );
		} catch ( ContractLockedException $e ) {
			$this->assertSame( 'cancel', $e->get_operation() );
		}

		$this->assertSame( array(), $this->sut->find_history( $this->id ) );
	}

	/**
	 * @testdox Without a lock, the entry is written in one insert and no lock is taken.
	 */
	public function test_an_unlocked_write_takes_no_lock(): void {
		$stored     = null;
		$statements = $this->statements_during(
			function () use ( &$stored ): void {
				$stored = $this->sut->add_history_entry( $this->entry(), self::OWNER );
			}
		);

		$this->assertInstanceOf( HistoryEntry::class, $stored );
		$this->assertCount( 1, $statements, 'One statement.' );
		$this->assertStringStartsWith( 'INSERT INTO ' . SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_HISTORY ), $statements[0] );
		$this->assertNull( $this->get_lock_holder( $this->id )['operation'], 'No lock is held.' );
		$this->assertInstanceOf( ContractLock::class, $this->sut->acquire_lock( $this->id, self::OWNER, 'hold' ), 'The contract is lockable.' );
	}

	/**
	 * @testdox Without a lock, the entry is written while another operation holds the contract's lock.
	 */
	public function test_an_unlocked_write_lands_while_another_operation_holds_the_lock(): void {
		$this->assertInstanceOf( ContractLock::class, $this->sut->acquire_lock( $this->id, self::OWNER, 'renew' ) );

		$stored = $this->sut->add_history_entry( $this->entry( array( 'type' => 'note' ) ), self::OWNER );

		$this->assertInstanceOf( HistoryEntry::class, $stored );
		$this->assertCount( 1, $this->sut->find_history( $this->id ) );
		$this->assertSame( 'renew', $this->get_lock_holder( $this->id )['operation'], 'The held lock is untouched.' );
	}

	/**
	 * @testdox Without a lock, an unknown contract or one of another extension is refused and nothing is written.
	 */
	public function test_an_unlocked_write_to_a_missing_or_foreign_contract_is_refused(): void {
		$other = $this->insert_released( Contract::create( array( 'extension_slug' => 'another-extension' ) ) );

		foreach ( array( 999999, $other ) as $contract_id ) {
			try {
				$this->sut->add_history_entry( $this->entry( array( 'contract_id' => $contract_id ) ), self::OWNER );
				$this->fail( "Expected contract {$contract_id} to be refused." );
			} catch ( InvalidArgumentException $e ) {
				$this->assertStringContainsString( "has id {$contract_id}.", $e->getMessage() );
			}
			$this->assertSame( array(), $this->sut->find_history( $contract_id ) );
		}
	}

	/**
	 * @testdox With a lock, a lock for another contract or another extension is refused and nothing is written.
	 */
	public function test_a_lock_for_another_contract_or_extension_is_refused(): void {
		$sibling      = $this->insert_released( Contract::create( array( 'extension_slug' => self::OWNER ) ) );
		$sibling_lock = $this->sut->acquire_lock( $sibling, self::OWNER, 'hold' );
		$this->assertInstanceOf( ContractLock::class, $sibling_lock );

		try {
			$this->sut->add_history_entry( $this->entry(), self::OWNER, $sibling_lock );
			$this->fail( 'Expected a lock for another contract to be refused.' );
		} catch ( InvalidArgumentException $e ) {
			$this->assertStringContainsString( "The lock belongs to contract {$sibling}, not contract {$this->id}.", $e->getMessage() );
		}

		$foreign      = $this->insert_released( Contract::create( array( 'extension_slug' => 'another-extension' ) ) );
		$foreign_lock = $this->sut->acquire_lock( $foreign, 'another-extension', 'hold' );
		$this->assertInstanceOf( ContractLock::class, $foreign_lock );

		try {
			$this->sut->add_history_entry( $this->entry( array( 'contract_id' => $foreign ) ), self::OWNER, $foreign_lock );
			$this->fail( 'Expected a lock for a contract of another extension to be refused.' );
		} catch ( InvalidArgumentException $e ) {
			$this->assertStringContainsString( 'another extension', $e->getMessage() );
		}

		$this->assertSame( array(), $this->sut->find_history( $this->id ) );
		$this->assertSame( array(), $this->sut->find_history( $sibling ) );
		$this->assertSame( array(), $this->sut->find_history( $foreign ) );
	}

	/**
	 * @testdox A cycle of another contract, or an unknown cycle, is refused with and without a lock; a cycle of this contract is stored.
	 */
	public function test_the_cycle_must_belong_to_the_contract(): void {
		$other       = $this->insert_released( Contract::create( array( 'extension_slug' => self::OWNER ) ) );
		$other_cycle = $this->add_cycle( $other );
		$own_cycle   = $this->add_cycle( $this->id );
		$lock        = $this->sut->acquire_lock( $this->id, self::OWNER, 'renew' );
		$this->assertInstanceOf( ContractLock::class, $lock );

		foreach ( array( $other_cycle, 999999 ) as $cycle_id ) {
			$modes = array(
				'locked'   => $lock,
				'unlocked' => null,
			);
			foreach ( $modes as $mode => $with ) {
				try {
					$this->sut->add_history_entry( $this->entry( array( 'cycle_id' => $cycle_id ) ), self::OWNER, $with );
					$this->fail( "Expected cycle {$cycle_id} to be refused ({$mode})." );
				} catch ( InvalidArgumentException $e ) {
					$this->assertStringContainsString( "cycle {$cycle_id}", strtolower( $e->getMessage() ), $mode );
				}
			}
		}
		$this->assertSame( array(), $this->sut->find_history( $this->id ), 'Nothing was written.' );

		$locked   = $this->sut->add_history_entry( $this->entry( array( 'cycle_id' => $own_cycle ) ), self::OWNER, $lock );
		$unlocked = $this->sut->add_history_entry( $this->entry( array( 'cycle_id' => $own_cycle ) ), self::OWNER );
		$this->assertInstanceOf( HistoryEntry::class, $locked );
		$this->assertInstanceOf( HistoryEntry::class, $unlocked );
		$this->assertSame( array( $own_cycle, $own_cycle ), $this->cycle_ids( $this->sut->find_history( $this->id ) ) );
	}

	/**
	 * @testdox A taken creation key returns null and writes nothing, with and without a lock; it is scoped to the contract.
	 */
	public function test_a_taken_creation_key_is_a_lost_race(): void {
		$other = $this->insert_released( Contract::create( array( 'extension_slug' => self::OWNER ) ) );
		$key   = array( 'creation_key' => 'renewal-paid-7' );

		$this->assertInstanceOf( HistoryEntry::class, $this->sut->add_history_entry( $this->entry( $key ), self::OWNER ) );
		$this->assertNull( $this->sut->add_history_entry( $this->entry( $key ), self::OWNER ), 'Unlocked repeat.' );

		$lock = $this->sut->acquire_lock( $this->id, self::OWNER, 'renew' );
		$this->assertInstanceOf( ContractLock::class, $lock );
		$this->assertNull( $this->sut->add_history_entry( $this->entry( $key ), self::OWNER, $lock ), 'Locked repeat.' );
		$this->assertCount( 1, $this->sut->find_history( $this->id ) );

		$this->assertInstanceOf( HistoryEntry::class, $this->sut->add_history_entry( $this->entry( $key + array( 'contract_id' => $other ) ), self::OWNER ), 'Another contract may use the key.' );
	}

	/**
	 * @testdox Creation keys differing only by surrounding spaces collide (trimmed); keys differing by case do not (binary).
	 */
	public function test_creation_keys_compare_trimmed_and_binary(): void {
		$this->assertInstanceOf( HistoryEntry::class, $this->sut->add_history_entry( $this->entry( array( 'creation_key' => 'created' ) ), self::OWNER ) );
		$this->assertNull( $this->sut->add_history_entry( $this->entry( array( 'creation_key' => 'created  ' ) ), self::OWNER ) );
		$this->assertInstanceOf( HistoryEntry::class, $this->sut->add_history_entry( $this->entry( array( 'creation_key' => 'Created' ) ), self::OWNER ) );
		$this->assertCount( 2, $this->sut->find_history( $this->id ) );
	}

	/**
	 * @testdox The payload round-trips through storage: nested arrays, unicode and null.
	 */
	public function test_the_payload_round_trips(): void {
		$payload = array(
			'text'   => 'Café ☕ "quoted" <b>markup</b>',
			'nested' => array(
				'list' => array( 1, 2.5, true, null ),
				'map'  => array( 'deep' => array( 'deeper' => 'yes' ) ),
			),
		);

		$this->sut->add_history_entry( $this->entry( array( 'payload' => $payload ) ), self::OWNER );
		$this->sut->add_history_entry( $this->entry( array( 'type' => 'held' ) ), self::OWNER );

		$entries = $this->sut->find_history( $this->id );
		$this->assertNull( $entries[0]->get_payload() );
		$this->assertSame( $payload, $entries[1]->get_payload() );
	}

	/**
	 * @testdox The returned entry's payload matches a fresh read: a nested object comes back as an array, and null stays null.
	 */
	public function test_the_returned_payload_matches_a_fresh_read(): void {
		$with_object = $this->sut->add_history_entry( $this->entry( array( 'payload' => array( 'actor' => (object) array( 'role' => 'system' ) ) ) ), self::OWNER );
		$without     = $this->sut->add_history_entry( $this->entry(), self::OWNER );
		$this->assertNotNull( $with_object );
		$this->assertNotNull( $without );

		$this->assertSame( array( 'actor' => array( 'role' => 'system' ) ), $with_object->get_payload() );
		$this->assertNull( $without->get_payload() );
		$entries = $this->sut->find_history( $this->id );
		$this->assertSame( $entries[1]->get_payload(), $with_object->get_payload() );
	}

	/**
	 * @testdox A failed insert throws RuntimeException and stores nothing ($mode).
	 * @dataProvider provide_failed_insert_modes
	 *
	 * @param string $mode     Case name.
	 * @param bool   $locked   Whether the insert is made with the caller's lock.
	 * @param bool   $with_key Whether the entry carries a creation key.
	 */
	public function test_a_failed_insert_throws( string $mode, bool $locked, bool $with_key ): void {
		$lock = null;
		if ( $locked ) {
			$lock = $this->sut->acquire_lock( $this->id, self::OWNER, 'renew' );
			$this->assertInstanceOf( ContractLock::class, $lock );
		}
		$entry = $this->entry( $with_key ? array( 'creation_key' => 'renewal-paid-9' ) : array() );

		$this->fail_next_query( '/^INSERT INTO \S*contract_history/' );
		try {
			$this->sut->add_history_entry( $entry, self::OWNER, $lock );
			$this->fail( "Expected the failed insert to throw ({$mode})." );
		} catch ( ContractLockedException $e ) {
			$this->fail( "The lock is still held, so the failure is not a lock conflict ({$mode})." );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( "Failed to add a history entry to contract {$this->id}", $e->getMessage() );
		}

		$this->assertSame( array(), $this->sut->find_history( $this->id ) );
	}

	/**
	 * @return array<string, array{0: string, 1: bool, 2: bool}>
	 */
	public function provide_failed_insert_modes(): array {
		return array(
			'unlocked, no key' => array( 'unlocked, no key', false, false ),
			'unlocked, keyed'  => array( 'unlocked, keyed', false, true ),
			'locked, no key'   => array( 'locked, no key', true, false ),
			'locked, keyed'    => array( 'locked, keyed', true, true ),
		);
	}

	/**
	 * @testdox A failed insert made with a lock lost meanwhile throws ContractLockedException.
	 */
	public function test_a_failed_insert_with_a_lost_lock_is_a_lock_conflict(): void {
		$this->pin_db_clock( '2035-01-01 00:00:00' );
		$old = $this->sut->acquire_lock( $this->id, self::OWNER, 'renew' );
		$this->assertInstanceOf( ContractLock::class, $old );
		$this->pin_db_clock( '2035-01-01 00:10:00' );
		$this->assertInstanceOf( ContractLock::class, $this->sut->acquire_lock( $this->id, self::OWNER, 'cancel' ) );

		// The old lock is still inside its trusted window, so the fence lets the insert run.
		$this->fail_next_query( '/^INSERT INTO \S*contract_history/' );
		try {
			$this->sut->add_history_entry( $this->entry(), self::OWNER, $old );
			$this->fail( 'Expected the lost lock to be reported.' );
		} catch ( ContractLockedException $e ) {
			$this->assertSame( 'cancel', $e->get_operation() );
		}

		$this->assertSame( array(), $this->sut->find_history( $this->id ) );
	}

	/**
	 * @testdox The creation-key fallback read finds a key only on its own contract and matches it exactly.
	 */
	public function test_creation_key_fallback_read(): void {
		$other = $this->insert_released( Contract::create( array( 'extension_slug' => self::OWNER ) ) );
		$this->sut->add_history_entry( $this->entry( array( 'creation_key' => 'renewal-paid-7' ) ), self::OWNER );

		$this->assertTrue( $this->call_private( $this->sut, 'has_history_creation_key', $this->id, 'renewal-paid-7' ) );
		$this->assertFalse( $this->call_private( $this->sut, 'has_history_creation_key', $this->id, 'RENEWAL-PAID-7' ) );
		$this->assertFalse( $this->call_private( $this->sut, 'has_history_creation_key', $other, 'renewal-paid-7' ) );
	}

	/**
	 * @testdox find_history() returns entries newest first and pages with limit and offset.
	 */
	public function test_find_history_pages_newest_first(): void {
		foreach ( array( 'a', 'b', 'c', 'd', 'e' ) as $type ) {
			$this->sut->add_history_entry( $this->entry( array( 'type' => $type ) ), self::OWNER );
		}

		$this->assertSame( array( 'e', 'd', 'c', 'b', 'a' ), $this->types( $this->sut->find_history( $this->id ) ) );
		$this->assertSame( array( 'e', 'd' ), $this->types( $this->sut->find_history( $this->id, 2, 0 ) ) );
		$this->assertSame( array( 'c', 'b' ), $this->types( $this->sut->find_history( $this->id, 2, 2 ) ) );
		$this->assertSame( array( 'a' ), $this->types( $this->sut->find_history( $this->id, 2, 4 ) ) );
		$this->assertSame( array(), $this->sut->find_history( 999999 ) );
	}

	/**
	 * @testdox find_history() throws on a failed read instead of returning an empty history.
	 */
	public function test_find_history_throws_on_a_failed_read(): void {
		$this->fail_next_query( '/^SELECT \* FROM \S*contract_history/' );

		$this->expectException( RuntimeException::class );
		$this->sut->find_history( $this->id );
	}

	/**
	 * @testdox delete() removes the contract's history with it, and no other contract's.
	 */
	public function test_delete_removes_the_history(): void {
		$other = $this->insert_released( Contract::create( array( 'extension_slug' => self::OWNER ) ) );
		$this->sut->add_history_entry( $this->entry(), self::OWNER );
		$this->sut->add_history_entry( $this->entry( array( 'contract_id' => $other ) ), self::OWNER );

		$this->assertTrue( $this->sut->delete( $this->id ) );

		$this->assertSame( array(), $this->sut->find_history( $this->id ) );
		$this->assertCount( 1, $this->sut->find_history( $other ) );
	}

	/**
	 * A new entry on the fixture contract.
	 *
	 * @param array<string, mixed> $overrides Entry attributes to replace.
	 */
	private function entry( array $overrides = array() ): HistoryEntry {
		return HistoryEntry::create(
			$overrides + array(
				'contract_id' => $this->id,
				'type'        => 'held',
			)
		);
	}

	/**
	 * Add a billing cycle to a contract and return its id.
	 *
	 * @param int $contract_id Contract id.
	 */
	private function add_cycle( int $contract_id ): int {
		$cycle = $this->add_cycle_locked(
			$contract_id,
			array(
				'starts_at_gmt' => '2026-07-15 00:00:00',
				'ends_at_gmt'   => '2026-08-15 00:00:00',
				'currency'      => 'USD',
			)
		);
		$this->assertNotNull( $cycle );

		return $cycle->get_id();
	}

	/**
	 * The types of entries, in order.
	 *
	 * @param array<int, HistoryEntry> $entries Entries.
	 * @return array<int, string>
	 */
	private function types( array $entries ): array {
		return array_map(
			static function ( HistoryEntry $entry ): string {
				return $entry->get_type();
			},
			$entries
		);
	}

	/**
	 * The cycle ids of entries, in order.
	 *
	 * @param array<int, HistoryEntry> $entries Entries.
	 * @return array<int, int|null>
	 */
	private function cycle_ids( array $entries ): array {
		return array_map(
			static function ( HistoryEntry $entry ): ?int {
				return $entry->get_cycle_id();
			},
			$entries
		);
	}
}
