<?php
/**
 * Integration tests for the multi-value contract meta reads and writes on
 * ContractRepository (WordPress post-meta semantics).
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
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository
 */
class ContractMetaTest extends EngineIntegrationTestCase {

	/**
	 * The System Under Test.
	 *
	 * @var ContractRepository
	 */
	private $sut;

	/**
	 * A stored contract id.
	 *
	 * @var int
	 */
	private $id;

	public function setUp(): void {
		parent::setUp();
		$this->sut = new ContractRepository();
		$this->id  = $this->sut->insert( Contract::create( array( 'extension_slug' => 'acme-subs' ) ) );
	}

	public function test_add_meta_keeps_every_value_under_one_key_in_order(): void {
		$first  = $this->sut->add_meta( $this->id, 'note', 'one' );
		$second = $this->sut->add_meta( $this->id, 'note', 'two' );

		$this->assertIsInt( $first );
		$this->assertIsInt( $second );
		$this->assertGreaterThan( $first, $second );
		$this->assertSame( array( 'one', 'two' ), $this->sut->get_meta( $this->id, 'note' ) );
		$this->assertSame( 'one', $this->sut->get_meta( $this->id, 'note', true ) );
	}

	public function test_unique_add_on_an_existing_key_adds_nothing(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );

		$this->assertNull( $this->sut->add_meta( $this->id, 'note', 'two', true ) );
		$this->assertSame( array( 'one' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox A repeated unique add, with or without the lock, keeps one row and checks the key in its insert.
	 */
	public function test_unique_add_is_idempotent(): void {
		global $wpdb;

		$this->assertIsInt( $this->sut->add_meta( $this->id, 'note', 'one', true ) );
		$this->assertNull( $this->sut->add_meta( $this->id, 'note', 'two', true ) );

		$lock = $this->lock();
		$this->assertNull( $this->sut->add_meta( $this->id, 'note', 'three', true, $lock ) );
		$lock->release();

		$this->assertIsInt( $this->sut->add_meta( $this->id, 'fresh', 'x', true ) );

		$queries = $wpdb->num_queries;
		$this->assertIsInt( $this->sut->add_meta( $this->id, 'single', 'x', true ) );
		$this->assertSame( 2, $wpdb->num_queries - $queries, 'The insert and its duplicate check: no read before the insert.' );

		$this->assertSame( array( 'one' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox update_meta on an absent key adds once; a repeat updates instead of duplicating.
	 */
	public function test_update_meta_does_not_duplicate(): void {
		$this->assertTrue( $this->sut->update_meta( $this->id, 'note', 'one' ) );
		$this->assertTrue( $this->sut->update_meta( $this->id, 'note', 'two' ) );

		$lock = $this->lock();
		$this->assertTrue( $this->sut->update_meta( $this->id, 'locked', 'one', null, $lock ) );
		$this->assertTrue( $this->sut->update_meta( $this->id, 'locked', 'two', null, $lock ) );
		$lock->release();

		$this->assertSame( array( 'two' ), $this->sut->get_meta( $this->id, 'note' ) );
		$this->assertSame( array( 'two' ), $this->sut->get_meta( $this->id, 'locked' ) );
	}

	/**
	 * @testdox An unlocked unique add that lands after a concurrent one removes its row and returns null.
	 */
	public function test_a_unique_add_racing_an_earlier_row_removes_its_duplicate(): void {
		$this->insert_rival_before_the_duplicate_check( 'note', 'rival' );

		$this->assertNull( $this->sut->add_meta( $this->id, 'note', 'mine', true ) );
		$this->assertSame( array( 'rival' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox An unlocked update_meta that lands after a concurrent add removes its row and updates instead.
	 */
	public function test_an_update_racing_an_earlier_row_updates_it_instead(): void {
		$this->insert_rival_before_the_duplicate_check( 'note', 'rival' );

		$this->assertTrue( $this->sut->update_meta( $this->id, 'note', 'mine' ) );
		$this->assertSame( array( 'mine' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox A failed duplicate check keeps the landed row and logs a warning.
	 */
	public function test_a_failed_duplicate_check_keeps_the_row(): void {
		$this->fail_next_query( '/^\s*SELECT MIN\( id \)/i' );

		$meta_id = null;
		$logs    = $this->capture_engine_log(
			'warning',
			array( 'contract_id' => $this->id ),
			function () use ( &$meta_id ) {
				$meta_id = $this->sut->add_meta( $this->id, 'note', 'mine', true );
			}
		);

		$this->assertIsInt( $meta_id );
		$this->assertNotSame( array(), $logs, 'The warning is logged.' );
		$this->assertSame( array( 'mine' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox A failed meta read throws instead of reading as an absent key.
	 */
	public function test_a_failed_meta_read_throws(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );

		$this->fail_next_query( '/^\s*SELECT\s+meta_key,\s*meta_value/i' );

		$this->expectException( \RuntimeException::class );
		$this->sut->get_meta( $this->id, 'note' );
	}

	public function test_update_meta_adds_when_absent(): void {
		$this->assertTrue( $this->sut->update_meta( $this->id, 'note', 'one' ) );
		$this->assertSame( array( 'one' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	public function test_update_meta_rewrites_every_row_for_the_key(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );
		$this->sut->add_meta( $this->id, 'note', 'two' );

		$this->assertTrue( $this->sut->update_meta( $this->id, 'note', 'three' ) );
		$this->assertSame( array( 'three', 'three' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	public function test_update_meta_with_a_previous_value_rewrites_only_matching_rows(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );
		$this->sut->add_meta( $this->id, 'note', 'two' );

		$this->assertTrue( $this->sut->update_meta( $this->id, 'note', 'three', 'two' ) );
		$this->assertSame( array( 'one', 'three' ), $this->sut->get_meta( $this->id, 'note' ) );
		$this->assertFalse( $this->sut->update_meta( $this->id, 'note', 'four', 'missing' ) );
	}

	public function test_update_meta_to_the_same_value_reports_no_change(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );

		$this->assertFalse( $this->sut->update_meta( $this->id, 'note', 'one' ) );
	}

	public function test_delete_meta_with_a_value_removes_only_that_row(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );
		$this->sut->add_meta( $this->id, 'note', 'two' );

		$this->assertTrue( $this->sut->delete_meta( $this->id, 'note', 'one' ) );
		$this->assertSame( array( 'two' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	public function test_delete_meta_without_a_value_removes_every_row_for_the_key(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );
		$this->sut->add_meta( $this->id, 'note', 'two' );
		$this->sut->add_meta( $this->id, 'other', 'kept' );

		$this->assertTrue( $this->sut->delete_meta( $this->id, 'note' ) );
		$this->assertSame( array(), $this->sut->get_meta( $this->id, 'note' ) );
		$this->assertSame( array( 'kept' ), $this->sut->get_meta( $this->id, 'other' ) );
		$this->assertFalse( $this->sut->delete_meta( $this->id, 'note' ) );
	}

	public function test_get_meta_without_a_key_groups_every_key(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );
		$this->sut->add_meta( $this->id, 'flag', 'yes' );
		$this->sut->add_meta( $this->id, 'note', 'two' );

		$this->assertSame(
			array(
				'note' => array( 'one', 'two' ),
				'flag' => array( 'yes' ),
			),
			$this->sut->get_meta( $this->id )
		);
	}

	public function test_arrays_round_trip_through_serialization(): void {
		$value = array(
			'a' => 1,
			'b' => array( 'c' ),
		);

		$this->sut->add_meta( $this->id, 'payload', $value );

		$this->assertSame( $value, $this->sut->get_meta( $this->id, 'payload', true ) );
		$this->assertTrue( $this->sut->delete_meta( $this->id, 'payload', $value ) );
	}

	public function test_missing_key_reads_as_empty(): void {
		$this->assertSame( '', $this->sut->get_meta( $this->id, 'missing', true ) );
		$this->assertSame( array(), $this->sut->get_meta( $this->id, 'missing' ) );
		$this->assertSame( array(), $this->sut->get_meta( $this->id ) );
	}

	public function test_meta_is_scoped_to_its_contract(): void {
		$other = $this->sut->insert( Contract::create( array( 'extension_slug' => 'acme-subs' ) ) );
		$this->sut->add_meta( $other, 'note', 'theirs' );

		$this->assertSame( array(), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @dataProvider provide_write_methods
	 *
	 * @param string $method Write method name.
	 */
	public function test_an_empty_key_is_rejected_on_writes( string $method ): void {
		$this->expectException( InvalidArgumentException::class );

		$this->sut->{$method}( $this->id, '', 'value' );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public function provide_write_methods(): array {
		return array(
			'add'    => array( 'add_meta' ),
			'update' => array( 'update_meta' ),
			'delete' => array( 'delete_meta' ),
		);
	}

	/**
	 * @testdox Each meta write lands with the live lock.
	 */
	public function test_meta_writes_with_the_lock_land(): void {
		$lock = $this->lock();

		$this->assertIsInt( $this->sut->add_meta( $this->id, 'note', 'one', false, $lock ) );
		$this->assertTrue( $this->sut->update_meta( $this->id, 'note', 'two', null, $lock ) );
		$this->assertTrue( $this->sut->update_meta( $this->id, 'other', 'added', null, $lock ) );
		$this->assertSame( array( 'two' ), $this->sut->get_meta( $this->id, 'note' ) );
		$this->assertTrue( $this->sut->delete_meta( $this->id, 'note', null, $lock ) );
		$this->assertSame( array(), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox Unlocked meta writes are refused while another lock lives and land after release.
	 */
	public function test_unlocked_meta_writes_are_refused_while_locked(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );
		$lock = $this->lock();

		$writes = array(
			'add'            => function (): void {
				$this->sut->add_meta( $this->id, 'note', 'two' );
			},
			'update'         => function (): void {
				$this->sut->update_meta( $this->id, 'note', 'two' );
			},
			'add via update' => function (): void {
				$this->sut->update_meta( $this->id, 'fresh', 'two' );
			},
			'delete'         => function (): void {
				$this->sut->delete_meta( $this->id, 'note' );
			},
		);

		foreach ( $writes as $name => $write ) {
			try {
				$write();
				$this->fail( "Expected the unlocked {$name} to be refused." );
			} catch ( ContractLockedException $e ) {
				$this->assertSame( 'hold', $e->get_operation() );
			}
		}
		$this->assertSame( array( 'note' => array( 'one' ) ), $this->sut->get_meta( $this->id ), 'Nothing was written.' );

		$lock->release();
		$this->assertTrue( $this->sut->update_meta( $this->id, 'note', 'two' ) );
		$this->assertIsInt( $this->sut->add_meta( $this->id, 'note', 'three' ) );
		$this->assertTrue( $this->sut->delete_meta( $this->id, 'note', 'two' ) );
		$this->assertSame( array( 'three' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox A meta add for an unknown contract throws and inserts nothing.
	 */
	public function test_a_meta_add_for_an_unknown_contract_throws(): void {
		foreach ( array( 'add_meta', 'update_meta' ) as $method ) {
			try {
				$this->sut->{$method}( 999999, 'note', 'orphan' );
				$this->fail( "Expected {$method}() to refuse an unknown contract." );
			} catch ( InvalidArgumentException $e ) {
				$this->assertSame( array(), $this->sut->get_meta( 999999 ) );
			}
		}
		$this->assertFalse( $this->sut->delete_meta( 999999, 'note' ) );
	}

	/**
	 * @testdox A meta write with a lock for another contract is refused.
	 */
	public function test_a_meta_write_with_another_contracts_lock_is_refused(): void {
		$other = $this->sut->insert( Contract::create( array( 'extension_slug' => 'acme-subs' ) ) );
		$lock  = $this->sut->acquire_lock( $other, 'hold' );
		$this->assertInstanceOf( ContractLock::class, $lock );

		$this->expectException( InvalidArgumentException::class );
		$this->sut->add_meta( $this->id, 'note', 'one', false, $lock );
	}

	/**
	 * @testdox A failed meta write throws.
	 */
	public function test_a_failed_meta_write_throws(): void {
		global $wpdb;

		$this->sut->add_meta( $this->id, 'note', 'one' );

		$break = static function ( string $query ): string {
			return 1 === preg_match( '/^\s*(UPDATE|DELETE)\s/i', $query ) && false !== strpos( $query, 'contract_meta' ) ? 'SELECT * FROM table_that_does_not_exist_for_tests' : $query;
		};
		add_filter( 'query', $break );
		$suppress = $wpdb->suppress_errors( true );
		try {
			foreach ( array( 'update_meta', 'delete_meta' ) as $method ) {
				try {
					$this->sut->{$method}( $this->id, 'note', 'two' );
					$this->fail( "Expected {$method}() to throw." );
				} catch ( RuntimeException $e ) {
					$this->assertStringContainsString( 'contract meta', $e->getMessage() );
				}
			}
		} finally {
			$wpdb->suppress_errors( $suppress );
			remove_filter( 'query', $break );
		}
	}

	/**
	 * Simulate a concurrent add the absence check missed: right before the duplicate check, store
	 * a row for `$key` with an id lower than the one just inserted (a gap reserved up front).
	 *
	 * @param string $key   Meta key.
	 * @param string $value The rival row's value.
	 */
	private function insert_rival_before_the_duplicate_check( string $key, string $value ): void {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_META );
		$this->sut->add_meta( $this->id, 'gap', 'x' );
		$gap_id = (int) $wpdb->insert_id;
		$this->sut->delete_meta( $this->id, 'gap' );

		$contract_id = $this->id;
		$rival       = static function ( $query ) use ( $table, $gap_id, $contract_id, $key, $value, &$rival ) {
			global $wpdb;
			if ( is_string( $query ) && 1 === preg_match( '/^\s*SELECT MIN\( id \)/i', $query ) ) {
				remove_filter( 'query', $rival );
				$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$table,
					array(
						'id'          => $gap_id,
						'contract_id' => $contract_id,
						'meta_key'    => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_value'  => $value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					)
				);
			}
			return $query;
		};
		add_filter( 'query', $rival );
	}

	private function lock(): ContractLock {
		$lock = $this->sut->acquire_lock( $this->id, 'hold' );
		$this->assertInstanceOf( ContractLock::class, $lock );

		return $lock;
	}
}
