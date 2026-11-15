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
	 * A stored contract id.
	 *
	 * @var int
	 */
	private $id;

	public function setUp(): void {
		parent::setUp();
		$this->sut = new ContractRepository();
		$this->id  = $this->insert_released( Contract::create( array( 'extension_slug' => self::OWNER ) ) );
	}

	public function test_add_meta_keeps_every_value_under_one_key_in_order(): void {
		$first  = $this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );
		$second = $this->sut->add_meta( $this->id, self::OWNER, 'note', 'two' );

		$this->assertIsInt( $first );
		$this->assertIsInt( $second );
		$this->assertGreaterThan( $first, $second );
		$this->assertSame( array( 'one', 'two' ), $this->sut->get_meta( $this->id, 'note' ) );
		$this->assertSame( 'one', $this->sut->get_meta( $this->id, 'note', true ) );
	}

	public function test_unique_add_on_an_existing_key_adds_nothing(): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );

		$this->assertNull( $this->sut->add_meta( $this->id, self::OWNER, 'note', 'two', true ) );
		$this->assertSame( array( 'one' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox A repeated unique add, with or without the lock, keeps one row: a plain read of the key, then the insert.
	 */
	public function test_unique_add_is_idempotent(): void {
		global $wpdb;

		$this->assertIsInt( $this->sut->add_meta( $this->id, self::OWNER, 'note', 'one', true ) );
		$this->assertNull( $this->sut->add_meta( $this->id, self::OWNER, 'note', 'two', true ) );

		$lock = $this->lock();
		$this->assertNull( $this->sut->add_meta( $this->id, self::OWNER, 'note', 'three', true, $lock ) );
		$lock->release();

		$this->assertIsInt( $this->sut->add_meta( $this->id, self::OWNER, 'fresh', 'x', true ) );

		$queries = $wpdb->num_queries;
		$this->assertIsInt( $this->sut->add_meta( $this->id, self::OWNER, 'single', 'x', true ) );
		$this->assertSame( 4, $wpdb->num_queries - $queries, 'Unlocked: the internal lock acquire, the key read, the insert, the release.' );

		$lock       = $this->lock();
		$queries    = $wpdb->num_queries;
		$statements = $this->statements_during(
			function () use ( $lock ): void {
				$this->assertIsInt( $this->sut->add_meta( $this->id, self::OWNER, 'single-locked', 'x', true, $lock ) );
			}
		);
		$this->assertSame( 2, $wpdb->num_queries - $queries, 'With a trusted lock: the key read, then the insert.' );
		$this->assert_no_self_table_insert_select( $statements, SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_META ) );
		$lock->release();

		$this->assertSame( array( 'one' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox update_meta on an absent key adds once; a repeat updates instead of duplicating.
	 */
	public function test_update_meta_does_not_duplicate(): void {
		$this->assertTrue( $this->sut->update_meta( $this->id, self::OWNER, 'note', 'one' ) );
		$this->assertTrue( $this->sut->update_meta( $this->id, self::OWNER, 'note', 'two' ) );

		$lock = $this->lock();
		$this->assertTrue( $this->sut->update_meta( $this->id, self::OWNER, 'locked', 'one', null, $lock ) );
		$this->assertTrue( $this->sut->update_meta( $this->id, self::OWNER, 'locked', 'two', null, $lock ) );
		$lock->release();

		$this->assertSame( array( 'two' ), $this->sut->get_meta( $this->id, 'note' ) );
		$this->assertSame( array( 'two' ), $this->sut->get_meta( $this->id, 'locked' ) );
	}

	/**
	 * @testdox A failed meta read throws instead of reading as an absent key.
	 */
	public function test_a_failed_meta_read_throws(): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );

		$this->fail_next_query( '/^\s*SELECT\s+meta_key,\s*meta_value/i' );

		$this->expectException( \RuntimeException::class );
		$this->sut->get_meta( $this->id, 'note' );
	}

	public function test_update_meta_adds_when_absent(): void {
		$this->assertTrue( $this->sut->update_meta( $this->id, self::OWNER, 'note', 'one' ) );
		$this->assertSame( array( 'one' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	public function test_update_meta_rewrites_every_row_for_the_key(): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'two' );

		$this->assertTrue( $this->sut->update_meta( $this->id, self::OWNER, 'note', 'three' ) );
		$this->assertSame( array( 'three', 'three' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	public function test_update_meta_with_a_previous_value_rewrites_only_matching_rows(): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'two' );

		$this->assertTrue( $this->sut->update_meta( $this->id, self::OWNER, 'note', 'three', 'two' ) );
		$this->assertSame( array( 'one', 'three' ), $this->sut->get_meta( $this->id, 'note' ) );
		$this->assertFalse( $this->sut->update_meta( $this->id, self::OWNER, 'note', 'four', 'missing' ) );
	}

	/**
	 * @testdox update_meta() on an existing key writes one UPDATE: between the internal lock's acquire and release, or alone under a trusted lock.
	 */
	public function test_update_meta_on_an_existing_key_is_one_statement(): void {
		global $wpdb;

		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );

		$queries = $wpdb->num_queries;
		$this->assertTrue( $this->sut->update_meta( $this->id, self::OWNER, 'note', 'two' ) );
		$this->assertSame( 3, $wpdb->num_queries - $queries, 'Unlocked: the internal lock acquire, the update, the release.' );

		$lock    = $this->lock();
		$queries = $wpdb->num_queries;
		$this->assertTrue( $this->sut->update_meta( $this->id, self::OWNER, 'note', 'three', null, $lock ) );
		$this->assertSame( 1, $wpdb->num_queries - $queries, 'With a trusted lock.' );
		$lock->release();

		$this->assertSame( array( 'three' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	public function test_update_meta_to_the_same_value_reports_no_change(): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );

		$this->assertFalse( $this->sut->update_meta( $this->id, self::OWNER, 'note', 'one' ) );
	}

	public function test_delete_meta_with_a_value_removes_only_that_row(): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'two' );

		$this->assertTrue( $this->sut->delete_meta( $this->id, self::OWNER, 'note', 'one' ) );
		$this->assertSame( array( 'two' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	public function test_delete_meta_without_a_value_removes_every_row_for_the_key(): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'two' );
		$this->sut->add_meta( $this->id, self::OWNER, 'other', 'kept' );

		$this->assertTrue( $this->sut->delete_meta( $this->id, self::OWNER, 'note' ) );
		$this->assertSame( array(), $this->sut->get_meta( $this->id, 'note' ) );
		$this->assertSame( array( 'kept' ), $this->sut->get_meta( $this->id, 'other' ) );
		$this->assertFalse( $this->sut->delete_meta( $this->id, self::OWNER, 'note' ) );
	}

	public function test_get_meta_without_a_key_groups_every_key(): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );
		$this->sut->add_meta( $this->id, self::OWNER, 'flag', 'yes' );
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'two' );

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

		$this->sut->add_meta( $this->id, self::OWNER, 'payload', $value );

		$this->assertSame( $value, $this->sut->get_meta( $this->id, 'payload', true ) );
		$this->assertTrue( $this->sut->delete_meta( $this->id, self::OWNER, 'payload', $value ) );
	}

	public function test_missing_key_reads_as_empty(): void {
		$this->assertSame( '', $this->sut->get_meta( $this->id, 'missing', true ) );
		$this->assertSame( array(), $this->sut->get_meta( $this->id, 'missing' ) );
		$this->assertSame( array(), $this->sut->get_meta( $this->id ) );
	}

	public function test_meta_is_scoped_to_its_contract(): void {
		$other = $this->insert_released( Contract::create( array( 'extension_slug' => self::OWNER ) ) );
		$this->sut->add_meta( $other, self::OWNER, 'note', 'theirs' );

		$this->assertSame( array(), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox Each meta write lands with the live lock.
	 */
	public function test_meta_writes_with_the_lock_land(): void {
		$lock = $this->lock();

		$this->assertIsInt( $this->sut->add_meta( $this->id, self::OWNER, 'note', 'one', false, $lock ) );
		$this->assertTrue( $this->sut->update_meta( $this->id, self::OWNER, 'note', 'two', null, $lock ) );
		$this->assertTrue( $this->sut->update_meta( $this->id, self::OWNER, 'other', 'added', null, $lock ) );
		$this->assertSame( array( 'two' ), $this->sut->get_meta( $this->id, 'note' ) );
		$this->assertTrue( $this->sut->delete_meta( $this->id, self::OWNER, 'note', null, $lock ) );
		$this->assertSame( array(), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox Unlocked meta writes are refused while another lock lives and land after release.
	 */
	public function test_unlocked_meta_writes_are_refused_while_locked(): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );
		$lock = $this->lock();

		$writes = array(
			'add'            => function (): void {
				$this->sut->add_meta( $this->id, self::OWNER, 'note', 'two' );
			},
			'update'         => function (): void {
				$this->sut->update_meta( $this->id, self::OWNER, 'note', 'two' );
			},
			'add via update' => function (): void {
				$this->sut->update_meta( $this->id, self::OWNER, 'fresh', 'two' );
			},
			'delete'         => function (): void {
				$this->sut->delete_meta( $this->id, self::OWNER, 'note' );
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
		$this->assertTrue( $this->sut->update_meta( $this->id, self::OWNER, 'note', 'two' ) );
		$this->assertIsInt( $this->sut->add_meta( $this->id, self::OWNER, 'note', 'three' ) );
		$this->assertTrue( $this->sut->delete_meta( $this->id, self::OWNER, 'note', 'two' ) );
		$this->assertSame( array( 'three' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox A meta add for an unknown contract throws and inserts nothing.
	 */
	public function test_a_meta_add_for_an_unknown_contract_throws(): void {
		foreach ( array( 'add_meta', 'update_meta' ) as $method ) {
			try {
				$this->sut->{$method}( 999999, self::OWNER, 'note', 'orphan' );
				$this->fail( "Expected {$method}() to refuse an unknown contract." );
			} catch ( InvalidArgumentException $e ) {
				$this->assertSame( array(), $this->sut->get_meta( 999999 ) );
			}
		}
		$this->assertFalse( $this->sut->delete_meta( 999999, self::OWNER, 'note' ) );
	}

	/**
	 * @testdox A meta write with a lock for another contract of another extension is refused for the contract first.
	 */
	public function test_a_meta_write_with_another_contracts_lock_is_refused(): void {
		$other = $this->insert_released( Contract::create( array( 'extension_slug' => 'another-extension' ) ) );
		$lock  = $this->sut->acquire_lock( $other, 'another-extension', 'hold' );
		$this->assertInstanceOf( ContractLock::class, $lock );

		$writes = array(
			'add_meta'    => function () use ( $lock ): void {
				$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one', false, $lock );
			},
			'update_meta' => function () use ( $lock ): void {
				$this->sut->update_meta( $this->id, self::OWNER, 'note', 'one', null, $lock );
			},
			'delete_meta' => function () use ( $lock ): void {
				$this->sut->delete_meta( $this->id, self::OWNER, 'note', null, $lock );
			},
		);
		foreach ( $writes as $method => $write ) {
			try {
				$write();
				$this->fail( "Expected {$method}() to refuse the lock." );
			} catch ( InvalidArgumentException $e ) {
				$this->assertStringContainsString( "The lock belongs to contract {$other}, not contract {$this->id}.", $e->getMessage(), $method );
			}
		}
	}

	/**
	 * @testdox A failed meta write throws.
	 */
	public function test_a_failed_meta_write_throws(): void {
		global $wpdb;

		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );

		$break = static function ( string $query ): string {
			return 1 === preg_match( '/^\s*(UPDATE|DELETE)\s/i', $query ) && false !== strpos( $query, 'contract_meta' ) ? 'SELECT * FROM table_that_does_not_exist_for_tests' : $query;
		};
		add_filter( 'query', $break );
		$suppress = $wpdb->suppress_errors( true );
		try {
			foreach ( array( 'update_meta', 'delete_meta' ) as $method ) {
				try {
					$this->sut->{$method}( $this->id, self::OWNER, 'note', 'two' );
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
	 * @testdox A meta key longer than the 255-character column is refused and stores nothing, in ASCII or multibyte characters; a 255-character key is stored.
	 */
	public function test_an_over_long_meta_key_is_refused(): void {
		foreach ( array( 'k', 'é' ) as $char ) {
			$too_long = str_repeat( $char, 256 );
			foreach ( array( 'add_meta', 'update_meta' ) as $method ) {
				try {
					$this->sut->{$method}( $this->id, self::OWNER, $too_long, 'value' );
					$this->fail( "Expected {$method}() to refuse an over-long key of '{$char}'." );
				} catch ( RuntimeException $e ) {
					// The message tells this refusal apart from a ContractLockedException, also a RuntimeException.
					$this->assertStringContainsString( 'is longer than the column allows', $e->getMessage(), $method );
				}
			}
			$this->assertSame( array(), $this->sut->get_meta( $this->id ) );

			$longest = str_repeat( $char, 255 );
			$this->assertIsInt( $this->sut->add_meta( $this->id, self::OWNER, $longest, 'one' ) );
			$this->sut->delete_meta( $this->id, self::OWNER, $longest );
			$this->assertTrue( $this->sut->update_meta( $this->id, self::OWNER, $longest, 'two' ) );
			$this->assertSame( array( $longest => array( 'two' ) ), $this->sut->get_meta( $this->id ) );
			$this->sut->delete_meta( $this->id, self::OWNER, $longest );
		}
	}

	private function lock(): ContractLock {
		$lock = $this->sut->acquire_lock( $this->id, self::OWNER, 'hold' );
		$this->assertInstanceOf( ContractLock::class, $lock );

		return $lock;
	}
}
